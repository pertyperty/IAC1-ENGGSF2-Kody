<?php

namespace App\Jobs\Transactions;

use App\Enums\AccountStatus;
use App\Mail\Transactions\FinancialReceipt;
use App\Models\User;
use App\Services\Account\SecureAccountMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendFinancialNotice implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(public string $deliveryId) {}

    public function handle(SecureAccountMailer $mailer): void
    {
        $claim = DB::transaction(function (): ?object {
            $row = DB::table('financial_deliveries')->where('id', $this->deliveryId)->lockForUpdate()->first();
            if ($row === null || $row->state !== 'Pending') {
                return null;
            }
            DB::table('financial_deliveries')->where('id', $row->id)->update(['state' => 'Sending', 'attempted_at' => now(),
                'lease_expires_at' => now()->addSeconds(60), 'updated_at' => now()]);

            return $row;
        });
        if ($claim === null) {
            return;
        }
        $user = User::find($claim->user_id);
        if ($user === null || $user->account_status === AccountStatus::Deleted || $user->email_verified_at === null) {
            DB::table('financial_deliveries')->where('id', $claim->id)->update(['state' => 'Suppressed', 'sent_at' => null, 'lease_expires_at' => null, 'updated_at' => now()]);

            return;
        }
        try {
            $mailer->send('notifications', $user->email, new FinancialReceipt($claim->id, $claim->title, json_decode($claim->details, true, flags: JSON_THROW_ON_ERROR)));
            DB::table('financial_deliveries')->where('id', $claim->id)->update(['state' => 'Sent', 'sent_at' => now(), 'lease_expires_at' => null, 'updated_at' => now()]);
        } catch (Throwable) {
            // SMTP acceptance can be uncertain. A reconciler must explicitly
            // retry Review rows rather than jobs blindly sending twice.
            DB::table('financial_deliveries')->where('id', $claim->id)->update(['state' => 'Review', 'lease_expires_at' => null, 'updated_at' => now()]);
            throw new RuntimeException('Financial notice delivery requires review.');
        }
    }
}
