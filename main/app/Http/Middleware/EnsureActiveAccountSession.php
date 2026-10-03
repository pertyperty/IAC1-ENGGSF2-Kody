<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccountSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $valid = DB::transaction(function () use ($request): bool {
            $user = User::whereKey($request->user()?->id)->lockForUpdate()->first();
            if ($user === null || $user->account_status !== AccountStatus::Active || $user->email_verified_at === null
                || $user->active_session_hash === null || ! $user->active_session_expires_at?->isFuture()
                || ! hash_equals($user->active_session_hash, hash('sha256', $request->session()->getId()))) {
                return false;
            }
            $user->forceFill(['active_session_expires_at' => now()->addMinutes((int) config('session.lifetime'))])->save();
            Auth::guard()->setUser($user);

            return true;
        });
        if (! $valid) {
            Auth::guard()->forgetUser();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Your session has ended. Sign in again.'], 401)
                : redirect()->route('login')->with('status', 'Your session has ended. Sign in again.');
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
