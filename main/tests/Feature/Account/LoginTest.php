<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('A03 verified users of every role can sign in with a rotated session', function (Role $role) {
    $user = User::factory()->create(['account_role' => $role]);
    session()->start();
    $oldId = session()->getId();
    $oldToken = session()->token();
    $this->post(route('login.store'), ['email' => strtoupper($user->email), 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($oldId)
        ->and(session()->token())->not->toBe($oldToken)
        ->and($user->fresh()->last_login_at)->not->toBeNull()
        ->and($user->fresh()->active_session_hash)->toBe(hash('sha256', session()->getId()));
    $this->withCookie(config('session.cookie'), session()->getId());
    $this->get(route('dashboard'))->assertOk()->assertSee('View your profile')
        ->assertHeader('Cache-Control', 'no-store, private');
})->with(Role::cases());

test('A03 unknown email and wrong password return the same credentials error', function () {
    $user = User::factory()->create();
    $unknown = $this->postJson(route('login.store'), ['email' => 'missing@example.test', 'password' => 'wrong'])->assertUnprocessable();
    $wrong = $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
    expect($unknown->json('errors'))->toBe($wrong->json('errors'))
        ->and($user->fresh()->failed_login_attempts)->toBe(1);
    $this->assertGuest();
});

test('A03 malformed inputs do not count as password failures', function () {
    $user = User::factory()->create();
    $this->postJson(route('login.store'), ['email' => 'invalid', 'password' => ''])->assertJsonValidationErrors(['email', 'password']);
    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => str_repeat('x', 1025)])->assertJsonValidationErrors('password');
    expect($user->fresh()->failed_login_attempts)->toBe(0);
});

test('A03 unverified and restricted accounts cannot authenticate', function (AccountStatus $status, string $message) {
    $user = User::factory()->create(['account_status' => $status, 'email_verified_at' => $status === AccountStatus::Unverified ? null : now()]);
    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertJsonValidationErrors('email')->assertJsonPath('errors.email.0', $message);
    $this->assertGuest();
    expect($user->fresh()->last_login_at)->toBeNull();
})->with([
    'unverified' => [AccountStatus::Unverified, 'Verify your email before signing in.'],
    'suspended' => [AccountStatus::Suspended, 'Access to this account is restricted.'],
    'archived' => [AccountStatus::Archived, 'Access to this account is restricted.'],
    'deleted' => [AccountStatus::Deleted, 'Access to this account is restricted.'],
]);

test('A03 wrong passwords do not disclose restricted status', function () {
    $user = User::factory()->create(['account_status' => AccountStatus::Suspended]);
    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
        ->assertJsonPath('errors.email.0', 'Invalid email or password.');
});

test('A03 lockouts follow the approved schedule and do not reset on cooldown', function () {
    $this->freezeTime();
    $user = User::factory()->create();
    foreach ([3 => 15, 6 => 60, 9 => 1440, 12 => 1440] as $threshold => $minutes) {
        while ($user->fresh()->failed_login_attempts < $threshold) {
            $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        }
        expect($user->fresh()->login_locked_until->timestamp)->toBe(now()->addMinutes($minutes)->timestamp);
        $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertJsonPath('errors.email.0', 'This account is temporarily locked. Try again after the cooldown.');
        expect($user->fresh()->failed_login_attempts)->toBe($threshold);
        $this->travel($minutes)->minutes();
    }
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    expect($user->fresh()->failed_login_attempts)->toBe(0)->and($user->fresh()->login_locked_until)->toBeNull();
});

test('A03 login rehashes an outdated password without changing its value', function () {
    $user = User::factory()->create();
    Hash::driver()->setRounds(5);
    $oldHash = $user->password;
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
    expect($user->fresh()->password)->not->toBe($oldHash)->and(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('A03 failures never flash passwords or grant privileges from login input', function () {
    $user = User::factory()->create();
    $this->from(route('login'))->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    expect(session()->getOldInput('password'))->toBeNull();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'account_role' => 'Admin', 'redirect' => 'https://example.test'])
        ->assertRedirect(route('dashboard'));
    expect($user->fresh()->account_role)->toBe(Role::Learner);
});

test('A03 failed login transaction leaves neither session nor security state', function () {
    $user = User::factory()->create();
    User::saving(function (User $account): void {
        if ($account->isDirty('active_session_hash')) {
            throw new RuntimeException('Test persistence failure.');
        }
    });
    try {
        $this->withoutExceptionHandling();
        expect(fn () => $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']))
            ->toThrow(RuntimeException::class, 'Test persistence failure.');
        $this->assertGuest();
        expect($user->fresh()->last_login_at)->toBeNull()->and($user->fresh()->active_session_hash)->toBeNull();
    } finally {
        User::flushEventListeners();
    }
});

test('A03 sign out clears the current session and allows a new login', function () {
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $oldToken = session()->token();
    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
    expect($user->fresh()->active_session_hash)->toBeNull()->and(session()->token())->not->toBe($oldToken);
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->app['auth']->forgetGuards();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
});

test('A03 suspended accounts and replaced or expired sessions lose protected access', function (string $change) {
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withCookie(config('session.cookie'), session()->getId());
    $update = match ($change) {
        'suspended' => ['account_status' => AccountStatus::Suspended],
        'replaced' => ['active_session_hash' => hash('sha256', 'another-session')],
        'expired' => ['active_session_expires_at' => now()],
        'unverified' => ['account_status' => AccountStatus::Unverified, 'email_verified_at' => null],
    };
    $user->forceFill($update)->save();
    $this->withCredentials()->getJson(route('dashboard'))->assertUnauthorized();
    $this->assertGuest();
})->with(['suspended', 'replaced', 'expired', 'unverified']);

test('A03 authenticated users cannot login again and session secrets are hidden', function () {
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->get(route('login'))->assertRedirect(route('dashboard'));
    expect($user->fresh()->toArray())->not->toHaveKeys(['active_session_hash', 'active_session_expires_at', 'password', 'failed_login_attempts']);
});

test('A03 login and logout enforce CSRF outside the testing bypass', function () {
    $this->app['env'] = 'production';
    $this->post(route('login.store'), ['email' => 'user@example.test', 'password' => 'password'])->assertStatus(419);
    $this->post(route('login.confirm'), ['choice' => 'continue'])->assertStatus(419);
    $this->post(route('logout'))->assertStatus(419);
});

test('A03 login endpoints have independent IP throttles', function () {
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->postJson(route('login.store'), [])->assertUnprocessable();
    }
    $this->postJson(route('login.store'), [])->assertTooManyRequests();
    $this->postJson(route('login.confirm'), [])->assertUnprocessable();
});

test('A03 login security constraints reject invalid persistent state', function () {
    $user = User::factory()->create();
    expect(fn () => DB::transaction(fn () => DB::table('users')->where('id', $user->id)->update(['failed_login_attempts' => -1])))
        ->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('users')->where('id', $user->id)->update(['active_session_hash' => str_repeat('a', 64)])))
        ->toThrow(QueryException::class);
});
