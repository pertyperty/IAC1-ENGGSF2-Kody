<?php

use App\Enums\AccountStatus;
use App\Jobs\Account\SendVerificationEmail;
use App\Mail\Account\VerificationLink;
use App\Models\EmailVerification;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Account\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function pendingVerification(): array
{
    $user = User::factory()->unverified()->create();
    app(EmailVerificationService::class)->request($user);
    $delivery = VerificationDelivery::where('user_id', $user->id)->sole();

    return [$user, $delivery, $delivery->token];
}

test('A02 valid links activate accounts once and invalidate the token', function () {
    [$user, $delivery, $token] = pendingVerification();
    $url = route('verification.verify');
    $this->postJson($url, ['verification_token' => $token])->assertOk()->assertHeader('Referrer-Policy', 'no-referrer');
    $user->refresh();
    $verifiedAt = $user->email_verified_at->toISOString();
    expect($user->account_status)->toBe(AccountStatus::Active)
        ->and(EmailVerification::sole()->token_hash)->toBeNull()
        ->and($delivery->fresh()->token)->toBeNull();
    $this->postJson($url, ['verification_token' => $token])->assertUnprocessable();
    expect($user->fresh()->email_verified_at->toISOString())->toBe($verifiedAt);
    $this->assertGuest();
});

test('A02 expired or altered links cannot activate an account', function (bool $expired) {
    [$user, $delivery, $token] = pendingVerification();
    if ($expired) {
        $this->travel(config('account.verification.expires_minutes'))->minutes();
    } else {
        $token = str_repeat('0', 64);
    }
    $this->postJson(route('verification.verify'), ['verification_token' => $token])->assertUnprocessable();
    expect($user->fresh()->account_status)->toBe(AccountStatus::Unverified);
})->with([true, false]);

test('A02 suspended or changed-email accounts cannot be activated by old links', function (bool $suspended) {
    [$user, $delivery, $token] = pendingVerification();
    $user->forceFill($suspended ? ['account_status' => AccountStatus::Suspended] : ['email' => 'changed@example.test'])->save();
    $this->postJson(route('verification.verify'), ['verification_token' => $token])->assertUnprocessable();
    expect($user->fresh()->email_verified_at)->toBeNull();
})->with([true, false]);

test('A02 resends enforce cooldown and five total requests and invalidate older links', function () {
    [$user, $delivery, $token] = pendingVerification();
    $service = app(EmailVerificationService::class);
    expect($service->request($user))->toBeFalse();
    foreach (range(2, 5) as $count) {
        $this->travel(1)->minutes();
        expect($service->request($user))->toBeTrue()
            ->and(EmailVerification::sole()->request_count)->toBe($count);
    }
    $this->travel(1)->minutes();
    expect($service->request($user))->toBeFalse();
    $this->postJson(route('verification.verify'), ['verification_token' => $token])->assertUnprocessable();
    expect($delivery->fresh()->token)->toBeNull()->and($delivery->fresh()->cancelled_at)->not->toBeNull();
    $this->assertDatabaseCount('jobs', 5);
});

test('A02 resend responses do not reveal account existence or eligibility', function () {
    [$user] = pendingVerification();
    $existing = $this->postJson(route('verification.resend'), ['email' => $user->email])->assertOk()->json();
    $unknown = $this->postJson(route('verification.resend'), ['email' => 'unknown@example.test'])->assertOk()->json();
    expect($existing)->toBe($unknown);
    $this->assertDatabaseCount('jobs', 1);
});

test('A02 queued delivery uses trusted URLs and successful retries do not resend', function () {
    Mail::fake();
    config(['app.url' => 'https://kody.example.test', 'account.verification.mailer' => 'array']);
    [$user, $delivery, $token] = pendingVerification();
    $job = new SendVerificationEmail($delivery->id);
    $job->handle();
    $job->handle();
    Mail::assertSent(VerificationLink::class, fn (VerificationLink $mail) => $mail->hasTo($user->email)
        && $mail->verificationUrl === 'https://kody.example.test/email/verify#token='.$token);
    Mail::assertSentCount(1);
    expect($delivery->fresh()->token)->toBeNull()->and($delivery->fresh()->sent_at)->not->toBeNull();
});

test('A02 provider failures preserve accounts and raise only sanitized retry errors', function () {
    config(['account.verification.mailer' => 'array']);
    [$user, $delivery, $token] = pendingVerification();
    Mail::shouldReceive('mailer')->once()->andThrow(new RuntimeException('provider leaked token '.$token));
    expect(fn () => (new SendVerificationEmail($delivery->id))->handle())->toThrow(RuntimeException::class, 'Verification email could not be delivered.');
    expect($user->fresh()->account_status)->toBe(AccountStatus::Unverified)
        ->and($delivery->fresh()->failed_at)->not->toBeNull()
        ->and($delivery->fresh()->token)->toBe($token);
});

test('A02 verification tokens cannot be sent through logging transports', function () {
    config(['account.verification.mailer' => 'log']);
    Mail::fake();
    [, $delivery] = pendingVerification();
    expect(fn () => (new SendVerificationEmail($delivery->id))->handle())->toThrow(RuntimeException::class, 'Verification email could not be delivered.');
    Mail::assertNothingSent();
});

test('A02 superseded delivery jobs do not send obsolete links', function () {
    Mail::fake();
    [$user, $delivery] = pendingVerification();
    $this->travel(1)->minutes();
    app(EmailVerificationService::class)->request($user);
    (new SendVerificationEmail($delivery->id))->handle();
    Mail::assertNothingSent();
});

test('A01 and A02 browser pages render forms and verification results', function () {
    $this->get(route('register'))->assertOk()->assertSee('Create your account');
    $this->get(route('verification.notice'))->assertOk()->assertSee('Check your inbox');
    [, , $token] = pendingVerification();
    $this->post(route('verification.verify'), ['verification_token' => $token])->assertOk()->assertSee('You’re verified');
});
