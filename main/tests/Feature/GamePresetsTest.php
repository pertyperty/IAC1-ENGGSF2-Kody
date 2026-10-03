<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\GamePreset;
use App\Models\GamePresetRevision;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Content\ModulePublishing;
use App\Services\Games\GamePresets;
use App\Services\Gamification\LearningProgression;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function presetData(array $overrides = []): array
{
    return array_replace(['name' => 'Picnic template', 'record_version' => 1, 'basis' => 'sequences', 'title' => 'Picnic trail',
        'instructions' => 'Guide Kody to the picnic.', 'hint' => 'Right, right, up, right, right.', 'learning_idea' => 'Order matters.',
        'question' => 'Does order matter?', 'choice_a' => 'Yes', 'choice_b' => 'No', 'answer' => 'a',
        'explanation' => 'A sequence follows instructions in order.', 'reward_mode' => 'Deferred'], $overrides);
}

function presetFixture(array $data = []): GamePreset
{
    return app(GamePresets::class)->save(moduleAccount(Role::Administrator), 'module-test-session', presetData($data));
}

test('G08 administrator creates typed reusable preset rules audit and escaped local preview', function (string $basis) {
    $admin = User::factory()->create(['account_role' => Role::Administrator]);
    moduleSignIn($this, $admin);
    $this->get(route('game-presets.create'))->assertOk();
    $this->post(route('game-presets.store'), presetData(['basis' => $basis, 'title' => '<script>bad()</script>']))->assertRedirect();
    $preset = GamePreset::sole();
    $revision = $preset->currentRevision;
    expect($preset->created_by)->toBe($admin->id)->and($revision->reward_mode)->toBe('Deferred')
        ->and($revision->scoring)->toBe('ValidatedWin')->and($revision->participation)->toBe('VerifiedActiveParticipants');
    expect($revision->instance['template'])->toBe($basis === 'quiz' ? 'choice-quiz' : 'command-garden');
    if ($basis !== 'quiz') {
        expect($revision->instance['path'])->toBe(config('learning.instances.'.$basis.'.path'));
    }
    $this->get(route('game-presets.edit', $preset))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>bad()', false)
        ->assertDontSee('data-completion-url', false)->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('game-presets.index'))->assertOk()->assertSee('Picnic template');
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->assertDatabaseHas('audit_events', ['event' => 'game_preset.created', 'actor_id' => $admin->id]);
})->with(['sequences', 'loops', 'conditions', 'quiz']);

test('G08 G09 G10 all nonadministrator roles are denied administration and direct mutations', function (Role $role) {
    $preset = presetFixture();
    $user = moduleAccount($role);
    moduleSignIn($this, $user);
    $this->get(route('game-presets.index'))->assertForbidden();
    $this->get(route('game-presets.create'))->assertForbidden();
    $this->get(route('game-presets.edit', $preset))->assertForbidden();
    $this->postJson(route('game-presets.store'), presetData())->assertForbidden();
    $this->putJson(route('game-presets.update', $preset), presetData())->assertForbidden();
    $this->postJson(route('game-presets.inactivate', $preset), ['record_version' => 1, 'confirmed' => true])->assertForbidden();
    $user->forceFill(['active_session_hash' => hash('sha256', 'module-test-session'), 'active_session_expires_at' => now()->addHour()])->save();
    expect(fn () => app(GamePresets::class)->save($user, 'module-test-session', presetData()))->toThrow(AuthorizationException::class);
    expect(fn () => app(GamePresets::class)->inactivate($user, 'module-test-session', $preset, 1, true))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('game_preset_revisions', 1);
})->with([Role::Learner, Role::Contributor, Role::Instructor, Role::Moderator]);

test('G08 configuration rejects unsupported mechanics conflicting choices and injected privilege or rewards', function (array $override, string $field) {
    $admin = User::factory()->create(['account_role' => Role::Administrator]);
    moduleSignIn($this, $admin);
    $this->postJson(route('game-presets.store'), presetData($override))->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('game_presets', 0);
})->with([
    [['basis' => 'javascript'], 'basis'], [['title' => str_repeat('x', 101)], 'title'], [['name' => '   '], 'name'],
    [['instructions' => ''], 'instructions'], [['reward_mode' => 'XP'], 'reward_mode'], [['reward_points' => 1000], 'reward_points'],
    [['instance' => ['source' => 'bad()']], 'instance'], [['status' => 'Inactive'], 'status'], [['created_by' => 999], 'created_by'],
    [['basis' => 'quiz', 'choice_a' => 'Same', 'choice_b' => 'Same'], 'choice_a'], [['basis' => 'quiz', 'answer' => 'c'], 'answer'],
]);

