<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\AccountRecovery;
use App\Models\EmailVerification;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Account\AccountRecoveryService;
use App\Services\Account\EmailVerificationService;
use App\Services\Account\ProfileEditing;
use App\Services\Administration\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

function profileEditData(User $user, array $overrides = []): array
{
    return array_replace(['username' => $user->username ?? 'playername', 'first_name' => 'Player',
        'last_name' => 'Coder', 'email' => $user->email, 'profile_version' => $user->fresh()->profile_version], $overrides);
}

function profileSignIn($test, User $user): void
{
    $test->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    $test->withCredentials()->withCookie(config('session.cookie'), session()->getId());
}

test('A06 all approved roles edit only their own ordinary profile fields without password confirmation', function (Role $role) {
    $user = User::factory()->create(['account_role' => $role]);
    $other = User::factory()->create();
    profileSignIn($this, $user);
    $sessionId = session()->getId();
    $this->get(route('account.edit', ['user_id' => $other->id]))->assertOk()->assertSee($user->email)
        ->assertDontSee($other->email)->assertHeader('Cache-Control', 'no-store, private');
    $this->patch(route('account.update'), profileEditData($user))->assertRedirect(route('account.show'));
    expect($user->fresh()->first_name)->toBe('Player')->and($user->fresh()->name)->toBe('Player Coder')
        ->and($user->fresh()->profile_version)->toBe(2)->and(session()->getId())->toBe($sessionId);
    $this->assertAuthenticatedAs($user);
    $audit = DB::table('audit_events')->where('event', 'account.profile-updated')->sole();
    expect($audit->actor_id)->toBe($user->id)->and($audit->context)->not->toContain($user->email, 'Player Coder');
    expect($other->fresh()->profile_version)->toBe(1);
})->with(Role::cases());

test('A06 rejects invalid fields and protected role status identity inputs', function (array $change, string $field) {
    $user = User::factory()->create();
    profileSignIn($this, $user);
    $this->patchJson(route('account.update'), profileEditData($user, $change))->assertJsonValidationErrors($field);
    expect($user->fresh()->profile_version)->toBe(1);
    $this->assertDatabaseCount('audit_events', 0);
})->with([
    [['username' => 'short'], 'username'], [['first_name' => '<script>'], 'first_name'],
    [['last_name' => str_repeat('a', 51)], 'last_name'], [['email' => 'bad'], 'email'],
    [['account_role' => 'Admin'], 'account_role'], [['account_status' => 'Active'], 'account_status'],
    [['user_id' => 42], 'user_id'], [['email_verified_at' => now()->toDateString()], 'email_verified_at'],
    [['password' => 'weak', 'password_confirmation' => 'weak'], 'password'],
    [['password' => 'SecurePassword12!', 'password_confirmation' => 'other'], 'password'],
]);

test('A06 sensitive changes require the locked account current password and never flash secrets', function () {
    $user = User::factory()->create();
    profileSignIn($this, $user);
    $this->from(route('account.edit'))->patch(route('account.update'), profileEditData($user, ['email' => 'new@example.test',
        'current_password' => 'private-wrong', 'password' => 'SecurePassword12!', 'password_confirmation' => 'SecurePassword12!']))
        ->assertSessionHasErrors('current_password');
    expect(session()->getOldInput('current_password'))->toBeNull()->and(session()->getOldInput('password'))->toBeNull()
        ->and(session()->getOldInput('password_confirmation'))->toBeNull()->and($user->fresh()->profile_version)->toBe(1);
});

