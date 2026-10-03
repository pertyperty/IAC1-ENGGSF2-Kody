<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\SendRecoveryEmail;
use App\Mail\Account\RecoveryLink;
use App\Models\AccountRecovery;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Account\AccountRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function recoveryProof(User $user): array
{
    $service = app(AccountRecoveryService::class);
    expect($service->request($user))->toBeTrue();
    $token = VerificationDelivery::where('purpose', 'recovery')->latest()->firstOrFail()->token;

    return [$token, $service->authorization($token)];
}

test('A04 requests do not disclose account existence or eligibility', function () {
    $eligible = User::factory()->create();
    $blocked = User::factory()->create(['account_status' => AccountStatus::Suspended]);
    $responses = [];
    foreach (['missing@example.test', $blocked->email, strtoupper($eligible->email)] as $email) {
        $responses[] = $this->postJson(route('recovery.send'), compact('email'))->assertOk()->json();
    }
    expect($responses[0])->toBe($responses[1])->toBe($responses[2]);
    $this->assertDatabaseCount('account_recoveries', 1);
    $this->assertDatabaseCount('jobs', 1);
});

test('A04 verified Active and Archived accounts of every role can recover', function (Role $role, AccountStatus $status) {
    $user = User::factory()->create(['account_role' => $role, 'account_status' => $status, 'failed_login_attempts' => 9, 'login_locked_until' => now()->addDay()]);
    [$token, $proof] = recoveryProof($user);
    $this->post(route('recovery.authorize'), ['recovery_token' => $token])->assertRedirect(route('recovery.reset'));
    $this->withCookie(config('session.cookie'), session()->getId());
    $this->get(route('recovery.reset'))->assertOk();
    $this->post(route('recovery.complete'), ['password' => 'NewStrongPass12!', 'password_confirmation' => 'NewStrongPass12!', 'user_id' => 999, 'account_role' => 'Administrator'])
        ->assertRedirect(route('login'));
    $this->assertGuest();
    expect(Hash::check('NewStrongPass12!', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->account_status)->toBe(AccountStatus::Active)
        ->and($user->fresh()->account_role)->toBe($role)
        ->and($user->fresh()->failed_login_attempts)->toBe(0)
        ->and($user->fresh()->login_locked_until)->toBeNull()
        ->and(app(AccountRecoveryService::class)->complete($proof, 'OtherStrong12!'))->toBeFalse();
})->with(Role::cases())->with([AccountStatus::Active, AccountStatus::Archived]);

test('A04 blocked or unverified accounts cannot receive recovery tokens', function (AccountStatus $status) {
    $user = User::factory()->create(['account_status' => $status, 'email_verified_at' => $status === AccountStatus::Unverified ? null : now()]);
    expect(app(AccountRecoveryService::class)->request($user))->toBeFalse();
    $this->assertDatabaseCount('account_recoveries', 0);
    $this->assertDatabaseCount('jobs', 0);
})->with([AccountStatus::Suspended, AccountStatus::Deleted, AccountStatus::Unverified]);

test('A04 token expiry and changed security state invalidate recovery', function (string $change) {
    $user = User::factory()->create();
    [$token, $proof] = recoveryProof($user);
    match ($change) {
        'expiry' => AccountRecovery::query()->update(['expires_at' => now()]),
        'email' => $user->update(['email' => 'changed@example.test']),
        'password' => $user->update(['password' => 'ChangedPass12!']),
        'status' => $user->forceFill(['account_status' => AccountStatus::Suspended])->save(),
        'verification' => $user->forceFill(['email_verified_at' => null, 'account_status' => AccountStatus::Unverified])->save(),
    };
    $service = app(AccountRecoveryService::class);
    expect($service->authorization($token))->toBeNull()->and($service->complete($proof, 'NewStrongPass12!'))->toBeFalse();
})->with(['expiry', 'email', 'password', 'status', 'verification']);

test('A04 resend cooldown and hourly cap preserve only the latest token', function () {
    $user = User::factory()->create();
    [$token] = recoveryProof($user);
    $service = app(AccountRecoveryService::class);
    expect($service->request($user))->toBeFalse();
    for ($i = 1; $i < 5; $i++) {
        $this->travel(61)->seconds();
        expect($service->request($user))->toBeTrue();
    }
    $this->travel(61)->seconds();
    expect($service->request($user))->toBeFalse()->and($service->authorization($token))->toBeNull();
    expect(VerificationDelivery::whereNull('cancelled_at')->count())->toBe(1);
    $this->travel(1)->hours();
    expect($service->request($user))->toBeTrue()->and(AccountRecovery::sole()->request_count)->toBe(1);
});

test('A04 reset validation retains proof but never flashes passwords or tokens', function () {
    $user = User::factory()->create();
    [$token, $proof] = recoveryProof($user);
    $this->withSession(['recovery_authorization' => $proof])->from(route('recovery.reset'))
        ->post(route('recovery.complete'), ['password' => 'weak', 'password_confirmation' => 'weak'])
        ->assertSessionHasErrors('password');
    expect(session()->getOldInput('password'))->toBeNull()->and(session()->get('recovery_authorization'))->toBe($proof);
    $this->post(route('recovery.authorize'), ['recovery_token' => str_repeat('a', 65)])->assertSessionHasErrors('recovery_token');
    expect(session()->getOldInput('recovery_token'))->toBeNull();
});

test('A04 Archived accounts must change their password to reactivate', function () {
    $user = User::factory()->create(['account_status' => AccountStatus::Archived, 'password' => 'StrongPass12!']);
    [, $proof] = recoveryProof($user);
    $this->withSession(['recovery_authorization' => $proof])->postJson(route('recovery.complete'), ['password' => 'StrongPass12!', 'password_confirmation' => 'StrongPass12!'])
        ->assertJsonValidationErrors('password');
    expect($user->fresh()->account_status)->toBe(AccountStatus::Archived)->and(AccountRecovery::sole()->token_hash)->not->toBeNull();
});

test('A04 queue failure rolls back recovery and pending delivery', function () {
    $user = User::factory()->create();
    Queue::shouldReceive('connection')->with('database')->andReturnSelf();
    Queue::shouldReceive('push')->andThrow(new RuntimeException('Test queue failure.'));
    expect(fn () => app(AccountRecoveryService::class)->request($user))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('account_recoveries', 0);
    $this->assertDatabaseCount('verification_deliveries', 0);
});

test('A04 password persistence failure leaves the recovery token and sessions intact', function () {
    $user = User::factory()->create(['active_session_hash' => hash('sha256', 'old-session'), 'active_session_expires_at' => now()->addHour()]);
    [, $proof] = recoveryProof($user);
    $hash = $user->password;
    User::saving(function (User $account): void {
        if ($account->isDirty('password')) {
            throw new RuntimeException('Test persistence failure.');
        }
    });
    try {
        expect(fn () => app(AccountRecoveryService::class)->complete($proof, 'NewStrongPass12!'))->toThrow(RuntimeException::class);
        expect($user->fresh()->password)->toBe($hash)->and($user->fresh()->active_session_hash)->not->toBeNull()
            ->and(AccountRecovery::sole()->token_hash)->toBe($proof['token_hash']);
    } finally {
        User::flushEventListeners();
    }
});

test('A04 recovery mail is encrypted at rest and retry safe after successful delivery', function () {
    Mail::fake();
    $user = User::factory()->create();
    [$token] = recoveryProof($user);
    $delivery = VerificationDelivery::sole();
    expect(DB::table('verification_deliveries')->value('token'))->not->toContain($token)
        ->and(DB::table('jobs')->value('payload'))->not->toContain($token);
    $job = new SendRecoveryEmail($delivery->id);
    $job->handle();
    $job->handle();
    Mail::assertSent(RecoveryLink::class, 1);
    Mail::assertSent(RecoveryLink::class, fn ($mail) => $mail->hasTo($user->email) && str_contains($mail->recoveryUrl, '#recovery='.$token));
    expect($delivery->fresh()->token)->toBeNull()->and($delivery->fresh()->sent_at)->not->toBeNull();
});

test('A04 stale deliveries are cancelled and logging transports fail safely', function () {
    Mail::fake();
    $user = User::factory()->create();
    recoveryProof($user);
    $delivery = VerificationDelivery::sole();
    config(['account.recovery.mailer' => 'log']);
    expect(fn () => (new SendRecoveryEmail($delivery->id))->handle())->toThrow(RuntimeException::class, 'Recovery email could not be delivered.');
    expect($delivery->fresh()->failed_at)->not->toBeNull();
    $user->forceFill(['account_status' => AccountStatus::Suspended])->save();
    (new SendRecoveryEmail($delivery->id))->handle();
    expect($delivery->fresh()->cancelled_at)->not->toBeNull()->and($delivery->fresh()->token)->toBeNull();
    Mail::assertNothingSent();
});

test('A04 recovery routes require proof, CSRF and independent rate limits', function () {
    $this->get(route('recovery.reset'))->assertRedirect(route('recovery.request'));
    $this->postJson(route('recovery.complete'), ['password' => 'StrongPass12!', 'password_confirmation' => 'StrongPass12!'])->assertJsonValidationErrors('recovery_token');
    $this->get(route('recovery.request'))->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('Cache-Control', 'no-store, private');
    for ($i = 0; $i < 6; $i++) {
        $this->postJson(route('recovery.send'), [])->assertUnprocessable();
    }
    $this->postJson(route('recovery.send'), [])->assertTooManyRequests();
    $this->postJson(route('recovery.authorize'), [])->assertUnprocessable();
    $this->app['env'] = 'production';
    $this->post(route('recovery.complete'))->assertStatus(419);
});
