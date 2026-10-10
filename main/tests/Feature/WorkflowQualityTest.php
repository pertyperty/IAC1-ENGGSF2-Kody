<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('shared layout renders one document, one CSRF token and one main landmark across both shells', function (string $name) {
    $response = $this->get(route($name))->assertOk();
    $html = $response->getContent();
    expect(substr_count($html, '<!DOCTYPE html>'))->toBe(1)
        ->and(substr_count($html, 'name="csrf-token"'))->toBe(1)
        ->and(substr_count($html, 'id="main-content"'))->toBe(1)
        ->and(substr_count($html, 'data-toast-region'))->toBe(1);
    $response->assertSee('name="referrer" content="no-referrer"', false);
})->with(['home', 'login', 'register', 'recovery.request', 'help.index']);

test('dashboard reuses its owned snapshots rather than querying progression and unread counts twice', function (Role $role) {
    moduleSignIn($this, moduleAccount($role));
    DB::enableQueryLog();
    DB::flushQueryLog();
    try {
        $this->get(route('dashboard'))->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');
        $participant = $role === Role::Learner;
        expect($queries->filter(fn ($sql) => str_contains($sql, '"learning_progress"'))->count())->toBe($participant ? 1 : 0)
            ->and($queries->filter(fn ($sql) => str_contains($sql, '"learning_level_completions"'))->count())->toBe($participant ? 1 : 0)
            ->and($queries->filter(fn ($sql) => str_contains($sql, '"xp_totals"'))->count())->toBe(1)
            ->and($queries->filter(fn ($sql) => str_contains($sql, '"notifications"') && str_contains($sql, 'count(*)'))->count())->toBe(1);
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
})->with(Role::cases());

test('retained creator notices omit forbidden links after a role change while preserving readable history', function () {
    $module = moduleFixture(true);
    $owner = User::findOrFail($module->created_by);
    moduleSignIn($this, $owner);
    $this->get(route('notifications.index'))->assertOk()->assertSee(route('studio.edit', $module));
    $owner->forceFill(['account_role' => Role::Moderator])->save();
    moduleSignIn($this, $owner->fresh());
    $this->get(route('notifications.index'))->assertOk()->assertSee($module->publishedRevision->title)
        ->assertDontSee(route('studio.edit', $module));
    $this->get(route('studio.edit', $module))->assertForbidden();
});