test('A06 email changes revoke sessions and old proofs and queue verification for the normalized new email atomically', function () {
    $user = User::factory()->create();
    app(AccountRecoveryService::class)->request($user);
    $oldRecovery = VerificationDelivery::where('purpose', 'recovery')->sole()->token;
    EmailVerification::create(['user_id' => $user->id, 'email' => $user->email, 'request_count' => 5,
        'token_hash' => hash('sha256', str_repeat('a', 64)), 'expires_at' => now()->addHour(), 'last_requested_at' => now()]);
    profileSignIn($this, $user);
    $this->patch(route('account.update'), profileEditData($user, ['email' => ' NEW@EXAMPLE.TEST ', 'current_password' => 'password']))
        ->assertRedirect(route('verification.notice'));
    $this->assertGuest();
    expect($user->fresh()->email)->toBe('new@example.test')->and($user->fresh()->account_status)->toBe(AccountStatus::Unverified)
        ->and($user->fresh()->email_verified_at)->toBeNull()->and($user->fresh()->active_session_hash)->toBeNull()
        ->and(AccountRecovery::sole()->token_hash)->toBeNull()->and(app(AccountRecoveryService::class)->authorization($oldRecovery))->toBeNull();
    $delivery = VerificationDelivery::where('purpose', 'verification')->sole();
    expect(EmailVerification::sole()->request_count)->toBe(1)->and($delivery->token)->not->toBeNull();
    $this->assertDatabaseCount('jobs', 2);
    expect(app(EmailVerificationService::class)->verify(str_repeat('a', 64)))->toBeFalse()
        ->and(app(EmailVerificationService::class)->verify($delivery->token))->toBeTrue()
        ->and($user->fresh()->account_status)->toBe(AccountStatus::Active);
});

test('A06 password changes hash securely revoke sessions and keep the approved failure counter unchanged', function () {
    $user = User::factory()->create();
    profileSignIn($this, $user);
    $user->forceFill(['failed_login_attempts' => 2])->save();
    $this->patch(route('account.update'), profileEditData($user, ['current_password' => 'password',
        'password' => 'SecurePassword12!', 'password_confirmation' => 'SecurePassword12!']))->assertRedirect(route('login'));
    $this->assertGuest();
    expect(Hash::check('SecurePassword12!', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->active_session_hash)->toBeNull()->and($user->fresh()->account_status)->toBe(AccountStatus::Active)
        ->and($user->fresh()->failed_login_attempts)->toBe(2);
    $this->get(route('account.show'))->assertRedirect(route('login'));
});

test('A06 stale versions duplicate emails and revoked sessions cannot change an account', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    profileSignIn($this, $user);
    $this->patchJson(route('account.update'), profileEditData($user, ['profile_version' => 3]))->assertJsonValidationErrors('profile_version');
    $this->patchJson(route('account.update'), profileEditData($user, ['email' => strtoupper($other->email)]))->assertJsonValidationErrors('email');
    $user->forceFill(['active_session_hash' => hash('sha256', 'replacement-session')])->save();
    $this->patchJson(route('account.update'), profileEditData($user))->assertUnauthorized();
    expect($user->fresh()->profile_version)->toBe(1);
});

test('A06 current status and session are checked directly by the service', function (AccountStatus $status) {
    $user = moduleAccount(Role::Learner);
    $user->forceFill(['account_status' => $status])->save();
    expect(fn () => app(ProfileEditing::class)->update($user, 'module-test-session', profileEditData($user)))
        ->toThrow(AuthorizationException::class);
})->with([AccountStatus::Suspended, AccountStatus::Archived, AccountStatus::Deleted, AccountStatus::Unverified]);

test('A06 queue or audit failure rolls back profile credentials delivery and audit writes', function (bool $queueFailure) {
    $user = moduleAccount(Role::Learner);
    if ($queueFailure) {
        config(['queue.connections.database.table' => 'missing_queue_table']);
        Log::shouldReceive('error')->once()->with('Profile storage write failed.', ['sqlstate' => '42P01']);
    } else {
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test audit failure.'));
    }
    expect(fn () => app(ProfileEditing::class)->update($user, 'module-test-session', profileEditData($user, ['email' => 'next@example.test', 'current_password' => 'password'])))
        ->toThrow(RuntimeException::class);
    expect($user->fresh()->email)->toBe($user->email)->and($user->fresh()->profile_version)->toBe(1)
        ->and($user->fresh()->active_session_hash)->not->toBeNull();
    $this->assertDatabaseCount('verification_deliveries', 0);
    $this->assertDatabaseCount('email_verifications', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with([true, false]);

test('A06 authenticated edits use CSRF protection and prohibit cross-account resource routes', function () {
    $this->get(route('account.edit'))->assertRedirect(route('login'));
    $user = User::factory()->create();
    profileSignIn($this, $user);
    $this->app->detectEnvironment(fn () => 'local');
    $this->patch(route('account.update'), profileEditData($user))->assertStatus(419);
    $this->patch('/account/'.$user->id, profileEditData($user))->assertNotFound();
});
