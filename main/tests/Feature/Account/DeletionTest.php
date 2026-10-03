<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\EraseAccountFile;
use App\Jobs\Account\SendRecoveryEmail;
use App\Models\AccountFileErasure;
use App\Models\User;
use App\Services\Account\AccountDeletion;
use App\Services\Account\AccountRecoveryService;
use App\Services\Account\InstructorApplications;
use App\Services\Administration\AuditRecorder;
use App\Services\Challenges\SubmissionEvaluation;
use App\Services\Gamification\LearningProgression;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function deletionData(User $user, array $overrides = []): array
{
    return array_replace(['profile_version' => $user->fresh()->profile_version, 'current_password' => 'password',
        'confirmation_phrase' => 'DELETE MY ACCOUNT', 'confirmed' => true], $overrides);
}

test('Delete Account removes personal profile and learning records but retains a non-recoverable audit reference', function (Role $role) {
    $user = moduleAccount($role);
    $oldEmail = $user->email;
    app(LearningProgression::class)->record($user, 'module-test-session', 'sequences', 'game', ['program' => ['right', 'right', 'up', 'right', 'right']]);
    [$token, $proof] = recoveryProof($user);
    $deliveryId = DB::table('verification_deliveries')->sole()->id;
    moduleSignIn($this, $user);
    $this->get(route('account.delete'))->assertOk()->assertSee('DELETE MY ACCOUNT')->assertHeader('Cache-Control', 'no-store, private');
    $this->post(route('account.delete.store'), deletionData($user))->assertRedirect(route('login'));
    $this->assertGuest();
    $user->refresh();
    expect($user->account_status)->toBe(AccountStatus::Deleted)->and($user->anonymized_at)->not->toBeNull()
        ->and($user->name)->toBe('Deleted account')->and($user->email)->toEndWith('@deleted.invalid')
        ->and($user->username)->toBeNull()->and($user->first_name)->toBeNull()->and($user->last_name)->toBeNull()
        ->and($user->email_verified_at)->toBeNull()->and($user->active_session_hash)->toBeNull()->and($user->remember_token)->toBeNull();
    foreach (['learning_progress', 'learning_activity_days', 'learning_level_completions', 'account_recoveries', 'verification_deliveries'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
    expect(app(AccountRecoveryService::class)->authorization($token))->toBeNull()
        ->and(app(AccountRecoveryService::class)->complete($proof, 'FreshStrongPass12!'))->toBeFalse();
    (new SendRecoveryEmail($deliveryId))->handle();
    $this->post(route('login.store'), ['email' => $oldEmail, 'password' => 'password'])->assertSessionHasErrors('email');
    expect(DB::table('audit_events')->where('event', 'account.deleted')->sole()->context)->not->toContain($oldEmail);
})->with([Role::Learner, Role::Contributor, Role::Instructor]);

test('Delete Account retains published content owned by others while removing enrollment and completed source history', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $course = courseFixture(true);
    $user = moduleAccount(Role::Learner);
    joinCourse($course, $user);
    $attempt = submitAttempt($challenge, $user, ['source_code' => '# private personal source']);
    evaluateAttempt($attempt);
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    foreach (['challenge_submissions', 'challenge_case_evaluations', 'challenge_participations', 'course_enrollments', 'course_module_progress'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
    $this->assertDatabaseHas('coding_challenges', ['id' => $challenge->id, 'status' => 'Published']);
    $this->assertDatabaseHas('learning_courses', ['id' => $course->id, 'status' => 'Published']);
    expect(app(SubmissionEvaluation::class)->advance($attempt->id))->toBeNull();
});

test('Delete Account blocks active evaluations until they finish without consuming or cancelling them', function () {
    readyJudge();
    $challenge = challengeFixture(true);
    $user = moduleAccount(Role::Learner);
    $attempt = submitAttempt($challenge, $user);
    expect(fn () => app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user)))->toThrow(ValidationException::class);
    expect($user->fresh()->account_status)->toBe(AccountStatus::Active)->and($attempt->fresh()->status)->toBe('Queued');
    evaluateAttempt($attempt);
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    expect($user->fresh()->account_status)->toBe(AccountStatus::Deleted);
});

test('Delete Account blocks every kind of authored content and keeps its identity and revisions intact', function (string $kind) {
    $content = match ($kind) {
        'module' => moduleFixture(), 'course' => courseFixture(), default => challengeFixture(),
    };
    $user = User::find($content->created_by);
    moduleSignIn($this, $user);
    $this->get(route('account.delete'))->assertOk()->assertSee('content retention rules')->assertDontSee('name="confirmation_phrase"', false);
    $this->post(route('account.delete.store'), deletionData($user))->assertSessionHasErrors('account');
    expect($user->fresh()->account_status)->toBe(AccountStatus::Active)->and($content->fresh())->not->toBeNull();
})->with(['module', 'course', 'challenge']);

test('Delete Account enforces password confirmation phrase fresh version and protected inputs', function (array $override, string $field) {
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    $this->from(route('account.delete'))->post(route('account.delete.store'), deletionData($user, $override))->assertSessionHasErrors($field);
    expect($user->fresh()->account_status)->toBe(AccountStatus::Active)->and(session()->getOldInput('current_password'))->toBeNull();
})->with([
    [['current_password' => 'wrong'], 'current_password'], [['confirmed' => false], 'confirmed'],
    [['confirmation_phrase' => 'delete'], 'confirmation_phrase'], [['profile_version' => 99], 'profile_version'],
    [['user_id' => 999], 'user_id'], [['account_status' => 'Deleted'], 'account_status'],
]);

test('Delete Account administrative roles cannot self-delete through the participant use case', function (Role $role) {
    $user = User::factory()->create(['account_role' => $role]);
    moduleSignIn($this, $user);
    $this->get(route('account.delete'))->assertForbidden();
    $this->post(route('account.delete.store'), deletionData($user))->assertForbidden();
})->with([Role::Moderator, Role::Administrator]);

test('Delete Account requires a current Active session and Archived users must recover first', function (AccountStatus $status) {
    $user = moduleAccount(Role::Learner);
    $user->forceFill(['account_status' => $status])->save();
    expect(fn () => app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user)))->toThrow(AuthorizationException::class);
})->with([AccountStatus::Archived, AccountStatus::Suspended, AccountStatus::Deleted, AccountStatus::Unverified]);

