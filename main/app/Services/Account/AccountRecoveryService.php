<?php

namespace App\Services\Account;

use App\Enums\AccountStatus;
use App\Jobs\Account\SendRecoveryEmail;
use App\Models\AccountRecovery;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Support\AccountPasswords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class AccountRecoveryService
{
    public function eligible(User $user): bool
    {
        return in_array($user->account_status, [AccountStatus::Active, AccountStatus::Archived], true)
            && $user->email_verified_at !== null;
    }

    public function request(User $account): bool
    {
        return DB::transaction(function () use ($account): bool {
            $user = User::whereKey($account->id)->lockForUpdate()->firstOrFail();
            if (! $this->eligible($user)) {
                return false;
            }
            $recovery = AccountRecovery::where('user_id', $user->id)->first();
            $newWindow = $recovery === null || $recovery->window_started_at->addHour()->lessThanOrEqualTo(now());
            if ($recovery !== null && ($recovery->last_requested_at->addSeconds(config('account.recovery.cooldown_seconds'))->isFuture()
                || (! $newWindow && $recovery->request_count >= config('account.recovery.requests_per_hour')))) {
                return false;
            }
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            AccountRecovery::updateOrCreate(['user_id' => $user->id], [
                'email' => $user->email,
                'password_digest' => hash('sha256', $user->password),
                'token_hash' => $hash,
                'expires_at' => now()->addMinutes(config('account.recovery.expires_minutes')),
                'last_requested_at' => now(),
                'window_started_at' => $newWindow ? now() : $recovery->window_started_at,
                'request_count' => $newWindow ? 1 : $recovery->request_count + 1,
            ]);
            $this->cancelDeliveries($user->id);
            $delivery = VerificationDelivery::create([
                'id' => (string) Str::uuid(), 'user_id' => $user->id,
                'purpose' => 'recovery', 'token_hash' => $hash, 'token' => $token,
            ]);
            if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
                throw new LogicException('Recovery delivery requires the application database queue.');
            }
            Queue::connection('database')->push((new SendRecoveryEmail($delivery->id))->beforeCommit());

            return true;
        });
    }

    public function authorization(string $token): ?array
    {
        if (! preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            return null;
        }
        $hash = hash('sha256', $token);
        $recovery = AccountRecovery::where('token_hash', $hash)->first();
        $user = $recovery === null ? null : User::find($recovery->user_id);
        if ($user === null || ! $this->valid($user, $recovery, $hash)) {
            return null;
        }

        return ['user_id' => $user->id, 'token_hash' => $hash, 'expires_at' => min($recovery->expires_at->timestamp, now()->addMinutes(5)->timestamp)];
    }

    public function valid(User $user, AccountRecovery $recovery, string $hash): bool
    {
        return $this->eligible($user) && $recovery->token_hash !== null && hash_equals($recovery->token_hash, $hash)
            && $recovery->email === $user->email && hash_equals($recovery->password_digest, hash('sha256', $user->password))
            && $recovery->expires_at !== null && $recovery->expires_at->isFuture();
    }

    public function complete(?array $authorization, #[\SensitiveParameter] string $password): bool
    {
        if ($authorization === null || ($authorization['expires_at'] ?? 0) <= now()->timestamp) {
            return false;
        }

        return DB::transaction(function () use ($authorization, $password): bool {
            $user = User::whereKey($authorization['user_id'])->lockForUpdate()->first();
            $recovery = AccountRecovery::where('user_id', $authorization['user_id'])->first();
            if ($user === null || $recovery === null || ! $this->valid($user, $recovery, $authorization['token_hash'])) {
                return false;
            }
            if ($user->account_status === AccountStatus::Archived && AccountPasswords::matches($password, $user->password)) {
                throw ValidationException::withMessages(['password' => 'Choose a different password to reactivate your account.']);
            }
            if (config('session.driver') === 'database'
                && (config('session.connection') ?? config('database.default')) !== config('database.default')) {
                throw new LogicException('Recovery requires database sessions on the application database.');
            }
            $user->forceFill([
                'password' => $password, 'remember_token' => Str::random(60),
                'account_status' => AccountStatus::Active, 'failed_login_attempts' => 0,
                'login_locked_until' => null, 'active_session_hash' => null, 'active_session_expires_at' => null,
            ])->save();
            $recovery->update(['token_hash' => null, 'expires_at' => null]);
            $this->cancelDeliveries($user->id);
            if (config('session.driver') === 'database') {
                // The fingerprint also revokes sessions on other supported stores.
                DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $user->id)->delete();
            }

            return true;
        });
    }

    private function cancelDeliveries(int $userId): void
    {
        VerificationDelivery::where('user_id', $userId)->where('purpose', 'recovery')->whereNull('sent_at')->whereNull('cancelled_at')
            ->update(['token' => null, 'cancelled_at' => now()]);
    }
}
