<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Administration\ContentModeration;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Challenges\ChallengeSubmissions;
use App\Services\Content\CourseLearning;
use App\Services\Content\CoursePublishing;
use App\Services\Content\ModulePublishing;
use App\Services\Gamification\LearningProgression;
use App\Services\Gamification\WeeklyEvents;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function moderationFixture(string $kind, bool $published = true): mixed
{
    return match ($kind) {
        'module' => moduleFixture($published), 'course' => courseFixture($published), 'challenge' => challengeFixture($published)
    };
}

function moderationData($item, array $overrides = []): array
{
    return array_replace(['action' => 'Withdrawn', 'record_version' => $item->fresh()->record_version, 'confirmed' => true, 'flagged' => true], $overrides);
}

function staffWithdraw(string $kind, $item, ?User $staff = null): User
{
    $staff ??= moduleAccount(Role::Moderator);
    app(ContentModeration::class)->change($staff, 'module-test-session', $kind, $item->id, moderationData($item));

    return $staff;
}

test('G06 staff withdraw and restore each content kind while preserving lifecycle revisions and owner', function (string $kind, string $lifecycle) {
    $item = moderationFixture($kind);
    $item->forceFill(['status' => $lifecycle])->save();
    $original = $item->only(['created_by', 'status', 'published_revision_id']);
    $staff = moduleAccount(Role::Moderator);
    moduleSignIn($this, $staff);
    $this->get(route('content-moderation.show', [$kind, $item->id]))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->post(route('content-moderation.change', [$kind, $item->id]), moderationData($item))->assertRedirect();
    expect($item->fresh()->isWithdrawn())->toBeTrue()->and($item->fresh()->only(array_keys($original)))->toEqual($original);
    $restore = moderationData($item, ['action' => 'Restored']);
    unset($restore['flagged']);
    $this->post(route('content-moderation.change', [$kind, $item->id]), $restore)->assertRedirect()->assertSessionHasNoErrors();
    expect($item->fresh()->isWithdrawn())->toBeFalse()->and($item->fresh()->only(array_keys($original)))->toEqual($original);
    expect(DB::table('content_moderation_actions')->where('kind', $kind)->count())->toBe(2);
    expect(DB::table('audit_events')->where('subject_type', 'content_moderation_action')->count())->toBe(2);
    expect(DB::table('notifications')->where('type', 'content.moderated')->count())->toBe(2);
})->with([['module', 'Published'], ['module', 'Archived'], ['course', 'Published'], ['course', 'Archived'], ['challenge', 'Published'], ['challenge', 'Archived']]);

test('G06 participant roles cannot browse withdraw or restore content', function (Role $role) {
    $item = moduleFixture(true);
    $actor = moduleAccount($role);
    moduleSignIn($this, $actor);
    $this->get(route('content-moderation.index'))->assertForbidden();
    $this->get(route('content-moderation.show', ['module', $item->id]))->assertForbidden();
    $this->post(route('content-moderation.change', ['module', $item->id]), moderationData($item))->assertForbidden();
    expect(fn () => app(ContentModeration::class)->change($actor, session()->getId(), 'module', $item->id, moderationData($item)))->toThrow(AuthorizationException::class);
})->with([Role::Learner, Role::Contributor, Role::Instructor]);

test('G06 self review and Draft Deleted content cannot be moderated and fresh restricted staff fail', function () {
    $staff = moduleAccount(Role::Moderator);
    $item = moduleFixture(true);
    $item->forceFill(['created_by' => $staff->id])->save();
    $service = app(ContentModeration::class);
    expect(fn () => $service->change($staff, 'module-test-session', 'module', $item->id, moderationData($item)))->toThrow(AuthorizationException::class);
    $item = moduleFixture(false);
    foreach (['Draft', 'Deleted'] as $status) {
        if ($status === 'Deleted') {
            $item = moduleFixture(true);
        }
        $item->forceFill(['status' => $status])->save();
        expect(fn () => $service->change($staff, 'module-test-session', 'module', $item->id, moderationData($item)))->toThrow(AuthorizationException::class);
    }
    $item = moduleFixture(true);
    $staff->forceFill(['active_session_expires_at' => now()->subSecond()])->save();
    expect(fn () => $service->change($staff, 'module-test-session', 'module', $item->id, moderationData($item)))->toThrow(AuthorizationException::class);
    $staff->forceFill(['active_session_expires_at' => now()->addHour(), 'account_status' => AccountStatus::Suspended])->save();
    expect(fn () => $service->change($staff, 'module-test-session', 'module', $item->id, moderationData($item)))->toThrow(AuthorizationException::class);
});

