<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RecoveryEmailRequest;
use App\Http\Requests\Account\ResetPasswordRequest;
use App\Models\User;
use App\Services\Account\AccountRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class RecoveryController extends Controller
{
    public function create(): Response
    {
        return response()->view('account.recover')->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function request(RecoveryEmailRequest $request, AccountRecoveryService $recovery): JsonResponse|RedirectResponse
    {
        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($request->validated('email')))])->first();
        if ($user !== null) {
            $recovery->request($user);
        }
        $message = 'If this account is eligible for recovery, a recovery email will be sent. Check your inbox and spam folder.';
        $cooldown = config('account.recovery.cooldown_seconds');

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'cooldown_seconds' => $cooldown])
            : redirect()->route('recovery.request')->with('status', $message)->with('recovery_cooldown_until', now()->addSeconds($cooldown)->timestamp);
    }

    public function authorizeToken(Request $request, AccountRecoveryService $recovery): JsonResponse|RedirectResponse
    {
        $request->validate(['recovery_token' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/']]);
        $authorization = $recovery->authorization($request->string('recovery_token')->toString());
        if ($authorization === null) {
            $request->session()->forget('recovery_authorization');
            throw ValidationException::withMessages(['recovery_token' => 'This recovery code is invalid, expired or already used. Request another email.']);
        }
        $request->session()->regenerate(true);
        $request->session()->put('recovery_authorization', $authorization);

        return $request->expectsJson()
            ? response()->json(['redirect' => route('recovery.reset')])->header('Cache-Control', 'no-store')
            : redirect()->route('recovery.reset');
    }

    public function resetForm(Request $request): Response|RedirectResponse
    {
        if ($request->session()->get('recovery_authorization.expires_at', 0) <= now()->timestamp) {
            $request->session()->forget('recovery_authorization');

            return redirect()->route('recovery.request')->with('status', 'Open a valid recovery link before choosing a new password.');
        }

        return response()->view('account.reset-password')->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function complete(ResetPasswordRequest $request, AccountRecoveryService $recovery): JsonResponse|RedirectResponse
    {
        $success = $recovery->complete($request->session()->get('recovery_authorization'), $request->validated('password'));
        $request->session()->forget('recovery_authorization');
        if (! $success) {
            throw ValidationException::withMessages(['recovery_token' => 'This recovery code is invalid, expired or already used. Request another email.']);
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $message = 'Your password has changed and previous sessions have ended. Sign in with your new password.';

        return $request->expectsJson()
            ? response()->json(['message' => $message])->header('Cache-Control', 'no-store')
            : redirect()->route('login')->with('status', $message);
    }
}
