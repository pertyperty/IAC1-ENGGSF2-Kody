<?php

namespace App\Services\Transactions;

use App\Enums\Role;
use App\Models\LearningCourse;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Support\AccountPasswords;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FinancialAccounts
{
    public function lock(User $actor, string $sessionId, bool $staff = false): User
    {
        $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
        app(CurrentAccountSession::class)->assert($user, $sessionId);
        if ($staff) {
            abort_unless($user->account_role === Role::Administrator, 403);
        } else {
            Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
        }

        return $user;
    }

    public function confirm(User $user, #[\SensitiveParameter] array $data): void
    {
        if (! in_array($data['confirmed'] ?? null, [true, 1, '1', 'on', 'yes', 'true'], true)
            || ! AccountPasswords::matches($data['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Confirm this action with your current password.']);
        }
    }
}
