<?php

use App\Enums\Role;
use App\Models\TowerLevel;
use App\Models\TowerRevision;
use App\Models\User;
use App\Services\Games\GameAssessment;
use App\Services\Games\TowerCurriculumAudit;
use App\Services\Games\TowerEditorialUpgrade;
use App\Services\Gamification\TowerProgression;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('optional profile identities can be cleared and restored without changing security or public username', function () {
    $user = User::factory()->create(['first_name' => null, 'last_name' => null, 'name' => 'usernamefallback']);
    profileSignIn($this, $user);
    $sessionId = session()->getId();
    foreach ([['Renée', null, 'Renée'], [null, 'de la Cruz', 'de la Cruz'], [null, null, $user->username]] as [$first, $last, $expected]) {
        $this->patch(route('account.update'), profileEditData($user, ['first_name' => $first, 'last_name' => $last]))->assertRedirect(route('account.show'));
        expect($user->fresh()->name)->toBe($expected)->and($user->fresh()->username)->toBe($user->username)
            ->and(session()->getId())->toBe($sessionId);
    }
    $migration = require database_path('migrations/2026_10_18_000001_allow_optional_profile_names.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Optional identities exist');
});

test('independent tracks admit their first level but enforce internal sequence without fabricating earlier clears', function () {
    $this->seed(TowerLevelSeeder::class);
    $user = moduleAccount(Role::Learner);
    moduleSignIn($this, $user);
    $start = TowerLevel::where('position', 26)->sole();
    $next = TowerLevel::where('position', 27)->sole();
    $this->get(route('tower.show', $start))->assertOk()->assertSee('Intermediate practice')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('tower.show', $next))->assertForbidden();
    $this->postJson(route('tower.complete', [$next, $next->current_revision_id, 0]), towerSolution($next->currentRevision->stages[0]))->assertForbidden();
    $this->postJson(route('tower.complete', [$start, $start->current_revision_id, 0]), towerSolution($start->currentRevision->stages[0]))->assertOk()->assertJsonPath('tower.next', route('tower.show', $next));
    $this->assertDatabaseCount('tower_clearances', 1);
    $this->assertDatabaseCount('xp_awards', 0);
    $this->get(route('tower.show', $next))->assertOk();
    $snapshot = app(TowerProgression::class)->snapshot($user->id);
    expect($snapshot['track_next']['beginner']->position)->toBe(1)->and($snapshot['track_next']['intermediate']->position)->toBe(27);
    $this->assertDatabaseMissing('tower_clearances', ['user_id' => $user->id, 'level_id' => TowerLevel::where('position', 1)->value('id')]);
});

test('all eighty teaching proposals have bounded server-verified reference solutions and honest outcomes', function () {
    $seed = new TowerLevelSeeder;
    foreach ($seed->curriculum() as $index => $entry) {
        foreach (app(TowerCurriculumAudit::class)->entry($entry, $index + 1)['stages'] as $stage) {
            expect($stage['reference_verified'])->toBeTrue()->and($stage['objective'])->not->toBeEmpty();
        }
    }
    expect($seed->curriculum()[4]['stages'][0]['hint'])->toContain('3', '18')->not->toContain('add 3, then multiply 2');
    expect($seed->curriculum()[9]['stages'][0]['instructions'])->toContain('3 crystals')->not->toContain('both');
    expect($seed->curriculum()[49]['concept'])->toBe('Foundation synthesis');
    $this->artisan('kody:tower-curriculum-audit', ['--seed-only' => true])->assertSuccessful();
});

test('editorial references handle valid duplicate sorted identity empty and boundary scenarios', function () {
    foreach ([['sort-lab', ['items' => [1, 2, 1]]], ['sort-lab', ['items' => [2, 2]]],
        ['memory-trail', ['start' => [], 'target' => []]], ['string-forge', ['start' => 'MiXeD', 'target' => 'MiXeD']],
        ['number-machine', ['start' => -100, 'target' => 100]]] as [$template, $scenario]) {
        $game = array_replace(config('arcade.'.$template), ['scenario' => $scenario]);
        $submission = app(TowerCurriculumAudit::class)->solution($game);
        expect(app(GameAssessment::class)->succeeds($game, $submission))->toBeTrue();
    }
});

test('guarded teaching rollout preserves old revisions custom edits clears and idempotency', function () {
    $seed = new TowerLevelSeeder;
    foreach ($seed->legacyCurriculum() as $index => $entry) {
        $level = TowerLevel::create(['position' => $index + 1]);
        $revision = TowerRevision::create($entry + ['level_id' => $level->id, 'number' => 1, 'source_notes' => 'Original Kody curriculum and scenarios, 2026-10-10–11. Editorial learning material; not copied course or repository content.']);
        $level->update(['current_revision_id' => $revision->id]);
    }
    $user = moduleAccount(Role::Learner);
    $first = TowerLevel::where('position', 1)->sole();
    app(TowerProgression::class)->complete($user, 'module-test-session', $first, $first->current_revision_id, 0, towerSolution($first->currentRevision->stages[0]));
    $original = $first->currentRevision->toArray();
    $custom = TowerLevel::where('position', 2)->sole();
    $custom->currentRevision->update(['description' => 'Owned editorial customization']);
    TowerLevel::where('position', 3)->sole()->currentRevision->update(['source_notes' => 'Original Kody curriculum and scenarios, 2026-10-10–11. Custom editorial provenance.']);
    $upgrade = app(TowerEditorialUpgrade::class);
    expect($upgrade->run()['eligible'])->toHaveCount(48);
    $this->assertDatabaseCount('tower_revisions', 50);
    expect($upgrade->run(true)['published'])->toHaveCount(48)->and($upgrade->run(true)['published'])->toBe([]);
    expect(TowerRevision::findOrFail($original['id'])->toArray())->toBe($original);
    expect($custom->fresh()->current_revision_id)->toBe($custom->current_revision_id);
    $this->assertDatabaseCount('tower_clearances', 1);
    expect(DB::table('audit_events')->where('event', 'tower.editorial-updated')->count())->toBe(48);
    $this->artisan('kody:tower-curriculum-audit')->assertSuccessful();
});
