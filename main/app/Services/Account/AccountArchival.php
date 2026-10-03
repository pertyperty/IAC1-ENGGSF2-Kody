<?php

namespace App\Services\Account;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Support\AccountPasswords;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AccountArchival
{
    public function archive(User $actor, string $sessionId, #[\SensitiveParameter] array $data): void
    {
        try {
            DB::transaction(function () use ($actor, $sessionId, $data): void {
                $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                Gate::forUser($user)->authorize('archive', $user);
                if ($user->profile_version !== (int) $data['profile_version']) {
                    throw ValidationException::withMessages(['profile_version' => 'Your account changed. Reload before archiving.']);
                }
                if (! in_array($data['confirmed'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)
                    || ! AccountPasswords::matches($data['current_password'] ?? '', $user->password)) {
                    throw ValidationException::withMessages(['current_password' => 'Confirm archiving and enter your current password.']);
                }
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                app(AccountSecurity::class)->revoke($user);
                $user->forceFill(['account_status' => AccountStatus::Archived, 'profile_version' => $user->profile_version + 1])->save();
                app(AuditRecorder::class)->record($user->id, $user->id, 'account.archived', 'user', (string) $user->id,
                    ['previous_status' => 'Active', 'status' => 'Archived', 'version' => $user->profile_version]);
            });
        } catch (QueryException $exception) {
            Log::error('Account archival write failed.', ['sqlstate' => $exception->getCode()]);
            throw new RuntimeException('Your account could not be archived. Please try again.');
        }
    }
}