test('G06 validates flags confirmations stale requests and server owned fields', function (array $overrides, string $field) {
    $item = moduleFixture(true);
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $this->post(route('content-moderation.change', ['module', $item->id]), moderationData($item, $overrides))->assertSessionHasErrors($field);
    expect($item->fresh()->isWithdrawn())->toBeFalse();
    $this->assertDatabaseCount('content_moderation_actions', 0);
})->with([
    [['confirmed' => false], 'confirmed'], [['flagged' => false], 'flagged'], [['record_version' => 999], 'record_version'],
    [['action' => 'Restored'], 'record_version'], [['action' => 'Deleted'], 'action'], [['status' => 'Archived'], 'status'],
    [['staff_withdrawn_at' => '2026-10-03'], 'staff_withdrawn_at'], [['created_by' => 999], 'created_by'], [['published_revision_id' => 999], 'published_revision_id'],
]);

test('G06 withdrawal hides standalone modules and rejects stale completion without granting a streak', function () {
    $module = moduleFixture(true);
    $staff = staffWithdraw('module', $module);
    $learner = moduleAccount(Role::Learner);
    moduleSignIn($this, $learner);
    $this->get(route('learning.catalog'))->assertDontSee($module->publishedRevision->title);
    $this->get(route('modules.show', $module))->assertNotFound();
    $this->postJson(route('modules.game', [$module, $module->published_revision_id]), courseWin())->assertStatus(409);
    expect(fn () => app(LearningProgression::class)->recordApprovedModule($learner, session()->getId(), $module->id, $module->published_revision_id, 'game', courseWin()))->toThrow(HttpException::class);
    $this->assertDatabaseCount('learning_activity_days', 0);
    app(ContentModeration::class)->change($staff, 'module-test-session', 'module', $module->id, moderationData($module, ['action' => 'Restored']));
    $this->get(route('modules.show', $module))->assertOk();
});

test('G06 course withdrawal blocks existing and new enrollment pinned lessons and completion but restores retained progress', function (string $lifecycle) {
    $course = courseFixture(true);
    $learner = moduleAccount(Role::Learner);
    $enrollment = joinCourse($course, $learner);
    $slot = $course->publishedRevision->modules->sole();
    app(CourseLearning::class)->lesson($learner, 'module-test-session', $course->id, $slot->id);
    $course->forceFill(['status' => $lifecycle])->save();
    $staff = staffWithdraw('course', $course);
    moduleSignIn($this, $learner);
    $this->get(route('course-learning.show', $course))->assertNotFound();
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertNotFound();
    $this->postJson(route('course-learning.game', [$course, $slot->id]), courseWin())->assertNotFound();
    $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id])->assertNotFound();
    $this->get(route('course-learning.mine'))->assertOk()->assertSee('Journey temporarily unavailable')->assertDontSee(route('course-learning.show', $course));
    $this->assertDatabaseCount('course_enrollments', 1);
    $this->assertDatabaseCount('course_module_progress', 1);
    $this->assertDatabaseCount('learning_activity_days', 0);
    expect(fn () => joinCourse($course))->toThrow(HttpException::class);
    app(ContentModeration::class)->change($staff, 'module-test-session', 'course', $course->id, moderationData($course, ['action' => 'Restored']));
    $this->get(route('course-learning.show', $course))->assertOk();
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertOk();
    expect($enrollment->fresh()->course_revision_id)->toBe($course->published_revision_id);
    if ($lifecycle === 'Archived') {
        expect(fn () => joinCourse($course))->toThrow(HttpException::class);
    }
})->with(['Published', 'Archived']);

test('G06 module withdrawal applies to pinned lessons and blocks course composition and new enrollment', function () {
    $course = courseFixture(true);
    $slot = $course->publishedRevision->modules->sole();
    $module = $slot->module;
    $learner = moduleAccount(Role::Learner);
    joinCourse($course, $learner);
    staffWithdraw('module', $module);
    moduleSignIn($this, $learner);
    $this->get(route('course-learning.show', $course))->assertOk()->assertSee('Adventure unavailable')->assertDontSee(route('course-learning.lesson', [$course, $slot->id]));
    $this->get(route('course-learning.lesson', [$course, $slot->id]))->assertNotFound();
    expect(fn () => joinCourse($course))->toThrow(ValidationException::class);
    $owner = User::find($course->created_by);
    expect(fn () => app(CoursePublishing::class)->save($owner, 'module-test-session', courseData([$module->id])))->toThrow(ValidationException::class);
});

test('G06 withdrawal blocks new coding attempts but preserves durable retries and committed evaluation', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $learner = moduleAccount(Role::Learner);
    $data = attemptData($challenge);
    $attempt = submitAttempt($challenge, $learner, $data);
    staffWithdraw('challenge', $challenge);
    moduleSignIn($this, $learner);
    $this->get(route('challenges.catalog'))->assertDontSee($challenge->publishedRevision->title);
    $this->get(route('challenges.show', $challenge))->assertNotFound();
    $this->post(route('challenge-attempts.store', $challenge), attemptData($challenge))->assertSessionHasErrors('revision_id');
    expect(app(ChallengeSubmissions::class)->submit($learner, session()->getId(), $challenge, $data)->id)->toBe($attempt->id);
    evaluateAttempt($attempt);
    expect($attempt->fresh()->status)->toBe('Passed');
    $this->get(route('challenge-attempts.show', $attempt))->assertOk();
    $this->getJson(route('challenge-attempts.status', $attempt))->assertOk()->assertJsonPath('status', 'Passed');
    $this->assertDatabaseCount('challenge_submissions', 1);
});

