<?php

use App\Actions\Account\ReviewInstructorApplication;
use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\SendModeratorNotice;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Account\AccountDeletion;
use App\Services\Account\SecureAccountMailer;
use App\Services\Administration\AuditRecorder;
use App\Services\Administration\ModeratorAppointments;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function moderatorChangeData(User $target, array $overrides = []): array
{
    return array_replace(['action' => 'Appointed', 'profile_version' => $target->fresh()->profile_version,
        'current_password' => 'password', 'confirmed' => true], $overrides);
}

test('G02 Admin appoints and removes Moderators using the original approved participant role', function (Role $role) {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount($role);
    $original = $target->only(['email', 'name', 'username', 'email_verified_at', 'password']);
    moduleSignIn($this, $admin);
    $this->get(route('account-governance.show', $target))->assertOk()->assertSee('Appoint a Moderator');
    $this->post(route('account-governance.moderator', $target), moderatorChangeData($target))->assertRedirect(route('account-governance.show', $target));
    $target->refresh();
    expect($target->account_role)->toBe(Role::Moderator)->and($target->moderator_prior_role)->toBe($role)
        ->and($target->active_session_hash)->toBeNull()->and($target->account_status)->toBe(AccountStatus::Active)
        ->and($target->profile_version)->toBe(2)->and($target->only(array_keys($original)))->toEqual($original);
    expect($target->toArray())->not->toHaveKey('moderator_prior_role');
    $this->get(route('account-governance.show', $target))->assertSee('Removal returns this account to '.$role->value);
    $this->post(route('account-governance.moderator', $target), moderatorChangeData($target, ['action' => 'Removed']))->assertRedirect();
    expect($target->fresh()->account_role)->toBe($role)->and($target->fresh()->moderator_prior_role)->toBeNull()
        ->and($target->fresh()->active_session_hash)->toBeNull()->and($target->fresh()->profile_version)->toBe(3);
    expect(DB::table('account_role_changes')->pluck('resulting_role')->all())->toBe(['Moderator', $role->value]);
    expect(DB::table('audit_events')->where('subject_type', 'account_role_change')->count())->toBe(2);
    expect(DB::table('jobs')->count())->toBe(2);
})->with([Role::Learner, Role::Contributor, Role::Instructor]);

test('G02 unauthorized roles cannot appoint Moderators or select arbitrary roles', function (Role $role) {
    $actor = moduleAccount($role);
    $target = moduleAccount(Role::Learner);
    moduleSignIn($this, $actor);
    $this->post(route('account-governance.moderator', $target), moderatorChangeData($target))->assertForbidden();
    expect(fn () => app(ModeratorAppointments::class)->change($actor->fresh(), session()->getId(), $target, moderatorChangeData($target)))->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('account_role_changes', 0);
})->with([Role::Learner, Role::Contributor, Role::Instructor, Role::Moderator]);

test('G02 blocks self Admin and legacy Moderator targets without inventing a prior role', function () {
    $admin = moduleAccount(Role::Administrator);
    $otherAdmin = moduleAccount(Role::Administrator);
    $legacyModerator = moduleAccount(Role::Moderator);
    moduleSignIn($this, $admin);
    foreach ([$admin, $otherAdmin, $legacyModerator] as $target) {
        $this->get(route('account-governance.show', $target))->assertOk()->assertDontSee('name="current_password"', false);
        $this->post(route('account-governance.moderator', $target), moderatorChangeData($target, ['action' => 'Removed']))->assertForbidden();
    }
    expect($legacyModerator->fresh()->moderator_prior_role)->toBeNull();
    $this->assertDatabaseCount('account_role_changes', 0);
});

