<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\LoginAccount;
use App\Enums\LoginOutcome;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\GoogleLinkRequest;
use App\Services\Account\GoogleAuthentication;
use App\Services\Account\GoogleOAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class GoogleAuthenticationController extends Controller
{
    public function show(Request $request, GoogleAuthentication $google, GoogleOAuth $provider): Response
    {
        return response()->view('account.google', ['identity' => $google->state($request->user()), 'available' => $provider->available()])
            ->header('Cache-Control', 'no-store, private');
    }

    public function start(Request $request, GoogleAuthentication $google): RedirectResponse
    {
        try {
            return $this->protectedRedirect($google->start($request));
        } catch (Throwable) {
            return $this->failure($request);
        }
    }

    public function link(GoogleLinkRequest $request, GoogleAuthentication $google): RedirectResponse
    {
        // Validation errors stay on the management page; no OAuth query is accepted here.
        try {
            return $this->protectedRedirect($google->start($request, $request->validated()));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            return $this->failure($request);
        }
    }

    public function callback(Request $request, GoogleAuthentication $google, GoogleOAuth $provider, LoginAccount $login): RedirectResponse
    {
        try {
            $attempt = $google->claim($request);
            $code = $request->query('code');
            if ($request->query('error') !== null || ! is_string($code) || strlen($code) > 4096 || $code === '') {
                throw new \RuntimeException('Google authorization was not completed.');
            }
            $subject = $provider->identity($request);
            if ($attempt->intent === 'link') {
                $google->link($request, $attempt, $subject);

                return $this->signedOut($request, 'Google account linked. Sign in again to continue.');
            }
            if ($request->user() !== null) {
                throw new \RuntimeException('Start sign-in from a signed-out session.');
            }
            $outcome = $login->fromGoogle($subject, $request->session());
            if ($outcome === LoginOutcome::Authenticated) {
                return $this->protectedRedirect(redirect()->route($request->user()->account_role === Role::Learner ? 'home' : 'dashboard'));
            }
            if ($outcome === LoginOutcome::Conflict) {
                return $this->protectedRedirect(redirect()->route('login.confirmation'));
            }

            return $this->failure($request);
        } catch (Throwable) {
            return $this->failure($request);
        } finally {
            $request->session()->forget(['google_attempt_id', 'state', 'code_verifier']);
        }
    }

    public function unlink(GoogleLinkRequest $request, GoogleAuthentication $google): RedirectResponse
    {
        $google->unlink($request, $request->validated());

        return $this->signedOut($request, 'Google account unlinked. Use your Kody password to sign in.');
    }

    private function signedOut(Request $request, string $message): RedirectResponse
    {
        Auth::guard()->forgetUser();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->protectedRedirect(redirect()->route('login')->with('status', $message));
    }

    private function failure(Request $request): RedirectResponse
    {
        $request->session()->forget(['google_attempt_id', 'state', 'code_verifier']);

        return $this->protectedRedirect(redirect()->route($request->user() === null ? 'login' : 'account.google')
            ->with('status', 'Google sign-in could not be completed. Start again or use your Kody password.'));
    }

    private function protectedRedirect(RedirectResponse $response): RedirectResponse
    {
        return $response->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }
}
