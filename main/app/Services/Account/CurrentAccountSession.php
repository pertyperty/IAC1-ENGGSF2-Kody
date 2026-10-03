<?php

namespace App\Services\Account;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CurrentAccountSession
{
    // Call with a freshly locked account immediately before changing protected state.
    public function assert(User $user, string $sessionId): void
    {
        if ($user->account_status !== AccountStatus::Active || $user->email_verified_at === null
            || $user->active_session_hash === null || ! hash_equals($user->active_session_hash, hash('sha256', $sessionId))
            || ! $user->active_session_expires_at?->isFuture()) {
            throw new AuthorizationException('Use your current signed-in session.');
        }
    }
}
