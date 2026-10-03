<?php

namespace App\Services\Account;

use App\Enums\AccountStatus;
use App\Jobs\Account\SendVerificationEmail;
use App\Models\EmailVerification;
use App\Models\User;
use App\Models\VerificationDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use LogicException;

class EmailVerificationService
{
    public function request(User $account): bool
    {
        return DB::transaction(function () use ($account): bool {
            $user = User::whereKey($account->id)->lockForUpdate()->firstOrFail();

            if ($user->account_status !== AccountStatus::Unverified || $user->email_verified_at !== null) {
                return false;
            }

            $verification = EmailVerification::firstOrCreate(['user_id' => $user->id], ['email' => $user->email]);

            if ($verification->request_count >= 5 || $verification->last_requested_at?->addMinute()->isFuture()) {
                return false;
            }

            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $verification->update([
                'email' => $user->email,
                'token_hash' => $hash,
                'expires_at' => now()->addMinutes(config('account.verification.expires_minutes')),
                'request_count' => $verification->request_count + 1,
                'last_requested_at' => now(),
            ]);

            VerificationDelivery::where('user_id', $user->id)->where('purpose', 'verification')->whereNull('sent_at')->whereNull('cancelled_at')
                ->update(['token' => null, 'cancelled_at' => now()]);

            $delivery = VerificationDelivery::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'token_hash' => $hash,
                'token' => $token,
            ]);

            $queueDatabase = config('queue.connections.database.connection') ?? config('database.default');
            if ($queueDatabase !== config('database.default')) {
                throw new LogicException('Verification delivery requires the application database queue connection.');
            }

            // Persist pending work inside this transaction, rather than after commit.
            Queue::connection('database')->push((new SendVerificationEmail($delivery->id))->beforeCommit());

            return true;
        });
    }

    public function verify(string $token): bool
    {
        if (! preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            return false;
        }

        $hash = hash('sha256', $token);
        $accountId = EmailVerification::where('token_hash', $hash)->value('user_id');
        if ($accountId === null) {
            return false;
        }

        return DB::transaction(function () use ($accountId, $hash): bool {
            $user = User::whereKey($accountId)->lockForUpdate()->first();
            $verification = EmailVerification::where('user_id', $accountId)->first();

            if ($user === null || $verification === null || $user->account_status !== AccountStatus::Unverified
                || $user->email_verified_at !== null || $verification->token_hash === null
                || ! hash_equals($verification->token_hash, $hash) || $verification->email !== $user->email
                || $verification->expires_at === null || $verification->expires_at->lessThanOrEqualTo(now())) {
                return false;
            }

            $user->forceFill(['account_status' => AccountStatus::Active, 'email_verified_at' => now()])->save();
            $verification->update(['token_hash' => null, 'expires_at' => null]);
            VerificationDelivery::where('user_id', $accountId)->where('purpose', 'verification')->whereNull('sent_at')->whereNull('cancelled_at')
                ->update(['token' => null, 'cancelled_at' => now()]);

            return true;
        });
    }
}
