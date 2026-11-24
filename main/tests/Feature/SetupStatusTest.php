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
        'judge0.rapidapi_key' => 'PRIVATE_JUDGE_KEY', 'mail.mailers.mailgun.username' => 'postmaster@private.example.test',
        'mail.mailers.mailgun.password' => 'PRIVATE_MAIL_KEY']);
    $this->withoutMockingConsoleOutput();
    expect(Artisan::call('kody:setup-status'))->toBe(0);
    $output = Artisan::output();
    expect($output)->toContain('Configuration presence is not live verification');
    foreach (['PRIVATE_CLIENT_ID', 'PRIVATE_GOOGLE_SECRET', 'PRIVATE_JUDGE_KEY', 'PRIVATE_MAIL_KEY', 'postmaster@private.example.test'] as $secret) {
        expect($output)->not->toContain($secret);
    }
    Http::assertNothingSent();
    Mail::assertNothingSent();
    expect(config('judge0.enabled'))->toBeFalse()->and(config('services.google.enabled'))->toBeFalse();
});

test('named Mailgun transport uses bounded authenticated SMTP and required TLS without connecting', function () {
    config([
        'mail.mailers.mailgun.host' => 'smtp.mailgun.org',
        'mail.mailers.mailgun.port' => 587,
        'mail.mailers.mailgun.username' => 'postmaster@sandbox.example.test',
        'mail.mailers.mailgun.password' => 'synthetic-private-password',
    ]);
    $transport = app('mail.manager')->mailer('mailgun')->getSymfonyTransport();
    expect($transport)->toBeInstanceOf(EsmtpTransport::class)
        ->and($transport->getUsername())->toBe('postmaster@sandbox.example.test')->and($transport->isAutoTls())->toBeTrue()
        ->and($transport->isTlsRequired())->toBeTrue()
        ->and($transport->getStream()->getTimeout())->toBe(10.0);
});
