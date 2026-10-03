<?php

namespace App\Actions\Account;

use App\Enums\AccountStatus;
use App\Enums\LoginOutcome;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class LoginAccount
{
    public function attempt(string $email, #[\SensitiveParameter] string $password, Session $session): LoginOutcome
    {
        $session->forget('login_confirmation');

        return $this->transaction($session, function () use ($email, $password, $session): LoginOutcome {
            $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->lockForUpdate()->first();
            if ($user === null) {
                // Perform password work for missing accounts too; never authenticate a dummy identity.
                Hash::check($password, Hash::make(Str::random(40)));

                return LoginOutcome::Invalid;
            }
            if ($user->login_locked_until?->isFuture()) {
                return LoginOutcome::Locked;
            }
            if (! $this->verifyPassword($password, $user->password)) {
                $attempts = min($user->failed_login_attempts + 1, 2147483647);
                $minutes = match (true) {
                    $attempts >= 9 => 1440,
                    $attempts >= 6 => 60,
                    default => 15,
                };
                $lockedUntil = $attempts % 3 === 0 ? now()->addMinutes($minutes) : null;
                $user->forceFill(['failed_login_attempts' => $attempts, 'login_locked_until' => $lockedUntil])->save();

                return $lockedUntil !== null ? LoginOutcome::Locked : LoginOutcome::Invalid;
            }
            $status = $this->eligibility($user);
            if ($status !== null) {
                return $status;
            }
            if ($user->active_session_hash !== null && $user->active_session_expires_at?->isFuture()) {
                $session->regenerate(true);
                $session->put('login_confirmation', [
                    'user_id' => $user->id,
                    'password_digest' => hash('sha256', $user->password),
                    'session_hash' => $user->active_session_hash,
                    'expires_at' => now()->addMinutes(5)->timestamp,
                ]);

                return LoginOutcome::Conflict;
            }
            if (Hash::needsRehash($user->password)) {
                $user->password = $password;
            }

            return $this->authenticate($user, $session);
        });
    }

    public function confirm(Session $session): LoginOutcome
    {
        $confirmation = $session->pull('login_confirmation');
        if (! is_array($confirmation) || ($confirmation['expires_at'] ?? 0) <= now()->timestamp) {
            return LoginOutcome::Invalid;
        }

        return $this->transaction($session, function () use ($confirmation, $session): LoginOutcome {
            $user = User::whereKey($confirmation['user_id'])->lockForUpdate()->first();
            if ($user === null || ! hash_equals($confirmation['password_digest'], hash('sha256', $user->password))
                || $confirmation['session_hash'] !== $user->active_session_hash) {
                return LoginOutcome::Invalid;
            }
            if ($user->login_locked_until?->isFuture()) {
                return LoginOutcome::Locked;
            }
            $status = $this->eligibility($user);

            return $status ?? $this->authenticate($user, $session);
        });
    }

    public function logout(Session $session): void
    {
        DB::transaction(function () use ($session): void {
            $user = User::whereKey(Auth::id())->lockForUpdate()->first();
            if ($user !== null && $user->active_session_hash !== null
                && hash_equals($user->active_session_hash, hash('sha256', $session->getId()))) {
                $user->forceFill(['active_session_hash' => null, 'active_session_expires_at' => null])->save();
            }
        });
        Auth::logout();
        $session->invalidate();
        $session->regenerateToken();
    }

    private function eligibility(User $user): ?LoginOutcome
    {
        if ($user->account_status === AccountStatus::Unverified) {
            return LoginOutcome::Unverified;
        }
        if ($user->account_status !== AccountStatus::Active) {
            return LoginOutcome::Restricted;
        }

        return $user->email_verified_at === null ? LoginOutcome::Unverified : null;
    }

    private function verifyPassword(#[\SensitiveParameter] string $password, string $hash): bool
    {
        // Explicitly permit Laravel's supported legacy hashes during an algorithm upgrade.
        $driver = match (password_get_info($hash)['algoName']) {
            'bcrypt' => 'bcrypt',
            'argon2i' => 'argon',
            'argon2id' => 'argon2id',
            default => null,
        };

        return $driver !== null && Hash::driver($driver)->check($password, $hash);
    }

    private function authenticate(User $user, Session $session): LoginOutcome
    {
        // SessionGuard rotates the session ID and CSRF token before recording its fingerprint.
        Auth::login($user);
        $user->forceFill([
            'failed_login_attempts' => 0,
            'login_locked_until' => null,
            'last_login_at' => now(),
            'active_session_hash' => hash('sha256', $session->getId()),
            'active_session_expires_at' => now()->addMinutes((int) config('session.lifetime')),
        ])->save();

        return LoginOutcome::Authenticated;
    }

    private function transaction(Session $session, \Closure $operation): LoginOutcome
    {
        try {
            return DB::transaction($operation);
        } catch (Throwable $exception) {
            // A rolled-back login must never leave an authenticated in-memory session.
            Auth::guard()->forgetUser();
            $session->invalidate();
            $session->regenerateToken();
            throw $exception;
        }
    }
}
