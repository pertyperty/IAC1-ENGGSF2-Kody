<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Transactions\WalletLedger;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('verified learners choose an independent Tower track without fabricated progress', function (string $track) {
    $this->seed(TowerLevelSeeder::class);
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    $this->get(route('home'))->assertOk()->assertSee('Where would you like to start?');
    $this->post(route('tower.proficiency'), ['proficiency' => $track])->assertRedirect();
    expect($user->fresh()->coding_proficiency)->toBe($track);
    $this->assertDatabaseCount('tower_clearances', 0);
    $this->assertDatabaseCount('tower_stage_completions', 0);
    $this->get(route('home'))->assertOk()->assertDontSee('Where would you like to start?')
        ->assertViewHas('tower', fn ($tower) => $tower['selected_track'] === $track);
    $this->flushSession();
    moduleSignIn($this, $user);
    $this->get(route('home'))->assertViewHas('tower', fn ($tower) => $tower['selected_track'] === $track);
})->with(['beginner', 'intermediate', 'expert']);

test('proficiency rejects unknown choices and staff accounts', function () {
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    $this->postJson(route('tower.proficiency'), ['proficiency' => 'master'])->assertUnprocessable();
    expect($user->fresh()->coding_proficiency)->toBeNull();
    $staff = User::factory()->create(['account_role' => Role::Administrator]);
    moduleSignIn($this, $staff);
    $this->post(route('tower.proficiency'), ['proficiency' => 'expert'])->assertForbidden();
});

test('notification filters are owned validated and keep read and unread separate', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    foreach ([[$user, 'Unread notice', null], [$user, 'Read notice', now()], [$other, 'Private other notice', null]] as [$owner, $title, $read]) {
        DB::table('notifications')->insert(['id' => (string) Str::uuid(), 'type' => 'account.notice', 'notifiable_type' => User::class,
            'notifiable_id' => $owner->id, 'data' => json_encode(['title' => $title]), 'read_at' => $read, 'created_at' => now(), 'updated_at' => now()]);
    }
    moduleSignIn($this, $user);
    $this->get(route('notifications.index', ['state' => 'unread']))->assertOk()->assertSee('Unread notice')->assertDontSee('Read notice')->assertDontSee('Private other notice');
    $this->get(route('notifications.index', ['state' => 'read']))->assertOk()->assertSee('Read notice')->assertDontSee('Unread notice');
    $this->getJson(route('notifications.index', ['state' => 'invalid']))->assertUnprocessable();
});

test('creator list filters use literal titles statuses and preserve ownership', function () {
    $module = moduleFixture(false, ['title' => 'Own searchable lesson']);
    moduleFixture(false, ['title' => 'Other searchable lesson']);
    moduleSignIn($this, $module->creator);
    $this->get(route('studio.index', ['q' => 'searchable', 'status' => 'Draft']))->assertOk()->assertSee('Own searchable lesson')->assertDontSee('Other searchable lesson');
    $this->get(route('studio.index', ['q' => '%']))->assertOk()->assertDontSee('Own searchable lesson');
    $this->get(route('studio.index', ['status' => 'Published']))->assertOk()->assertDontSee('Own searchable lesson');
    $this->getJson(route('studio.index', ['status' => 'invalid']))->assertUnprocessable();
});

test('course difficulty and preset browsing filters are validated and resettable', function () {
    $this->getJson(route('course-learning.catalog', ['difficulty' => 'Master']))->assertUnprocessable();
    $admin = moduleAccount(Role::Administrator);
    moduleSignIn($this, $admin);
    $preset = presetFixture();
    $this->get(route('game-presets.index', ['q' => 'Picnic', 'status' => 'Active']))->assertOk()->assertSee('Picnic template')->assertSee('Clear filters')->assertDontSee('Create preset');
    $this->get(route('game-presets.index', ['q' => '%']))->assertOk()->assertDontSee('Picnic template');
    $this->get(route('game-presets.edit', $preset))->assertOk()->assertSee('Where this preset is used');
    $this->getJson(route('game-presets.index', ['status' => 'Unknown']))->assertUnprocessable();
});

test('wallet display direction filters preserve the authoritative balance and ledger history', function () {
    $user = moduleAccount(Role::Learner);
    $module = moduleFixture(true, ['price_kb' => 5]);
    $ledger = app(WalletLedger::class);
    DB::transaction(function () use ($ledger, $user, $module): void {
        $ledger->credit($user->id, 'filter-credit', 'Reward', 20, 0);
        $ledger->buy($user->id, $module->created_by, 'module', $module->id, $module->published_revision_id, 5, 'Cash');
    });
    moduleSignIn($this, $user);
    foreach (['credit' => 20, 'debit' => -5] as $direction => $amount) {
        $this->get(route('wallet.index', ['direction' => $direction]))->assertOk()
            ->assertViewHas('operations', fn ($operations) => $operations->count() === 1 && $operations->first()->kodebits === $amount)
            ->assertViewHas('wallet', fn ($wallet) => $wallet['balance'] === 15);
    }
    $this->getJson(route('wallet.index', ['direction' => 'unknown']))->assertUnprocessable();
    $this->assertDatabaseCount('wallet_operations', 2);
});