test('G02 invalid confirmations stale versions password and protected values cannot change roles', function (array $overrides, string $field) {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    moduleSignIn($this, $admin);
    $this->post(route('account-governance.moderator', $target), moderatorChangeData($target, $overrides))->assertSessionHasErrors($field);
    expect($target->fresh()->account_role)->toBe(Role::Learner)->and($target->fresh()->moderator_prior_role)->toBeNull()
        ->and(session()->getOldInput('current_password'))->toBeNull();
})->with([
    [['current_password' => 'wrong'], 'current_password'], [['confirmed' => false], 'confirmed'], [['profile_version' => 99], 'account'],
    [['action' => 'Removed'], 'account'], [['action' => 'Admin'], 'action'], [['account_role' => 'Admin'], 'account_role'],
    [['moderator_prior_role' => 'Instructor'], 'moderator_prior_role'], [['resulting_role' => 'Instructor'], 'resulting_role'],
    [['account_status' => 'Active'], 'account_status'], [['user_id' => 999999], 'user_id'],
]);

test('G02 target enforcement states cannot be bypassed by a role edit', function (AccountStatus $status) {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    $target->forceFill(['account_status' => $status])->save();
    expect(fn () => app(ModeratorAppointments::class)->change($admin, 'module-test-session', $target, moderatorChangeData($target)))->toThrow(AuthorizationException::class);
    expect($target->fresh()->account_status)->toBe($status)->and($target->fresh()->account_role)->toBe(Role::Learner);
})->with([AccountStatus::Suspended, AccountStatus::Archived, AccountStatus::Unverified, AccountStatus::Deleted]);

test('G02 requires fresh verified Active Admin session and verified target email', function () {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    $target->forceFill(['email_verified_at' => null, 'account_status' => AccountStatus::Unverified])->save();
    expect(fn () => app(ModeratorAppointments::class)->change($admin, 'module-test-session', $target, moderatorChangeData($target)))->toThrow(AuthorizationException::class);
    $target->forceFill(['email_verified_at' => now(), 'account_status' => AccountStatus::Active])->save();
    $admin->forceFill(['active_session_expires_at' => now()->subSecond()])->save();
    expect(fn () => app(ModeratorAppointments::class)->change($admin, 'module-test-session', $target, moderatorChangeData($target)))->toThrow(AuthorizationException::class);
    $admin->forceFill(['account_status' => AccountStatus::Suspended, 'active_session_expires_at' => now()->addHour()])->save();
    expect(fn () => app(ModeratorAppointments::class)->change($admin, 'module-test-session', $target, moderatorChangeData($target)))->toThrow(AuthorizationException::class);
});

test('G02 audit and queue failures roll back role prior-role sessions and history', function (bool $queueFailure) {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Instructor);
    $original = $target->active_session_hash;
    if ($queueFailure) {
        config(['queue.connections.database.connection' => 'other_database']);
    } else {
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test failure.'));
    }
    expect(fn () => app(ModeratorAppointments::class)->change($admin, 'module-test-session', $target, moderatorChangeData($target)))
        ->toThrow($queueFailure ? LogicException::class : RuntimeException::class);
    expect($target->fresh()->account_role)->toBe(Role::Instructor)->and($target->fresh()->moderator_prior_role)->toBeNull()
        ->and($target->fresh()->active_session_hash)->toBe($original);
    $this->assertDatabaseCount('account_role_changes', 0);
})->with([false, true]);

test('G02 preserves authored content pending applications and committed evaluations while tools follow the current role', function () {
    readyJudge();
    $module = moduleFixture(true);
    $target = User::find($module->created_by);
    $challenge = challengeFixture(true);
    $attempt = submitAttempt($challenge, $target);
    $admin = moduleAccount(Role::Administrator);
    $service = app(ModeratorAppointments::class);
    $service->change($admin, 'module-test-session', $target, moderatorChangeData($target));
    $target->refresh();
    expect(Gate::forUser($target)->allows('viewAny', User::class))->toBeTrue()
        ->and(Gate::forUser($target)->allows('create', LearningModule::class))->toBeFalse();
    evaluateAttempt($attempt);
    expect($attempt->fresh()->status)->toBe('Passed')->and($module->fresh()->status)->toBe('Published');
    $service->change($admin, 'module-test-session', $target, moderatorChangeData($target, ['action' => 'Removed']));
    expect(Gate::forUser($target->fresh())->allows('create', LearningModule::class))->toBeTrue();
    $learner = moduleAccount(Role::Learner);
    $application = pendingInstructor($learner);
    $service->change($admin, 'module-test-session', $learner, moderatorChangeData($learner));
    expect($application->fresh()->verification_status)->toBe('Pending');
    expect(fn () => app(ReviewInstructorApplication::class)->handle($admin, 'module-test-session', $application, 1, 'Approved', null, true))->toThrow(ValidationException::class);
});

