<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

test('setup status is secret free and does not contact or enable providers', function () {
    Http::preventStrayRequests();
    Mail::fake();
    config(['services.google.enabled' => false, 'services.google.client_id' => 'PRIVATE_CLIENT_ID', 'services.google.client_secret' => 'PRIVATE_GOOGLE_SECRET',
        'services.google.redirect' => 'https://example.test/auth/google/callback', 'judge0.enabled' => false,
        'judge0.rapidapi_key' => 'PRIVATE_JUDGE_KEY', 'mail.mailers.sendgrid.password' => 'PRIVATE_MAIL_KEY']);
    $this->withoutMockingConsoleOutput();
    expect(Artisan::call('kody:setup-status'))->toBe(0);
    $output = Artisan::output();
    expect($output)->toContain('Configuration presence is not live verification');
    foreach (['PRIVATE_CLIENT_ID', 'PRIVATE_GOOGLE_SECRET', 'PRIVATE_JUDGE_KEY', 'PRIVATE_MAIL_KEY'] as $secret) {
        expect($output)->not->toContain($secret);
    }
    Http::assertNothingSent();
    Mail::assertNothingSent();
    expect(config('judge0.enabled'))->toBeFalse()->and(config('services.google.enabled'))->toBeFalse();
});

test('named SendGrid transport uses bounded authenticated SMTP and required TLS without connecting', function () {
    config(['mail.mailers.sendgrid.password' => 'synthetic-private-key']);
    $transport = app('mail.manager')->mailer('sendgrid')->getSymfonyTransport();
    expect($transport)->toBeInstanceOf(EsmtpTransport::class)
        ->and($transport->getUsername())->toBe('apikey')->and($transport->isAutoTls())->toBeTrue()
        ->and($transport->isTlsRequired())->toBeTrue()
        ->and($transport->getStream()->getTimeout())->toBe(10.0);
});