test('G08 normalized unique names remain reserved including inactive presets', function () {
    $admin = moduleAccount(Role::Administrator);
    $preset = presetFixture();
    app(GamePresets::class)->inactivate($admin, 'module-test-session', $preset, 1, true);
    expect(fn () => app(GamePresets::class)->save($admin, 'module-test-session', presetData(['name' => ' PICNIC TEMPLATE '])))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('game_presets', 1);
    $this->assertDatabaseCount('game_preset_revisions', 1);
});

test('G09 update creates immutable versions and rejects stale confirmations', function () {
    $admin = moduleAccount(Role::Administrator);
    $preset = presetFixture();
    $original = $preset->currentRevision;
    $updated = app(GamePresets::class)->save($admin, 'module-test-session', presetData(['basis' => 'loops', 'title' => 'New loop']), $preset);
    expect($updated->record_version)->toBe(2)->and($updated->currentRevision->number)->toBe(2)
        ->and($original->fresh()->instance['title'])->toBe('Picnic trail');
    expect(fn () => app(GamePresets::class)->save($admin, 'module-test-session', presetData(), $preset))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('game_preset_revisions', 2);
    $this->assertDatabaseHas('audit_events', ['event' => 'game_preset.updated']);
});

test('G08 G10 module snapshots remain playable after update and inactivation without reward effects', function (string $basis) {
    $admin = moduleAccount(Role::Administrator);
    $preset = presetFixture(['basis' => $basis]);
    $author = moduleAccount(Role::Instructor);
    $publisher = app(ModulePublishing::class);
    $data = moduleData(['assessment_kind' => 'preset', 'managed_preset' => $preset->current_revision_id, 'preset_title' => 'My picnic']);
    $module = $publisher->save($author, 'module-test-session', $data);
    $original = $module->latestRevision;
    $snapshot = $original->assessment;
    expect($original->game_preset_revision_id)->toBe($preset->current_revision_id)->and($snapshot['title'])->toBe('My picnic');
    $publisher->submit($author, 'module-test-session', $module, 1);
    $publisher->review(moduleAccount(Role::Moderator), 'module-test-session', $module->fresh(), 2, 'Approved', null);
    $updated = app(GamePresets::class)->save($admin, 'module-test-session', presetData(['basis' => $basis, 'title' => 'Changed prompts', 'answer' => 'b']), $preset);
    expect(fn () => $publisher->save($author, 'module-test-session', $data))->toThrow(ValidationException::class);
    app(GamePresets::class)->inactivate($admin, 'module-test-session', $updated, 2, true);
    expect($original->fresh()->assessment)->toBe($snapshot);
    expect(fn () => $publisher->save($author, 'module-test-session', array_replace($data, ['managed_preset' => $updated->current_revision_id])))->toThrow(ValidationException::class);
    $learner = moduleAccount(Role::Learner);
    $input = $basis === 'quiz' ? ['answer' => 'a'] : ['program' => ['right', 'right', 'up', 'right', 'right']];
    app(LearningProgression::class)->recordModule($learner, 'module-test-session', $module->id, $original->id, $basis === 'quiz' ? 'quiz' : 'game', $input);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseCount('learning_modules', 1);
    expect($learner->fresh()->account_role)->toBe(Role::Learner);
    moduleSignIn($this, $author);
    $this->get(route('studio.edit', $module))->assertOk()->assertSee('Saved preset revision #')->assertSee('My picnic');
    $this->get(route('studio.create'))->assertOk()->assertDontSee('Picnic template');
    $this->assertDatabaseHas('audit_events', ['event' => 'game_preset.inactivated']);
})->with(['sequences', 'quiz']);

test('G08 creator selection uses current presets and ignores forged instance and reference fields', function () {
    $preset = presetFixture();
    $author = User::factory()->create(['account_role' => Role::Instructor]);
    moduleSignIn($this, $author);
    $this->get(route('studio.create'))->assertOk()->assertSee('Picnic template');
    $this->post(route('studio.store'), moduleData(['assessment_kind' => 'preset', 'managed_preset' => $preset->current_revision_id,
        'preset_title' => 'My game', 'game_preset_revision_id' => 999, 'assessment' => ['source' => 'bad()']]))->assertRedirect();
    expect(ModuleRevision::sole()->assessment)->not->toHaveKey('source');
    expect(ModuleRevision::sole()->game_preset_revision_id)->toBe($preset->current_revision_id);
    $this->postJson(route('studio.store'), moduleData(['assessment_kind' => 'preset', 'managed_preset' => 999, 'preset_title' => 'Missing']))->assertNotFound();
});

