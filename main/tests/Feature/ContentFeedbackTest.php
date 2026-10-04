<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Services\Account\AccountDeletion;
use App\Services\Administration\AuditRecorder;
use App\Services\Administration\ContentModeration;
use App\Services\Content\CourseLearning;
use App\Services\Engagement\ContentFeedback;
use App\Services\Gamification\LearningProgression;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('B10 validated module wins and Passed evaluations qualify without invented visit history', function () {
    $user = moduleAccount(Role::Learner);
    $module = moduleFixture(true);
    app(LearningProgression::class)->recordModule($user, 'module-test-session', $module->id, $module->published_revision_id, 'game', courseWin());
    $service = app(ContentFeedback::class);
    expect($service->read($user, 'module-test-session', 'module', $module->id)['eligible'])->toBeTrue();
    readyJudge();
    $challenge = challengeFixture(true);
    $submission = submitAttempt($challenge, $user);
    expect($service->read($user, 'module-test-session', 'challenge', $challenge->id)['eligible'])->toBeFalse();
    evaluateAttempt($submission);
    expect($submission->fresh()->status)->toBe('Passed');
    expect($service->read($user, 'module-test-session', 'challenge', $challenge->id)['eligible'])->toBeTrue();
    $this->assertDatabaseCount('content_accesses', 0);
});

test('B10 weekly delivery qualifies its source challenge without revealing hidden tests', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-04 00:00:00', 'Asia/Manila')->utc());
    $event = weeklyFixture();
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('weekly-events.show', $event))->assertOk()->assertSee('A little encouragement?')->assertDontSee('HIDDEN_INPUT_SECRET');
    $this->postJson(route('content-reactions.store', ['challenge', $event->challenge_id]), ['record_version' => 0, 'reaction' => 'Like'])->assertOk();
    $this->assertDatabaseCount('content_accesses', 1);
});

test('B10 feedback requires csrf and rate limiting and rollback preserves access history', function () {
    $item = moduleFixture(true);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('modules.show', $item))->assertOk();
    foreach (range(1, 20) as $attempt) {
        $this->postJson(route('content-reactions.store', ['module', $item->id]), [])->assertUnprocessable();
    }
    $this->postJson(route('content-reactions.store', ['module', $item->id]), [])->assertTooManyRequests();
    $this->app->detectEnvironment(fn () => 'local');
    $this->post(route('content-reactions.store', ['module', $item->id]), ['record_version' => 0, 'reaction' => 'Like'])->assertStatus(419);
    $migration = require database_path('migrations/2026_10_03_000025_create_content_feedback.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('content_accesses', 1);
});

test('B10 one reaction replaces removes and retries without duplicate counts or lost versions', function (string $kind) {
    $item = moderationFixture($kind);
    $user = moduleAccount(Role::Learner);
    if ($kind === 'course') {
        joinCourse($item, $user);
    }
    $service = app(ContentFeedback::class);
    expect($service->read($user, 'module-test-session', $kind, $item->id)['eligible'])->toBeFalse();
    $service->read($user, 'module-test-session', $kind, $item->id, true);
    $first = $service->change($user, 'module-test-session', $kind, $item->id, 0, 'Like');
    expect($first['counts'])->toBe(['Like' => 1, 'Helpful' => 0, 'Favorite' => 0])->and($first['record_version'])->toBe(1);
    expect($service->change($user, 'module-test-session', $kind, $item->id, 0, 'Like'))->toBe($first);
    expect(fn () => $service->change($user, 'module-test-session', $kind, $item->id, 0, 'Favorite'))->toThrow(ValidationException::class);
    $changed = $service->change($user, 'module-test-session', $kind, $item->id, 1, 'Helpful');
    expect($changed['counts'])->toBe(['Like' => 0, 'Helpful' => 1, 'Favorite' => 0]);
    $removed = $service->change($user, 'module-test-session', $kind, $item->id, 2, null);
    expect($removed['record_version'])->toBe(3)->and(array_sum($removed['counts']))->toBe(0);
    expect($service->change($user, 'module-test-session', $kind, $item->id, 2, null))->toBe($removed);
    expect(fn () => $service->change($user, 'module-test-session', $kind, $item->id, 0, 'Like'))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('content_reactions', 1);
    $this->assertDatabaseCount('learning_activity_days', 0);
})->with(['module', 'course', 'challenge']);

