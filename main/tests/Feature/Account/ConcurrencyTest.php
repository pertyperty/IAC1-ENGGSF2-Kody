<?php

use App\Enums\Role;
use App\Models\AccountRecovery;
use App\Models\EmailVerification;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Account\AccountRecoveryService;
use App\Services\Account\EmailVerificationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

uses(DatabaseMigrations::class);

test('A10 concurrent reviews apply one role elevation audit and notification', function () {
    $applicant = User::factory()->create();
    $application = InstructorApplication::create(['user_id' => $applicant->id, 'institution_name' => 'Test Institute', 'specialization' => 'Coding', 'credential_disk' => 'local', 'credential_path' => 'instructor-credentials/test.pdf']);
    $reviewer = User::factory()->create(['account_role' => Role::Moderator, 'active_session_hash' => hash('sha256', 'test-review-session'), 'active_session_expires_at' => now()->addHour()]);
    $results = simultaneousAccountRequests('creator-review', ['actor_id' => $reviewer->id, 'session_id' => 'test-review-session', 'application_id' => $application->id], fn () => User::whereKey($applicant->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'reviewed'])->and($applicant->fresh()->account_role)->toBe(Role::Instructor);
    $this->assertDatabaseCount('audit_events', 1);
    $this->assertDatabaseCount('creator_decision_deliveries', 1);
    $this->assertDatabaseCount('jobs', 1);
});

test('game-first progression overlapping wins cannot duplicate daily streak or level grants', function () {
    $user = User::factory()->create(['active_session_hash' => hash('sha256', 'test-learning-session'), 'active_session_expires_at' => now()->addHour()]);
    $results = simultaneousAccountRequests('learning-complete', ['user_id' => $user->id, 'session_id' => 'test-learning-session'], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    expect($results)->toBe(['saved', 'saved']);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->assertDatabaseCount('learning_level_completions', 1);
    $this->assertDatabaseHas('learning_progress', ['user_id' => $user->id, 'current_streak' => 1, 'longest_streak' => 1]);
});

test('A04 simultaneous recovery requests cannot exceed the hourly cap', function () {
    $user = User::factory()->create();
    app(AccountRecoveryService::class)->request($user);
    AccountRecovery::query()->update(['request_count' => 4, 'last_requested_at' => now()->subMinutes(2)]);
    $results = simultaneousAccountRequests('recover-request', ['user_id' => $user->id], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['limited', 'queued'])->and(AccountRecovery::sole()->request_count)->toBe(5);
    $this->assertDatabaseCount('jobs', 2);
});

test('A04 simultaneous password resets consume one recovery token exactly once', function () {
    $user = User::factory()->create();
    $service = app(AccountRecoveryService::class);
    $service->request($user);
    $proof = $service->authorization(VerificationDelivery::sole()->token);
    $results = simultaneousAccountRequests('recover-complete', ['proof' => $proof, 'password' => 'NewStrongPass12!'], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['invalid', 'recovered'])->and(AccountRecovery::sole()->token_hash)->toBeNull();
});

function simultaneousAccountRequests(string $mode, array $input, Closure $lock): array
{
    $processes = [];
    $inputPath = tempnam(sys_get_temp_dir(), 'kody-concurrency-');
    file_put_contents($inputPath, Crypt::encryptString(json_encode($input, JSON_THROW_ON_ERROR)));
    DB::beginTransaction();

    try {
        $lock();
        foreach (range(1, 2) as $index) {
            $process = new Process([PHP_BINARY, base_path('tests/Support/account-concurrency.php'), $mode, $inputPath], base_path(), timeout: 30);
            $process->start();
            $processes[] = $process;
        }
        // Prove both requests overlap and wait on PostgreSQL locks before releasing them.
        $deadline = microtime(true) + 10;
        do {
            DB::select('SELECT pg_stat_clear_snapshot()');
            $waiting = DB::selectOne("SELECT count(*) AS count FROM pg_stat_activity WHERE application_name = 'kody-account-concurrency' AND wait_event_type = 'Lock'")->count;
            if ($waiting >= 2) {
                break;
            }
            usleep(20000);
        } while (microtime(true) < $deadline);
        expect((int) $waiting)->toBe(2);
        DB::commit();

        return array_map(function (Process $process): string {
            expect($process->wait())->toBe(0);

            return trim($process->getOutput());
        }, $processes);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop(0);
            }
        }
        if (is_file($inputPath)) {
            unlink($inputPath);
        }
    }
}

test('A01 simultaneous duplicate registrations create one account and one delivery', function () {
    $input = [
        'username' => 'racelearner', 'email' => 'race@example.test',
        'first_name' => 'Concurrent', 'last_name' => 'Learner',
        'password' => 'StrongPass12!', 'account_type' => 'learner',
    ];
    $results = simultaneousAccountRequests('register', $input, fn () => DB::statement('LOCK TABLE users IN SHARE MODE'));
    sort($results);
    expect($results)->toBe(['created', 'duplicate']);
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('email_verifications', 1);
    $this->assertDatabaseCount('verification_deliveries', 1);
    $this->assertDatabaseCount('jobs', 1);
});

test('A02 simultaneous resends cannot consume more than five requests', function () {
    $user = User::factory()->unverified()->create();
    app(EmailVerificationService::class)->request($user);
    EmailVerification::where('user_id', $user->id)->update(['request_count' => 4, 'last_requested_at' => now()->subMinutes(2)]);

    $results = simultaneousAccountRequests('resend', ['email' => $user->email], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['limited', 'queued'])
        ->and(EmailVerification::sole()->request_count)->toBe(5);
    $this->assertDatabaseCount('jobs', 2);
});

test('A02 simultaneous verification links activate the account only once', function () {
    $user = User::factory()->unverified()->create();
    app(EmailVerificationService::class)->request($user);
    $token = VerificationDelivery::sole()->token;
    $results = simultaneousAccountRequests('verify', ['token' => $token], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['invalid', 'verified'])
        ->and($user->fresh()->email_verified_at)->not->toBeNull()
        ->and(EmailVerification::sole()->token_hash)->toBeNull();
});

test('A03 overlapping password failures preserve the lockout threshold', function () {
    $user = User::factory()->create(['failed_login_attempts' => 2]);
    $results = simultaneousAccountRequests('login', ['email' => $user->email, 'password' => 'wrong'], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    expect($results)->toBe(['locked', 'locked'])->and($user->fresh()->failed_login_attempts)->toBe(3)
        ->and($user->fresh()->login_locked_until)->not->toBeNull();
});

test('A03 overlapping valid logins authenticate one session and prompt the other', function () {
    $user = User::factory()->create();
    $results = simultaneousAccountRequests('login', ['email' => $user->email, 'password' => 'password'], fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['authenticated', 'conflict'])->and($user->fresh()->active_session_hash)->not->toBeNull();
});

test('A03 concurrent confirmations cannot both replace the same session', function () {
    $user = User::factory()->create([
        'active_session_hash' => hash('sha256', 'previous-session'),
        'active_session_expires_at' => now()->addMinutes(30),
    ]);
    $input = ['confirmation' => [
        'user_id' => $user->id, 'password_digest' => hash('sha256', $user->password),
        'session_hash' => $user->active_session_hash, 'expires_at' => now()->addMinutes(5)->timestamp,
    ]];
    $results = simultaneousAccountRequests('confirm', $input, fn () => User::whereKey($user->id)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['authenticated', 'invalid'])->and($user->fresh()->active_session_hash)->not->toBe(hash('sha256', 'previous-session'));
});