test('G10 confirmation cancellation stale form and inactive modification preserve history', function () {
    $admin = User::factory()->create(['account_role' => Role::Administrator]);
    $preset = presetFixture();
    moduleSignIn($this, $admin);
    $this->postJson(route('game-presets.inactivate', $preset), ['record_version' => 1])->assertUnprocessable();
    $this->postJson(route('game-presets.inactivate', $preset), ['record_version' => 99, 'confirmed' => true])->assertUnprocessable();
    $this->post(route('game-presets.inactivate', $preset), ['record_version' => 1, 'confirmed' => true])->assertRedirect();
    $this->putJson(route('game-presets.update', $preset), presetData(['record_version' => 2]))->assertForbidden();
    $this->get(route('game-presets.edit', $preset))->assertOk()->assertSee('Inactive');
    $this->assertDatabaseCount('game_preset_revisions', 1);
});

test('G08 audit failure rolls back creation update and inactivation', function (string $action) {
    $admin = moduleAccount(Role::Administrator);
    $preset = $action === 'create' ? null : presetFixture();
    $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Audit unavailable'));
    expect(function () use ($admin, $preset, $action) {
        if ($action === 'inactivate') {
            app(GamePresets::class)->inactivate($admin, 'module-test-session', $preset, 1, true);
        } else {
            app(GamePresets::class)->save($admin, 'module-test-session', presetData(['title' => 'Changed']), $preset);
        }
    })->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('game_presets', $preset === null ? 0 : 1);
    $this->assertDatabaseCount('game_preset_revisions', $preset === null ? 0 : 1);
    if ($preset !== null) {
        expect($preset->fresh()->status)->toBe('Active')->and($preset->fresh()->record_version)->toBe(1);
    }
})->with(['create', 'update', 'inactivate']);

test('G08 expired suspended and unverified administrators cannot mutate presets', function (string $restriction) {
    $admin = moduleAccount(Role::Administrator);
    $admin->forceFill(match ($restriction) {
        'expired' => ['active_session_expires_at' => now()->subMinute()],
        'suspended' => ['account_status' => AccountStatus::Suspended],
        default => ['email_verified_at' => null, 'account_status' => AccountStatus::Unverified],
    })->save();
    expect(fn () => app(GamePresets::class)->save($admin, 'module-test-session', presetData()))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('game_presets', 0);
})->with(['expired', 'suspended', 'unverified']);

test('G08 guest csrf and missing resource protections apply', function () {
    $this->get(route('game-presets.index'))->assertRedirect(route('login'));
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Administrator]));
    $this->get(route('game-presets.edit', 999))->assertNotFound();
    $this->app->detectEnvironment(fn () => 'local');
    $this->postJson(route('game-presets.store'), presetData())->assertStatus(419);
});

test('G09 PostgreSQL prevents overwriting preset revisions or conflicting cross-preset pointers', function (string $violation) {
    $first = presetFixture();
    $second = presetFixture(['name' => 'Second template']);
    expect(fn () => match ($violation) {
        'update' => DB::table('game_preset_revisions')->where('id', $first->current_revision_id)->update(['reward_mode' => 'XP']),
        'delete' => DB::table('game_preset_revisions')->where('id', $first->current_revision_id)->delete(),
        'pointer' => $first->update(['current_revision_id' => $second->current_revision_id]),
        'reward' => GamePresetRevision::create(['preset_id' => $first->id, 'number' => 2, 'created_by' => $first->created_by, 'instance' => $first->currentRevision->instance, 'reward_mode' => 'XP']),
        'missing' => GamePresetRevision::create(['preset_id' => $first->id, 'number' => 2, 'created_by' => $first->created_by, 'instance' => []]),
    })->toThrow(QueryException::class);
})->with(['update', 'delete', 'pointer', 'reward', 'missing']);

test('G10 migration rollback refuses to erase preset history', function () {
    presetFixture();
    $migration = require database_path('migrations/2026_10_03_000023_create_game_presets.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
});
