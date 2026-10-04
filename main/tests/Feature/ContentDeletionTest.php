<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Account\AccountDeletion;
use App\Services\Administration\AuditRecorder;
use App\Services\Administration\ContentModeration;
use App\Services\Challenges\ChallengeDeletion;
use App\Services\Content\ContentDeletion;
use App\Services\Engagement\ContentFeedback;
use App\Services\Gamification\LearningProgression;
use App\Services\Gamification\WeeklyEvents;
use App\Services\Publishing\OwnedContentDeletion;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('C07 prior weekly planning audits preserve the former challenge after future event replacement', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-04 00:00:00', 'Asia/Manila')->utc());
    $content = challengeFixture(true);
    $event = weeklyFixture($content, ['week' => '2026-10-11']);
    $replacement = challengeFixture(true);
    app(WeeklyEvents::class)->configure(moduleAccount(Role::Moderator), 'module-test-session', weeklyData($replacement, ['week' => '2026-10-11', 'record_version' => $event->record_version]));
    expect(DB::table('weekly_events')->where('challenge_id', $content->id)->count())->toBe(0);
    $owner = User::find($content->created_by);
    expect(deletionService('challenge')->confirmation($owner, 'module-test-session', 'challenge', $content->id)['reasons'])->toContain('Weekly planning audit history');
    expect(fn () => deletionService('challenge')->delete($owner, 'module-test-session', 'challenge', $content->id, $content->record_version, true))->toThrow(ValidationException::class);
});

test('D09 C07 learner audits retain dependencies after account privacy erasure', function (string $kind) {
    $content = moderationFixture($kind);
    $user = moduleAccount(Role::Learner);
    if ($kind === 'course') {
        joinCourse($content, $user);
    } else {
        readyJudge();
        $submission = submitAttempt($content, $user);
        evaluateAttempt($submission, 4);
    }
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    $this->assertDatabaseCount($kind === 'course' ? 'course_enrollments' : 'challenge_participations', 0);
    $owner = User::find($content->created_by);
    expect(deletionService($kind)->confirmation($owner, 'module-test-session', $kind, $content->id)['reasons'])->toContain(
        $kind === 'course' ? 'Learner enrollment audit history' : 'Learner submission audit history');
    expect(fn () => deletionService($kind)->delete($owner, 'module-test-session', $kind, $content->id, $content->record_version, true))->toThrow(ValidationException::class);
})->with(['course', 'challenge']);

test('D04 foreign keys reject an unexpected retained dependency and roll back the entire purge', function () {
    $content = moduleFixture(true);
    $owner = User::find($content->created_by);
    $service = new class extends ContentDeletion
    {
        protected function removeDefinition(string $kind, Model $content): void
        {
            parent::removeDefinition($kind, $content);
            DB::table('content_accesses')->insert(['user_id' => $content->created_by, 'kind' => 'module', 'module_id' => $content->id, 'first_opened_at' => now()]);
        }
    };
    expect(fn () => $service->delete($owner, 'module-test-session', 'module', $content->id, $content->record_version, true))->toThrow(ValidationException::class);
    expect($content->fresh()->status)->toBe('Published')->and($content->fresh()->publishedRevision)->not->toBeNull();
    $this->assertDatabaseCount('content_accesses', 0);
    expect(DB::table('audit_events')->where('event', 'module.deleted')->count())->toBe(0);
});

function deletionService(string $kind): OwnedContentDeletion
{
    return app($kind === 'challenge' ? ChallengeDeletion::class : ContentDeletion::class);
}

test('D04 D09 C07 editor links render for saved definitions and empty authoring forms', function (string $kind) {
    $content = moderationFixture($kind);
    moduleSignIn($this, User::find($content->created_by));
    $studio = match ($kind) {
        'module' => 'studio', 'course' => 'courses', 'challenge' => 'challenges'
    };
    $this->get(route($studio.'.create'))->assertOk();
    $this->get(route($studio.'.edit', $content))->assertOk()->assertSee(route('content-deletion.show', [$kind, $content->id]));
    $this->post(route('content-deletion.store', [$kind, $content->id]), ['record_version' => $content->record_version, 'confirmed' => true])->assertRedirect();
    $this->get(route($studio.'.index'))->assertOk()->assertSee('Content permanently deleted.');
})->with(['module', 'course', 'challenge']);