test('B10 participant roles may react after an authorized content opening', function (Role $role, string $kind) {
    $item = moderationFixture($kind);
    $user = moduleAccount($role);
    if ($kind === 'course') {
        joinCourse($item, $user);
    }
    moduleSignIn($this, $user);
    $route = match ($kind) {
        'module' => 'modules.show', 'course' => 'course-learning.show', 'challenge' => 'challenges.show'
    };
    $this->postJson(route('content-reactions.store', [$kind, $item->id]), ['record_version' => 0, 'reaction' => 'Like'])->assertForbidden();
    $this->get(route($route, $item))->assertOk()->assertSee('A little encouragement?')->assertHeader('Cache-Control', 'no-store, private');
    $this->postJson(route('content-reactions.store', [$kind, $item->id]), ['record_version' => 0, 'reaction' => 'Favorite'])
        ->assertOk()->assertJsonPath('reaction', 'Favorite')->assertJsonPath('counts.Favorite', 1)->assertHeader('Cache-Control', 'no-store, private');
    $this->post(route('content-reactions.store', [$kind, $item->id]), ['record_version' => 1, 'reaction' => ''])
        ->assertRedirect(route($route, $item))->assertSessionHas('reaction_status');
})->with([Role::Learner, Role::Contributor, Role::Instructor])->with(['module', 'course', 'challenge']);

test('B10 guest catalog staff preview and another account cannot create participation proof', function () {
    $item = moduleFixture(true);
    $this->postJson(route('content-reactions.store', ['module', $item->id]), ['record_version' => 0, 'reaction' => 'Like'])->assertUnauthorized();
    foreach ([Role::Moderator, Role::Administrator] as $role) {
        moduleSignIn($this, moduleAccount($role));
        $this->get(route('modules.show', $item))->assertOk();
        $this->postJson(route('content-reactions.store', ['module', $item->id]), ['record_version' => 0, 'reaction' => 'Like'])->assertForbidden();
    }
    $this->assertDatabaseCount('content_accesses', 0);
    $first = moduleAccount(Role::Learner);
    app(ContentFeedback::class)->read($first, 'module-test-session', 'module', $item->id, true);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('learning.catalog'))->assertOk();
    $this->postJson(route('content-reactions.store', ['module', $item->id]), ['record_version' => 0, 'reaction' => 'Like', 'opened' => true])->assertUnprocessable();
    $this->postJson(route('content-reactions.store', ['module', $item->id]), ['record_version' => 0, 'reaction' => 'Like'])->assertForbidden();
    $this->assertDatabaseCount('content_reactions', 0);
});

test('B10 course requires enrollment and trusted pinned lesson visits qualify existing progress', function () {
    $course = courseFixture(true);
    $user = moduleAccount(Role::Learner);
    $service = app(ContentFeedback::class);
    expect($service->read($user, 'module-test-session', 'course', $course->id, true)['eligible'])->toBeFalse();
    $this->assertDatabaseCount('content_accesses', 0);
    joinCourse($course, $user);
    $slot = $course->publishedRevision->modules->sole();
    app(CourseLearning::class)->lesson($user, 'module-test-session', $course->id, $slot->id);
    foreach (['course' => $course->id, 'module' => $slot->module_id] as $kind => $id) {
        expect($service->read($user, 'module-test-session', $kind, $id)['eligible'])->toBeTrue();
        $service->change($user, 'module-test-session', $kind, $id, 0, 'Helpful');
    }
    $this->assertDatabaseCount('content_accesses', 0);
    $course->forceFill(['status' => 'Archived'])->save();
    expect($service->change($user, 'module-test-session', 'course', $course->id, 1, 'Like')['reaction'])->toBe('Like');
});