test('G06 creator edits republication and archival cannot clear staff withdrawal', function (string $kind) {
    $item = moderationFixture($kind);
    $staff = staffWithdraw($kind, $item);
    $owner = User::find($item->created_by);
    $publisher = app(match ($kind) {
        'module' => ModulePublishing::class, 'course' => CoursePublishing::class, 'challenge' => ChallengePublishing::class
    });
    $data = match ($kind) {
        'module' => moduleData(), 'course' => courseData($item->publishedRevision->modules->pluck('module_id')->all()), 'challenge' => challengeData()
    };
    $data = array_replace($data, ['record_version' => $item->fresh()->record_version, 'staff_withdrawn_at' => null]);
    $publisher->save($owner, 'module-test-session', $data, $item);
    $publisher->submit($owner, 'module-test-session', $item, $item->fresh()->record_version);
    $publisher->review($staff, 'module-test-session', $item, $item->fresh()->record_version, 'Approved', null);
    expect($item->fresh()->isWithdrawn())->toBeTrue();
    $publisher->archive($owner, 'module-test-session', $item, $item->fresh()->record_version);
    expect($item->fresh()->isWithdrawn())->toBeTrue()->and($item->fresh()->status)->toBe('Archived');
})->with(['module', 'course', 'challenge']);

test('G06 temporary weekly withdrawal blocks play and configuration but preserves the pinned event and committed evaluations', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-04 00:00:00', 'Asia/Manila')->utc());
    readyJudge();
    $challenge = challengeFixture(true);
    $event = weeklyFixture($challenge);
    $learner = moduleAccount(Role::Learner);
    $attempt = submitWeekly($event, $learner);
    $staff = staffWithdraw('challenge', $challenge);
    app(WeeklyEvents::class)->synchronize();
    expect($event->fresh()->status)->toBe('Active')->and($event->fresh()->revision_id)->toBe($challenge->published_revision_id);
    expect(fn () => submitWeekly($event, $learner))->toThrow(ValidationException::class);
    expect(fn () => app(WeeklyEvents::class)->configure($staff, 'module-test-session', weeklyData($challenge, ['week' => '2026-10-11'])))->toThrow(ValidationException::class);
    moduleSignIn($this, $learner);
    $this->get(route('weekly-events.show', $event))->assertNotFound();
    evaluateAttempt($attempt);
    expect($attempt->fresh()->status)->toBe('Passed');
    app(ContentModeration::class)->change($staff, 'module-test-session', 'challenge', $challenge->id, moderationData($challenge, ['action' => 'Restored']));
    $this->get(route('weekly-events.show', $event))->assertOk();
    expect($event->fresh()->status)->toBe('Active');
});

test('G06 audit or notification storage failures roll back withdrawal version and history', function (bool $noticeFailure) {
    $item = moduleFixture(true);
    $version = $item->fresh()->record_version;
    $staff = moduleAccount(Role::Moderator);
    if ($noticeFailure) {
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Test notification failure.'));
    } else {
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test audit failure.'));
    }
    expect(fn () => staffWithdraw('module', $item, $staff))->toThrow(RuntimeException::class);
    expect($item->fresh()->isWithdrawn())->toBeFalse()->and($item->fresh()->record_version)->toBe($version);
    $this->assertDatabaseCount('content_moderation_actions', 0);
})->with([false, true]);

test('G06 PostgreSQL constrains content references and protects withdrawal history on rollback', function () {
    $module = moduleFixture(true);
    staffWithdraw('module', $module);
    expect(fn () => DB::transaction(fn () => DB::table('content_moderation_actions')->update(['kind' => 'course'])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('learning_modules')->where('id', $module->id)->update(['status' => 'Draft'])))->toThrow(QueryException::class);
    $migration = require database_path('migrations/2026_10_03_000022_add_staff_content_withdrawals.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
});

test('G06 staff routes enforce authentication missing objects CSRF and a private creator notice', function () {
    $module = moduleFixture(true);
    $this->get(route('content-moderation.index'))->assertRedirect(route('login'));
    moduleSignIn($this, moduleAccount(Role::Moderator));
    $this->get(route('content-moderation.show', ['module', 999999]))->assertNotFound();
    $this->get('/manage/content/unknown/1')->assertNotFound();
    $this->post(route('content-moderation.change', ['module', $module->id]), moderationData($module))->assertRedirect();
    moduleSignIn($this, User::find($module->created_by));
    $this->get(route('notifications.index'))->assertOk()->assertSee('Withdrawn')->assertSee('staff moderation');
    $this->app->detectEnvironment(fn () => 'local');
    $this->post(route('content-moderation.change', ['module', $module->id]), moderationData($module))->assertStatus(419);
});