test('D04 D09 C07 owners permanently delete unreferenced definitions and preserve audit and reusable modules', function (string $kind, string $status) {
    $content = moderationFixture($kind, $status !== 'Draft');
    if ($status === 'Archived') {
        $content->forceFill(['status' => 'Archived'])->save();
    }
    $owner = User::find($content->created_by);
    $table = $content->getTable();
    $revisionTable = match ($kind) {
        'module' => 'module_revisions', 'course' => 'course_revisions', 'challenge' => 'coding_challenge_revisions'
    };
    $revisionIds = DB::table($revisionTable)->where($kind.'_id', $content->id)->pluck('id');
    moduleSignIn($this, $owner);
    $this->get(route('content-deletion.show', [$kind, $content->id]))->assertOk()->assertSee('It cannot be undone')->assertHeader('Cache-Control', 'no-store, private');
    $this->post(route('content-deletion.store', [$kind, $content->id]), ['record_version' => $content->record_version, 'confirmed' => true])->assertRedirect();
    $this->assertDatabaseMissing($table, ['id' => $content->id]);
    expect(DB::table($revisionTable)->whereIn('id', $revisionIds)->count())->toBe(0);
    $audit = DB::table('audit_events')->where('event', $kind.'.deleted')->sole();
    expect($audit->actor_id)->toBe($owner->id)->and($audit->subject_id)->toBe((string) $content->id)->and($audit->context)->not->toContain('source_code');
    if ($kind === 'course') {
        $this->assertDatabaseCount('learning_modules', 1);
    }
    if ($kind === 'challenge') {
        $this->assertDatabaseCount('challenge_test_cases', 0);
    }
    $this->get(route('content-deletion.show', [$kind, $content->id]))->assertNotFound();
    $this->postJson(route('content-deletion.store', [$kind, $content->id]), ['record_version' => $content->record_version, 'confirmed' => true])->assertNotFound();
    expect(DB::table('audit_events')->where('event', $kind.'.deleted')->count())->toBe(1);
})->with(['module', 'course', 'challenge'])->with(['Draft', 'Published', 'Archived']);

test('D04 D09 C07 guests other owners and staff cannot delete creator definitions', function (string $kind) {
    $content = moderationFixture($kind);
    $this->get(route('content-deletion.show', [$kind, $content->id]))->assertRedirect(route('login'));
    foreach ([Role::Learner, Role::Contributor, Role::Instructor, Role::Moderator, Role::Administrator] as $role) {
        moduleSignIn($this, moduleAccount($role));
        $this->get(route('content-deletion.show', [$kind, $content->id]))->assertForbidden();
        $this->postJson(route('content-deletion.store', [$kind, $content->id]), ['record_version' => $content->record_version, 'confirmed' => true])->assertForbidden();
    }
    $this->assertDatabaseHas($content->getTable(), ['id' => $content->id]);
})->with(['module', 'course', 'challenge']);

test('D04 D09 C07 retained openings reactions and restored moderation block deletion', function (string $kind, string $dependency) {
    $content = moderationFixture($kind);
    $owner = User::find($content->created_by);
    if ($dependency === 'moderation') {
        $staff = moduleAccount(Role::Moderator);
        app(ContentModeration::class)->change($staff, 'module-test-session', $kind, $content->id, moderationData($content));
        app(ContentModeration::class)->change($staff, 'module-test-session', $kind, $content->id, moderationData($content->fresh(), ['action' => 'Restored']));
        $content->refresh();
    } else {
        $user = moduleAccount(Role::Learner);
        if ($kind === 'course') {
            joinCourse($content, $user);
        }
        app(ContentFeedback::class)->read($user, 'module-test-session', $kind, $content->id, true);
        if ($dependency === 'reaction') {
            app(ContentFeedback::class)->change($user, 'module-test-session', $kind, $content->id, 0, 'Like');
            app(ContentFeedback::class)->change($user, 'module-test-session', $kind, $content->id, 1, null);
            DB::table('content_accesses')->where('user_id', $user->id)->delete();
        }
    }
    $service = deletionService($kind);
    expect($service->confirmation($owner, 'module-test-session', $kind, $content->id)['reasons'])->not->toBeEmpty();
    expect(fn () => $service->delete($owner, 'module-test-session', $kind, $content->id, $content->record_version, true))->toThrow(ValidationException::class);
    $this->assertDatabaseHas($content->getTable(), ['id' => $content->id]);
    expect(DB::table('audit_events')->where('event', $kind.'.deleted')->count())->toBe(0);
})->with(['module', 'course', 'challenge'])->with(['opening', 'reaction', 'moderation']);

test('D04 every historic course pin and validated game win blocks module deletion', function (string $dependency) {
    $content = moduleFixture(true);
    if ($dependency === 'pin') {
        courseFixture(false, $content);
    } else {
        app(LearningProgression::class)->recordModule(moduleAccount(Role::Learner), 'module-test-session', $content->id, $content->published_revision_id, 'game', courseWin());
    }
    $owner = User::find($content->created_by);
    expect(fn () => deletionService('module')->delete($owner, 'module-test-session', 'module', $content->id, $content->record_version, true))->toThrow(ValidationException::class);
    moduleSignIn($this, $owner);
    $this->get(route('content-deletion.show', ['module', $content->id]))->assertOk()->assertSee('Archive instead')->assertDontSee('name="confirmed"', false);
})->with(['pin', 'win']);