test('Delete Account credential versions are queued once per private path and erased idempotently', function () {
    $user = moduleAccount(Role::Learner);
    $application = pendingInstructor($user);
    app(InstructorApplications::class)->snapshot($application);
    $path = $application->credential_path;
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    $this->assertDatabaseCount('instructor_applications', 0);
    $this->assertDatabaseCount('instructor_application_versions', 0);
    $this->assertDatabaseCount('account_file_erasures', 1);
    $erasure = AccountFileErasure::sole();
    expect($erasure->path)->toBe($path)->and(DB::table('account_file_erasures')->sole()->path)->not->toBe($path);
    expect(DB::table('jobs')->sole()->payload)->not->toContain($path, $user->email);
    Storage::disk('local')->assertExists($path);
    (new EraseAccountFile($erasure->id))->handle();
    (new EraseAccountFile($erasure->id))->handle();
    Storage::disk('local')->assertMissing($path);
    expect($erasure->fresh()->path)->toBeNull()->and($erasure->fresh()->completed_at)->not->toBeNull()->and($erasure->fresh()->attempts)->toBe(1);
});

test('Delete Account audit or queue failure rolls back all data and leaves private files intact', function (bool $queueFailure) {
    $user = moduleAccount(Role::Learner);
    $application = pendingInstructor($user);
    if ($queueFailure) {
        config(['queue.connections.database.table' => 'missing_erasure_jobs']);
        Log::shouldReceive('error')->once()->with('Account deletion write failed.', ['sqlstate' => '42P01']);
    } else {
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test failure.'));
    }
    expect(fn () => app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user)))->toThrow(RuntimeException::class);
    expect($user->fresh()->account_status)->toBe(AccountStatus::Active)->and($user->fresh()->email)->toBe($user->email);
    $this->assertDatabaseCount('account_file_erasures', 0);
    $this->assertDatabaseCount('instructor_applications', 1);
    Storage::disk('local')->assertExists($application->credential_path);
})->with([true, false]);