test('B10 current lifecycle and staff withdrawal override earlier participation proof', function (string $kind, string $state) {
    $item = moderationFixture($kind);
    $user = moduleAccount(Role::Learner);
    if ($kind === 'course') {
        joinCourse($item, $user);
    }
    app(ContentFeedback::class)->read($user, 'module-test-session', $kind, $item->id, true);
    if ($state === 'Withdrawn') {
        app(ContentModeration::class)->change(moduleAccount(Role::Moderator), 'module-test-session', $kind, $item->id, moderationData($item));
    } else {
        $item->forceFill($state === 'Draft' ? ['status' => $state, 'published_revision_id' => null] : ['status' => $state])->save();
    }
    moduleSignIn($this, $user);
    $this->postJson(route('content-reactions.store', [$kind, $item->id]), ['record_version' => 0, 'reaction' => 'Like'])->assertNotFound();
    $this->assertDatabaseCount('content_reactions', 0);
})->with([['module', 'Archived'], ['challenge', 'Archived'], ['module', 'Draft'], ['module', 'Withdrawn'], ['course', 'Withdrawn'], ['challenge', 'Withdrawn']]);

test('B10 invalid reaction versions and forged ownership are rejected', function (array $data, string $field) {
    $item = moduleFixture(true);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('modules.show', $item))->assertOk();
    $this->postJson(route('content-reactions.store', ['module', $item->id]), $data)->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('content_reactions', 0);
})->with([
    [['record_version' => 0, 'reaction' => '5 stars'], 'reaction'],
    [['record_version' => -1, 'reaction' => 'Like'], 'record_version'],
    [['record_version' => 0], 'reaction'],
    [['record_version' => 0, 'reaction' => 'Like', 'user_id' => 999], 'user_id'],
]);

test('B10 revoked sessions and suspended accounts cannot mutate accepted reactions', function () {
    $item = moduleFixture(true);
    $user = moduleAccount(Role::Learner);
    $service = app(ContentFeedback::class);
    $service->read($user, 'module-test-session', 'module', $item->id, true);
    $service->change($user, 'module-test-session', 'module', $item->id, 0, 'Like');
    $user->forceFill(['account_status' => AccountStatus::Suspended, 'active_session_hash' => null, 'active_session_expires_at' => null])->save();
    expect(fn () => $service->change($user, 'module-test-session', 'module', $item->id, 1, null))->toThrow(AuthorizationException::class);
    expect(DB::table('content_reactions')->sole()->reaction)->toBe('Like');
});

test('B10 PostgreSQL enforces typed targets supported choices positive versions and one user reaction', function (string $invalid) {
    $item = moduleFixture(true);
    $user = moduleAccount(Role::Learner);
    $row = ['user_id' => $user->id, 'kind' => 'module', 'module_id' => $item->id, 'reaction' => 'Like', 'record_version' => 1];
    if ($invalid === 'duplicate') {
        DB::table('content_reactions')->insert($row);
    }
    $row = array_replace($row, match ($invalid) {
        'target' => ['kind' => 'course'], 'choice' => ['reaction' => 'Stars'], 'version' => ['record_version' => 0], default => []
    });
    expect(fn () => DB::table('content_reactions')->insert($row))->toThrow(QueryException::class);
})->with(['target', 'choice', 'version', 'duplicate']);

test('B10 deletion atomically removes feedback and access proof without deleting creator content', function () {
    $item = moduleFixture(true);
    $user = moduleAccount(Role::Learner);
    $service = app(ContentFeedback::class);
    $service->read($user, 'module-test-session', 'module', $item->id, true);
    $service->change($user, 'module-test-session', 'module', $item->id, 0, 'Like');
    $this->mock(AuditRecorder::class, fn ($mock) => $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('Unavailable')));
    expect(fn () => app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user)))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('content_reactions', 1);
    $this->assertDatabaseCount('content_accesses', 1);
    $this->app->forgetInstance(AuditRecorder::class);
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    $this->assertDatabaseCount('content_reactions', 0);
    $this->assertDatabaseCount('content_accesses', 0);
    $this->assertDatabaseCount('learning_modules', 1);
});
