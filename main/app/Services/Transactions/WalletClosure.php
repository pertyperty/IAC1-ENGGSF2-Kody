<?php

namespace App\Services\Transactions;

use App\Models\User;
use App\Services\Administration\AuditRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletClosure
{
    public function snapshot(int $userId): array
    {
        $lots = DB::table('wallet_lots')->where('user_id', $userId)->where('remaining_kb', '>', 0)->orderBy('id')->get();
        $earnings = DB::table('publisher_earnings')->where('user_id', $userId)->where('status', 'Available')
            ->where(fn ($q) => $q->whereColumn('amount_minor', '>', 'claimed_minor')->orWhereColumn('kb_milli', '>', 'claimed_kb_milli'))->orderBy('id')->get();

        return ['lots' => $lots, 'earnings' => $earnings, 'wallet_kb' => (int) $lots->sum('remaining_kb'),
            'cash_minor' => (int) $earnings->where('kind', 'Cash')->sum(fn ($row) => $row->amount_minor - $row->claimed_minor),
            'creator_kb_milli' => (int) $earnings->where('kind', 'KodeBits')->sum(fn ($row) => $row->kb_milli - $row->claimed_kb_milli),
            'fingerprint' => hash('sha256', json_encode([$lots, $earnings], JSON_THROW_ON_ERROR))];
    }

    public function relinquish(User $actor, string $sessionId, #[\SensitiveParameter] array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $data): void {
            $user = app(FinancialAccounts::class)->lock($actor, $sessionId);
            app(FinancialAccounts::class)->confirm($user, $data);
            if (($data['confirmation_phrase'] ?? '') !== 'RELINQUISH MY BALANCES') {
                throw ValidationException::withMessages(['confirmation_phrase' => 'Confirm the exact relinquishment phrase. This is optional and permanent.']);
            }
            $ledger = app(WalletLedger::class);
            $ledger->lock();
            $state = $this->snapshot($user->id);
            if ($ledger->snapshot($user->id)['balance'] !== $state['wallet_kb']) {
                throw new \LogicException('Wallet balances require reconciliation.');
            }
            $reference = 'closure:'.$user->id.':'.($data['fingerprint'] ?? '');
            if (DB::table('wallet_operations')->where('reference', $reference)->exists()) {
                return;
            }
            if (! hash_equals($state['fingerprint'], $data['fingerprint'] ?? '')) {
                throw ValidationException::withMessages(['fingerprint' => 'Balances changed. Reload and inspect the current amounts.']);
            }
            foreach (['payment_purchases', 'payout_requests', 'refund_requests'] as $table) {
                if (DB::table($table)->where('user_id', $user->id)->whereNotIn('state', ['Succeeded', 'Failed', 'Rejected', 'Refunded', 'Reversed'])->exists()) {
                    throw ValidationException::withMessages(['wallet' => 'Finish every open financial operation first.']);
                }
            }
            if ($state['lots']->contains(fn ($row) => $row->reserved_kb > 0) || $state['earnings']->contains(fn ($row) => $row->reserved_minor > 0 || CarbonImmutable::parse($row->available_at)->isFuture())) {
                throw ValidationException::withMessages(['wallet' => 'Reserved balances and unmatured earnings cannot be relinquished.']);
            }
            if ($state['lots']->isEmpty() && $state['earnings']->isEmpty()) {
                return;
            }
            $walletValue = (int) $state['lots']->sum('remaining_minor');
            $earningValue = (int) $state['earnings']->sum(fn ($row) => $row->amount_minor - $row->claimed_minor);
            $operation = $ledger->operation($user->id, $reference, 'Closure', -$state['wallet_kb'], -$walletValue);
            foreach ($state['lots'] as $lot) {
                $ledger->entry($operation, $lot->id, -$lot->remaining_kb, -$lot->remaining_minor);
                DB::table('wallet_lots')->where('id', $lot->id)->update(['remaining_kb' => 0, 'remaining_minor' => 0, 'remaining_purchased_kb' => 0]);
            }
            DB::table('wallet_accounts')->where('user_id', $user->id)->update(['balance' => 0, 'reserved' => 0]);
            foreach ($state['earnings'] as $earning) {
                DB::table('publisher_earnings')->where('id', $earning->id)->update(['claimed_minor' => $earning->amount_minor, 'claimed_kb_milli' => $earning->kb_milli]);
            }
            $ledger->cash($reference, $walletValue + $earningValue);
            app(AuditRecorder::class)->record($user->id, $user->id, 'balances.relinquished', 'wallet_operation', $operation,
                ['wallet_kb' => $state['wallet_kb'], 'cash_minor' => $state['cash_minor'], 'creator_kb_milli' => $state['creator_kb_milli'], 'explicit_consent' => true]);
            app(FinancialNotices::class)->queue($user->id, $reference, 'Optional balance relinquishment confirmed', ['kodebits' => $state['wallet_kb'], 'amount_minor' => $state['cash_minor']]);
        }, 3);
    }
}
