<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Account\AccountArchival;
use App\Services\Account\AccountRecoveryService;
use App\Services\Administration\AuditRecorder;
use App\Services\Gamification\LearningProgression;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function archiveData(User $user, array $overrides = []): array
{
    return array_replace(['profile_version' => $user->fresh()->profile_version, 'current_password' => 'password', 'confirmed' => true], $overrides);
}

test('A07 participant roles archive only their own account and preserve profile and progression data', function (Role $role) {
    $user = moduleAccount($role);
    app(LearningProgression::class)->record($user, 'module-test-session', 'sequences', 'game', ['program' => ['right', 'right', 'up', 'right', 'right']]);
    $before = $user->fresh()->only(['email', 'password', 'username', 'name', 'first_name', 'last_name', 'account_role', 'email_verified_at']);
    $progress = DB::table('learning_progress')->where('user_id', $user->id)->first();
    moduleSignIn($this, $user);
    $this->get(route('account.archive'))->assertOk()->assertSee('Cancel and keep learning')->assertHeader('Cache-Control', 'no-store, private');
    $this->post(route('account.archive.store'), archiveData($user))->assertRedirect(route('login'));
    $this->assertGuest();
    expect($user->fresh()->account_status)->toBe(AccountStatus::Archived)->and($user->fresh()->active_session_hash)->toBeNull()
        ->and($user->fresh()->only(array_keys($before)))->toEqual($before)
        ->and(DB::table('learning_progress')->where('user_id', $user->id)->first())->toEqual($progress);
    expect(DB::table('audit_events')->where('event', 'account.archived')->count())->toBe(1);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
})->with([Role::Learner, Role::Contributor, Role::Instructor]);

test('A07 administrative roles cannot self-archive under the three-role SRS actor scope', function (Role $role) {
    $user = User::factory()->create(['account_role' => $role]);
    moduleSignIn($this, $user);
    $this->get(route('account.archive'))->assertForbidden();
    $this->post(route('account.archive.store'), archiveData($user))->assertForbidden();
    expect($user->fresh()->account_status)->toBe(AccountStatus::Active);
})->with([Role::Moderator, Role::Administrator]);

test('A07 cancellation bad passwords stale profiles and incomplete confirmation do not archive', function (array $overrides, string $field) {
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    $this->get(route('account.archive'))->assertOk();
    expect($user->fresh()->account_status)->toBe(AccountStatus::Active);
    $this->from(route('account.archive'))->post(route('account.archive.store'), archiveData($user, $overrides))->assertSessionHasErrors($field);
    expect($user->fresh()->account_status)->toBe(AccountStatus::Active)->and(session()->getOldInput('current_password'))->toBeNull();
    expect(DB::table('audit_events')->where('event', 'account.archived')->count())->toBe(0);
})->with([
    [['current_password' => 'wrong'], 'current_password'], [['confirmed' => false], 'confirmed'],
    [['profile_version' => 9], 'profile_version'], [['user_id' => 999], 'user_id'], [['account_status' => 'Archived'], 'account_status'],
]);

test('A07 archive revokes old recovery proof and a fresh A04 recovery reactivates the same role and data', function () {
    $user = moduleAccount(Role::Instructor);
    [$oldToken, $oldProof] = recoveryProof($user);
    app(AccountArchival::class)->archive($user, 'module-test-session', archiveData($user));
    $recovery = app(AccountRecoveryService::class);
    expect($recovery->authorization($oldToken))->toBeNull()->and($recovery->complete($oldProof, 'NewStrongPass12!'))->toBeFalse();
    $this->travel(61)->seconds();
    [$token, $proof] = recoveryProof($user->fresh());
    expect($recovery->complete($proof, 'NewStrongPass12!'))->toBeTrue()
        ->and($user->fresh()->account_status)->toBe(AccountStatus::Active)->and($user->fresh()->account_role)->toBe(Role::Instructor)
        ->and($user->fresh()->active_session_hash)->toBeNull();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'NewStrongPass12!'])->assertRedirect(route(auth()->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
});

test('A07 action rejects revoked sessions and restricted fresh account states', function (string $change) {
    $user = moduleAccount(Role::Learner);
    match ($change) {
        'session' => $user->forceFill(['active_session_hash' => hash('sha256', 'replacement')])->save(),
        'expiry' => $user->forceFill(['active_session_expires_at' => now()->subMinute()])->save(),
        default => $user->forceFill(['account_status' => AccountStatus::from($change)])->save(),
    };
    expect(fn () => app(AccountArchival::class)->archive($user, 'module-test-session', archiveData($user)))->toThrow(AuthorizationException::class);
})->with(['session', 'expiry', 'Suspended', 'Archived', 'Deleted', 'Unverified']);

test('A07 audit failure leaves account sessions and recovery proofs unchanged', function () {
    $this->freezeTime();
    $user = moduleAccount(Role::Learner);
    [$token, $proof] = recoveryProof($user);
    $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test failure.'));
    expect(fn () => app(AccountArchival::class)->archive($user, 'module-test-session', archiveData($user)))->toThrow(RuntimeException::class);
    expect($user->fresh()->account_status)->toBe(AccountStatus::Active)->and($user->fresh()->active_session_hash)->not->toBeNull()
        ->and(app(AccountRecoveryService::class)->authorization($token))->toEqual($proof);
});

test('A07 archive requires authentication and CSRF and has no other-account resource route', function () {
    $this->get(route('account.archive'))->assertRedirect(route('login'));
    $user = User::factory()->create();
    moduleSignIn($this, $user);
    $this->app->detectEnvironment(fn () => 'local');
    $this->post(route('account.archive.store'), archiveData($user))->assertStatus(419);
    $this->post('/account/'.$user->id.'/archive', archiveData($user))->assertNotFound();
});
