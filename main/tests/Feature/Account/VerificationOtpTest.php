<?php

use App\Enums\AccountStatus;
use App\Models\EmailVerification;
use App\Services\Account\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('A02 six digit codes are encrypted in delivery and activate exactly once', function () {
    [$user, $delivery] = pendingVerification();
    $code = $delivery->otp;
    expect($code)->toMatch('/^[0-9]{6}$/');
    expect(DB::table('verification_deliveries')->where('id', $delivery->id)->value('otp'))->not->toBe($code);
    expect(json_encode($delivery))->not->toContain($code, $delivery->token);
    $this->postJson(route('verification.code'), ['email' => mb_strtoupper($user->email), 'code' => $code])->assertOk();
    expect($user->fresh()->account_status)->toBe(AccountStatus::Active);
    $this->postJson(route('verification.code'), ['email' => $user->email, 'code' => $code])->assertUnprocessable();
    expect($delivery->fresh()->otp)->toBeNull();
});

test('A02 expiry wrong attempts and resend invalidate old codes while preserving the secure link fallback', function () {
    [$user, $delivery, $token] = pendingVerification();
    $service = app(EmailVerificationService::class);
    $wrong = $delivery->otp === '000000' ? '999999' : '000000';
    for ($i = 0; $i < 5; $i++) {
        expect($service->verifyCode($user->email, $wrong))->toBeFalse();
    }
    expect($service->verifyCode($user->email, $delivery->otp))->toBeFalse();
    expect(EmailVerification::where('user_id', $user->id)->sole()->otp_attempts)->toBe(5);
    expect($service->verify($token))->toBeTrue();
    [$other, $old] = pendingVerification();
    $this->travel(11)->minutes();
    expect($service->verifyCode($other->email, $old->otp))->toBeFalse();
    expect($service->request($other))->toBeTrue();
    expect($old->fresh()->otp)->toBeNull();
    expect($service->verifyCode($other->email, $old->otp))->toBeFalse();
});

test('A02 code failures do not disclose account existence or permit unrelated codes', function () {
    [$user, $delivery] = pendingVerification();
    $wrong = $delivery->otp === '000000' ? '999999' : '000000';
    $known = $this->postJson(route('verification.code'), ['email' => $user->email, 'code' => $wrong])->assertUnprocessable()->json('message');
    $unknown = $this->postJson(route('verification.code'), ['email' => 'unknown@example.test', 'code' => $delivery->otp])->assertUnprocessable()->json('message');
    expect($known)->toBe($unknown);
    expect($user->fresh()->account_status)->toBe(AccountStatus::Unverified);
});

test('code entry keeps the email on retry and accepts a formatted copy of the email code', function () {
    [$user, $delivery] = pendingVerification();
    $wrong = $delivery->otp === '000000' ? '999999' : '000000';
    $this->post(route('verification.code'), ['email' => $user->email, 'code' => $wrong])
        ->assertUnprocessable()->assertViewIs('account.verify-email')->assertSee($user->email)
        ->assertSee('This code is invalid')->assertDontSee($wrong)->assertSessionMissing('_old_input.code');
    $formatted = substr($delivery->otp, 0, 2)." \u{00a0}".substr($delivery->otp, 2, 2).'-'.substr($delivery->otp, 4);
    $this->post(route('verification.code'), ['email' => $user->email, 'code' => $formatted])
        ->assertOk()->assertViewHas('verified', true)->assertDontSee($formatted);
    expect($user->fresh()->account_status)->toBe(AccountStatus::Active);
});
