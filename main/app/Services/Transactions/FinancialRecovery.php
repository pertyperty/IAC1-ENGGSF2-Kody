<?php

namespace App\Services\Transactions;

use App\Jobs\Transactions\CreateProviderOperation;
use App\Jobs\Transactions\ReconcileProviderEvent;
use App\Jobs\Transactions\SendFinancialNotice;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Transactions\Xendit\Readiness;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class FinancialRecovery
{
    public function sweep(): void
    {
        DB::transaction(function (): void {
            // Creating means a POST may already have reached the provider. Never repeat it.
            foreach (['payment_purchases', 'payout_requests', 'refund_requests'] as $table) {
                DB::table($table)->where('state', 'Creating')->where('updated_at', '<', now()->subMinutes(5))->update(['state' => 'Review', 'updated_at' => now()]);
            }
            DB::table('financial_deliveries')->where('state', 'Sending')->where('lease_expires_at', '<', now())->update(['state' => 'Review', 'lease_expires_at' => null, 'updated_at' => now()]);
            foreach (DB::table('financial_deliveries')->where('state', 'Pending')->where(fn ($q) => $q->whereNull('last_queued_at')->orWhere('last_queued_at', '<', now()->subMinutes(10)))->orderBy('created_at')->limit(100)->lockForUpdate()->get() as $row) {
                DB::table('financial_deliveries')->where('id', $row->id)->update(['last_queued_at' => now()]);
                Queue::connection('database')->pushOn('notifications', (new SendFinancialNotice($row->id))->beforeCommit());
            }
            if (! app(Readiness::class)->available()) {
                return;
            }
            foreach (['payment_purchases' => 'payment', 'payout_requests' => 'payout', 'refund_requests' => 'refund'] as $table => $kind) {
                foreach (DB::table($table)->where('state', 'Queued')->where('updated_at', '<', now()->subMinutes(10))->orderBy('created_at')->limit(100)->lockForUpdate()->get(['id']) as $row) {
                    DB::table($table)->where('id', $row->id)->update(['updated_at' => now()]);
                    Queue::connection('database')->pushOn('payments', (new CreateProviderOperation($kind, $row->id))->beforeCommit());
                }
            }
            foreach (DB::table('provider_webhooks')->where('state', 'Pending')->where(fn ($q) => $q->whereNull('last_queued_at')->orWhere('last_queued_at', '<', now()->subMinutes(10)))->orderBy('created_at')->limit(100)->lockForUpdate()->get(['id']) as $row) {
                DB::table('provider_webhooks')->where('id', $row->id)->update(['last_queued_at' => now()]);
                Queue::connection('database')->pushOn('payments', (new ReconcileProviderEvent($row->id))->beforeCommit());
            }
        }, 3);
    }

    public function retry(User $actor, string $sessionId, string $kind, string $id, #[\SensitiveParameter] array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $kind, $id, $data): void {
            $staff = app(FinancialAccounts::class)->lock($actor, $sessionId, true);
            app(FinancialAccounts::class)->confirm($staff, $data);
            $table = $kind === 'email' ? 'financial_deliveries' : 'provider_webhooks';
            $row = DB::table($table)->where('id', $id)->lockForUpdate()->firstOrFail();
            abort_unless($row->state === 'Review', 409);
            if ($kind !== 'email') {
                abort_unless(app(Readiness::class)->available(), 503);
            }
            DB::table($table)->where('id', $id)->update(['state' => 'Pending', 'last_queued_at' => now()]);
            Queue::connection('database')->pushOn($kind === 'email' ? 'notifications' : 'payments', ($kind === 'email' ? new SendFinancialNotice($id) : new ReconcileProviderEvent($id))->beforeCommit());
            app(AuditRecorder::class)->record($staff->id, $kind === 'email' ? $row->user_id : null, 'financial.reconciliation_requested', $table, $id, ['notes' => $data['notes']]);
        }, 3);
    }
}
