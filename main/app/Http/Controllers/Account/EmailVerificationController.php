<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ResendVerificationRequest;
use App\Models\User;
use App\Services\Account\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailVerificationController extends Controller
{
    public function notice(): Response
    {
        return response()->view('account.verify-email')
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function resend(ResendVerificationRequest $request, EmailVerificationService $verification): JsonResponse|RedirectResponse
    {
        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($request->validated('email'))])->first();
        if ($user !== null) {
            $verification->request($user);
        }

        // Keep unknown, active, exhausted and cooling-down accounts indistinguishable.
        $message = 'If this account is awaiting verification and a request is available, a new link will be sent. Wait at least one minute between requests.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function verify(Request $request, EmailVerificationService $verification): JsonResponse|Response
    {
        $request->validate(['verification_token' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/']]);
        $token = $request->string('verification_token')->toString();
        $verified = $verification->verify($token);
        $message = $verified ? 'Your email is verified. Your account is now active.' : 'This verification link is invalid, expired or already used. You can request a new link.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $verified ? 200 : 422)
                ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
        }

        return response()->view('account.verification-result', compact('verified', 'message'), $verified ? 200 : 422)
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }
}