test('G02 durable notices retry mail failure without reversing appointment or duplicating acknowledged delivery', function () {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    app(ModeratorAppointments::class)->change($admin, 'module-test-session', $target, moderatorChangeData($target));
    $notice = DB::table('account_role_changes')->sole();
    expect(DB::table('jobs')->sole()->payload)->not->toContain($admin->email, $target->email, 'current_password');
    $this->mock(SecureAccountMailer::class)->shouldReceive('send')->once()->andThrow(new RuntimeException('private transport diagnostics'));
    expect(fn () => (new SendModeratorNotice($notice->id))->handle())->toThrow(RuntimeException::class, 'Moderator appointment notice could not be delivered.');
    expect($target->fresh()->account_role)->toBe(Role::Moderator)->and(DB::table('account_role_changes')->sole()->failed_at)->not->toBeNull();
    $this->mock(SecureAccountMailer::class)->shouldReceive('send')->once();
    (new SendModeratorNotice($notice->id))->handle();
    (new SendModeratorNotice($notice->id))->handle();
    expect(DB::table('account_role_changes')->sole()->sent_at)->not->toBeNull();
});

test('G02 history survives removal and deletion while queued notices are cancelled for deleted identity', function () {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Learner);
    $service = app(ModeratorAppointments::class);
    $service->change($admin, 'module-test-session', $target, moderatorChangeData($target));
    $service->change($admin, 'module-test-session', $target, moderatorChangeData($target, ['action' => 'Removed']));
    $target->refresh()->forceFill(['active_session_hash' => hash('sha256', 'module-test-session'), 'active_session_expires_at' => now()->addHour()])->save();
    app(AccountDeletion::class)->delete($target, 'module-test-session', deletionData($target));
    $this->mock(SecureAccountMailer::class)->shouldNotReceive('send');
    foreach (DB::table('account_role_changes')->get() as $notice) {
        (new SendModeratorNotice($notice->id))->handle();
    }
    expect(DB::table('account_role_changes')->whereNotNull('cancelled_at')->count())->toBe(2);
});

test('G02 PostgreSQL rejects fabricated prior roles invalid role restoration and destructive rollback', function () {
    $admin = moduleAccount(Role::Administrator);
    $target = moduleAccount(Role::Contributor);
    expect(fn () => DB::transaction(fn () => DB::table('users')->where('id', $target->id)->update(['moderator_prior_role' => 'Admin'])))->toThrow(QueryException::class);
    app(ModeratorAppointments::class)->change($admin, 'module-test-session', $target, moderatorChangeData($target));
    expect(fn () => DB::transaction(fn () => DB::table('users')->where('id', $target->id)->update(['account_role' => 'Instructor'])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('account_role_changes')->update(['resulting_role' => 'Admin'])))->toThrow(QueryException::class);
    $migration = require database_path('migrations/2026_10_03_000019_add_moderator_appointments.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
});

test('G02 Moderator role routes require authentication missing-record handling and CSRF', function () {
    $target = User::factory()->create();
    $this->post(route('account-governance.moderator', $target), moderatorChangeData($target))->assertRedirect(route('login'));
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $this->post(route('account-governance.moderator', 999999), moderatorChangeData($target))->assertNotFound();
    $this->app->detectEnvironment(fn () => 'local');
    $this->post(route('account-governance.moderator', $target), moderatorChangeData($target))->assertStatus(419);
});
