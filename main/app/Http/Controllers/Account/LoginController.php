<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\LoginAccount;
use App\Enums\LoginOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ConfirmLoginRequest;
use App\Http\Requests\Account\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create(): Response
    {
        return response()->view('account.login')->header('Cache-Control', 'no-store');
    }

    public function store(LoginRequest $request, LoginAccount $login): JsonResponse|RedirectResponse
    {
        return $this->respond($request, $login->attempt($request->validated('email'), $request->validated('password'), $request->session()));
    }

    public function confirmation(Request $request): Response|RedirectResponse
    {
        if ($request->session()->get('login_confirmation.expires_at', 0) <= now()->timestamp) {
            $request->session()->forget('login_confirmation');

            return redirect()->route('login')->with('status', 'Sign in again to confirm another session.');
        }

        return response()->view('account.confirm-login')->header('Cache-Control', 'no-store');
    }

    public function confirm(ConfirmLoginRequest $request, LoginAccount $login): JsonResponse|RedirectResponse
    {
        if ($request->validated('choice') === 'cancel') {
            $request->session()->forget('login_confirmation');

            return redirect()->route('login')->with('status', 'Sign-in cancelled. The existing session remains active.');
        }

        return $this->respond($request, $login->confirm($request->session()));
    }

    public function destroy(Request $request, LoginAccount $login): RedirectResponse
    {
        $login->logout($request->session());

        return redirect()->route('login')->with('status', 'You are signed out.');
    }

    private function respond(Request $request, LoginOutcome $outcome): JsonResponse|RedirectResponse
    {
        if ($outcome === LoginOutcome::Authenticated) {
            return $request->expectsJson() ? response()->json(['redirect' => route('dashboard')]) : redirect()->route('dashboard');
        }
        if ($outcome === LoginOutcome::Conflict) {
            return $request->expectsJson()
                ? response()->json(['message' => $outcome->message(), 'redirect' => route('login.confirmation')], 409)
                : redirect()->route('login.confirmation');
        }

        throw ValidationException::withMessages(['email' => $outcome->message()]);
    }
}
