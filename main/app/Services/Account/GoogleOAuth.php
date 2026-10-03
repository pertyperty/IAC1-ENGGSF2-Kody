<?php

namespace App\Services\Account;

use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\GoogleProvider;
use RuntimeException;
use Throwable;

class GoogleOAuth
{
    public function __construct(private ?Client $http = null) {}

    public function available(): bool
    {
        $settings = config('services.google');
        if (! ($settings['enabled'] ?? false) || empty($settings['client_id']) || empty($settings['client_secret'])) {
            return false;
        }
        $redirect = parse_url($settings['redirect'] ?? '');
        $app = parse_url(config('app.url'));
        if (! is_array($redirect) || ! is_array($app)
            || isset($redirect['query']) || isset($redirect['fragment']) || isset($redirect['user']) || isset($redirect['pass'])
            || ($redirect['path'] ?? '') !== '/auth/google/callback') {
            return false;
        }
        foreach (['scheme', 'host', 'port'] as $part) {
            if (($redirect[$part] ?? null) !== ($app[$part] ?? null)) {
                return false;
            }
        }

        return ($redirect['scheme'] ?? '') === 'https'
            || (app()->environment(['local', 'testing']) && ($redirect['scheme'] ?? '') === 'http'
                && in_array($redirect['host'] ?? '', ['localhost', '127.0.0.1', '[::1]'], true));
    }

    public function redirect(Request $request): RedirectResponse
    {
        return $this->provider($request)->with(['prompt' => 'select_account'])->redirect();
    }

    public function identity(Request $request): string
    {
        try {
            $identity = $this->provider($request)->user();
            $subject = $identity->getId();
            $email = $identity->getEmail();
            if (! is_string($subject) || ! preg_match('/\A[\x21-\x7e]{1,255}\z/D', $subject)
                || ($identity->user['sub'] ?? null) !== $subject || ($identity->user['email_verified'] ?? null) !== true
                || ! is_string($email) || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException('Invalid Google identity.');
            }

            return hash('sha256', "google\0".$subject);
        } catch (Throwable) {
            // Provider exceptions can contain authorization codes, bearer tokens and response bodies.
            throw new RuntimeException('Google sign-in could not be verified. Start again.');
        }
    }

    private function provider(Request $request): GoogleProvider
    {
        if (! $this->available()) {
            throw new RuntimeException('Google sign-in is not configured.');
        }
        $provider = new GoogleProvider($request, config('services.google.client_id'), config('services.google.client_secret'), config('services.google.redirect'));
        $provider->setHttpClient($this->http ?? new Client(['timeout' => 8, 'connect_timeout' => 3, 'allow_redirects' => false]));

        return $provider->setScopes(['openid', 'email'])->enablePKCE();
    }
}