test('D09 retained enrollment blocks course deletion even without lesson progress', function () {
    $content = courseFixture(true);
    joinCourse($content);
    $content->forceFill(['status' => 'Archived'])->save();
    expect(fn () => deletionService('course')->delete(User::find($content->created_by), 'module-test-session', 'course', $content->id, $content->record_version, true))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('course_enrollments', 1);
    $this->assertDatabaseCount('course_module_progress', 0);
});

test('C07 completed failed submissions and closed weekly events remain dependencies', function (string $dependency) {
    $content = challengeFixture(true);
    if ($dependency === 'submission') {
        readyJudge();
        $submission = submitAttempt($content, moduleAccount(Role::Learner));
        evaluateAttempt($submission, 4);
        expect($submission->fresh()->completed_at)->not->toBeNull();
    } else {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 00:00:00', 'Asia/Manila')->utc());
        $content = challengeFixture(true);
        $event = weeklyFixture($content);
        $event->forceFill(['status' => 'Ended'])->save();
    }
    expect(fn () => deletionService('challenge')->delete(User::find($content->created_by), 'module-test-session', 'challenge', $content->id, $content->record_version, true))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('coding_challenges', ['id' => $content->id]);
})->with(['submission', 'weekly']);

test('D04 D09 C07 stale unconfirmed forged and revoked deletion never removes data', function (string $kind) {
    $content = moderationFixture($kind);
    $owner = User::find($content->created_by);
    $service = deletionService($kind);
    expect(fn () => $service->delete($owner, 'module-test-session', $kind, $content->id, 99, true))->toThrow(ValidationException::class);
    expect(fn () => $service->delete($owner, 'module-test-session', $kind, $content->id, $content->record_version, false))->toThrow(ValidationException::class);
    moduleSignIn($this, $owner);
    $this->postJson(route('content-deletion.store', [$kind, $content->id]), ['record_version' => $content->record_version])->assertUnprocessable()->assertJsonValidationErrors('confirmed');
    $this->postJson(route('content-deletion.store', [$kind, $content->id]), ['record_version' => $content->record_version, 'confirmed' => true, 'force' => true])->assertUnprocessable();
    $owner->refresh()->forceFill(['account_status' => AccountStatus::Suspended, 'active_session_hash' => null, 'active_session_expires_at' => null])->save();
    expect(fn () => $service->delete($owner, 'module-test-session', $kind, $content->id, $content->record_version, true))->toThrow(AuthorizationException::class);
    $this->assertDatabaseHas($content->getTable(), ['id' => $content->id]);
})->with(['module', 'course', 'challenge']);

test('D04 D09 C07 audit failure rolls back parent revisions publication and notification cleanup', function (string $kind) {
    $content = moderationFixture($kind);
    $owner = User::find($content->created_by);
    $before = $content->only(['status', 'published_revision_id', 'record_version']);
    $this->mock(AuditRecorder::class, fn ($mock) => $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('Unavailable')));
    expect(fn () => deletionService($kind)->delete($owner, 'module-test-session', $kind, $content->id, $content->record_version, true))->toThrow(RuntimeException::class);
    expect($content->fresh()->only(array_keys($before)))->toBe($before);
    expect($content->fresh()->publishedRevision)->not->toBeNull();
    expect(DB::table('notifications')->where('type', $kind.'.reviewed')->count())->toBe(1);
})->with(['module', 'course', 'challenge']);

test('D04 deletion rechecks dependencies introduced after its warning and enforces csrf and throttle', function () {
    $content = moduleFixture(true);
    $owner = User::find($content->created_by);
    moduleSignIn($this, $owner);
    $this->get(route('content-deletion.show', ['module', $content->id]))->assertOk()->assertSee('It cannot be undone');
    app(ContentFeedback::class)->read(moduleAccount(Role::Learner), 'module-test-session', 'module', $content->id, true);
    $this->postJson(route('content-deletion.store', ['module', $content->id]), ['record_version' => $content->record_version, 'confirmed' => true])->assertUnprocessable()->assertJsonValidationErrors('content');
    foreach (range(1, 4) as $attempt) {
        $this->postJson(route('content-deletion.store', ['module', $content->id]), [])->assertUnprocessable();
    }
    $this->postJson(route('content-deletion.store', ['module', $content->id]), [])->assertTooManyRequests();
    $this->app->detectEnvironment(fn () => 'local');
    $this->post(route('content-deletion.store', ['module', $content->id]), ['record_version' => $content->record_version, 'confirmed' => true])->assertStatus(419);
    $this->assertDatabaseHas('learning_modules', ['id' => $content->id]);
});