test('Account erasure failures retain pending work sanitized diagnostics and allow safe retry', function () {
    Storage::fake('local');
    $user = moduleAccount(Role::Learner);
    $erasure = AccountFileErasure::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'disk' => 'local',
        'path_digest' => hash('sha256', '../private-target'), 'path' => '../private-target', 'last_queued_at' => now()->subMinutes(10)]);
    Log::shouldReceive('warning')->once()->with('Private account file erasure failed.', ['erasure_id' => $erasure->id]);
    expect(fn () => (new EraseAccountFile($erasure->id))->handle())->toThrow(RuntimeException::class, 'Private account file erasure could not be completed.');
    expect($erasure->fresh()->completed_at)->toBeNull()->and($erasure->fresh()->failed_at)->not->toBeNull();
    $this->artisan('kody:account-erasures-retry')->assertSuccessful();
    $this->artisan('kody:account-erasures-retry')->assertSuccessful();
    $this->assertDatabaseCount('jobs', 1);
    $this->travel(10)->minutes();
    $this->artisan('kody:account-erasures-retry')->assertSuccessful();
    $this->assertDatabaseCount('jobs', 1);
    DB::table('jobs')->delete();
    $this->artisan('kody:account-erasures-retry')->assertSuccessful();
    $this->assertDatabaseCount('jobs', 1);
});

test('Delete Account rejects unauthenticated routes cross-account paths and missing CSRF', function () {
    $this->get(route('account.delete'))->assertRedirect(route('login'));
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    $this->app->detectEnvironment(fn () => 'local');
    $this->post(route('account.delete.store'), deletionData($user))->assertStatus(419);
    $this->post('/account/'.$user->id.'/delete', deletionData($user))->assertNotFound();
});

test('Delete Account erasure migration refuses rollback after account anonymization', function () {
    $user = moduleAccount(Role::Learner);
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    $migration = require database_path('migrations/2026_10_03_000016_add_account_anonymization.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
});

test('Delete Account batches credential history and deduplicates paths across version chunks', function () {
    $user = moduleAccount(Role::Learner);
    $application = pendingInstructor($user);
    $otherPath = 'instructor-credentials/older-proof.pdf';
    Storage::disk('local')->put($otherPath, '%PDF-private-previous-version');
    $versions = [];
    for ($version = 1; $version <= 102; $version++) {
        $versions[] = ['instructor_application_id' => $application->id, 'application_version' => $version,
            'institution_name' => 'Private Institute', 'specialization' => 'Programming', 'credential_disk' => 'local',
            'credential_path' => $version === 102 ? $otherPath : $application->credential_path, 'created_at' => now()];
    }
    DB::table('instructor_application_versions')->insert($versions);
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    $this->assertDatabaseCount('account_file_erasures', 2);
    $this->assertDatabaseCount('instructor_application_versions', 0);
    $this->assertDatabaseCount('jobs', 2);
});

test('Account erasure treats an already missing private object as successful cleanup', function () {
    Storage::fake('local');
    $user = moduleAccount(Role::Learner);
    $path = 'instructor-credentials/missing-proof.pdf';
    $erasure = AccountFileErasure::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'disk' => 'local',
        'path_digest' => hash('sha256', $path), 'path' => $path, 'last_queued_at' => now()]);
    (new EraseAccountFile($erasure->id))->handle();
    expect($erasure->fresh()->completed_at)->not->toBeNull()->and($erasure->fresh()->path)->toBeNull();
});

test('Delete Account PostgreSQL marker prevents reactivating or restoring personal fields on an anonymized account', function () {
    $user = moduleAccount(Role::Learner);
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    expect(fn () => DB::transaction(fn () => DB::table('users')->where('id', $user->id)->update(['name' => 'Restored identity'])))
        ->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('users')->where('id', $user->id)->update(['account_status' => 'Active', 'email_verified_at' => now()])))
        ->toThrow(QueryException::class);
});
