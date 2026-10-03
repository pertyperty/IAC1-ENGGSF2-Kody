<?php

namespace App\Jobs\Account;

use App\Enums\AccountStatus;
use App\Mail\Account\EnforcementNotice;
use App\Models\User;
use App\Services\Account\SecureAccountMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendEnforcementNotice implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly string $enforcementId) {}

    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(): void
    {
        $userId = DB::table('account_enforcements')->where('id', $this->enforcementId)->value('user_id');
        if ($userId === null) {
            return;
        }
        $failed = DB::transaction(function () use ($userId): bool {
            $user = User::whereKey($userId)->lockForUpdate()->first();
            $notice = DB::table('account_enforcements')->where('id', $this->enforcementId)->lockForUpdate()->first();
            if ($notice === null || $notice->sent_at !== null || $notice->cancelled_at !== null) {
                return false;
            }
            if ($user === null || $user->account_status === AccountStatus::Deleted || $user->email_verified_at === null) {
                DB::table('account_enforcements')->where('id', $this->enforcementId)->update(['cancelled_at' => now(), 'updated_at' => now()]);

                return false;
            }
            try {
                app(SecureAccountMailer::class)->send('notifications', $user->email,
                    new EnforcementNotice($notice->action, $notice->created_at));
                DB::table('account_enforcements')->where('id', $this->enforcementId)->update(['sent_at' => now(), 'failed_at' => null, 'updated_at' => now()]);

                return false;
            } catch (Throwable) {
                DB::table('account_enforcements')->where('id', $this->enforcementId)->update(['failed_at' => now(), 'updated_at' => now()]);

                return true;
            }
        });
        if ($failed) {
            throw new RuntimeException('Account enforcement notice could not be delivered.');
        }
    }
}
