<?php

namespace App\Jobs\Account;

use App\Mail\Account\RecoveryLink;
use App\Models\AccountRecovery;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Account\AccountRecoveryService;
use App\Services\Account\SecureAccountMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendRecoveryEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly string $deliveryId) {}

    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(): void
    {
        $accountId = VerificationDelivery::whereKey($this->deliveryId)->where('purpose', 'recovery')->value('user_id');
        if ($accountId === null) {
            return;
        }
        $failed = DB::transaction(function () use ($accountId): bool {
            $user = User::whereKey($accountId)->lockForUpdate()->first();
            $delivery = VerificationDelivery::whereKey($this->deliveryId)->lockForUpdate()->first();
            $recovery = AccountRecovery::where('user_id', $accountId)->first();
            if ($delivery === null || $delivery->sent_at !== null || $delivery->cancelled_at !== null) {
                return false;
            }
            if ($user === null || $recovery === null || ! app(AccountRecoveryService::class)->valid($user, $recovery, $delivery->token_hash)) {
                $delivery->update(['token' => null, 'cancelled_at' => now()]);

                return false;
            }
            try {
                $url = rtrim(config('app.url'), '/').route('recovery.request', absolute: false).'#recovery='.$delivery->token;
                app(SecureAccountMailer::class)->send('recovery', $user->email, new RecoveryLink($url, $delivery->token));
                $delivery->update(['token' => null, 'sent_at' => now(), 'failed_at' => null]);

                return false;
            } catch (Throwable) {
                $delivery->update(['failed_at' => now()]);

                return true;
            }
        });
        if ($failed) {
            throw new RuntimeException('Recovery email could not be delivered.');
        }
    }
}
