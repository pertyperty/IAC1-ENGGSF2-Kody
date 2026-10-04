<?php

namespace App\Services\Transactions;

use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Support\ExactMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceGovernance
{
    public function cash(User $actor, string $sessionId, #[\SensitiveParameter] array $data, bool $expense = false): void
    {
        DB::transaction(function () use ($actor, $sessionId, $data, $expense): void {
            $staff = app(FinancialAccounts::class)->lock($actor, $sessionId, true);
            app(FinancialAccounts::class)->confirm($staff, $data);
            $minor = ExactMoney::minor($data['amount']);
            if ($minor <= 0) {
                throw ValidationException::withMessages(['amount' => 'Record a positive amount from an actual statement.']);
            }
            $ledger = app(WalletLedger::class);
            $ledger->lock();
            $reference = ($expense ? 'operating-expense:' : 'owner-funding:').$data['reference'];
            $amount = $expense ? -$minor : $minor;
            $existing = DB::table('platform_cash_entries')->where('reference', $reference)->first();
            if ($existing !== null) {
                abort_unless($existing->amount_minor === $amount, 409);

                return;
            }
            if ($expense && $ledger->maturedCash() < $minor) {
                throw ValidationException::withMessages(['amount' => 'Fund the platform before recording expenses. Reserved cash and immature margins cannot be spent.']);
            }
            $ledger->cash($reference, $amount);
            app(AuditRecorder::class)->record($staff->id, $staff->id, $expense ? 'platform.expense_recorded' : 'platform.funded', 'cash_reference', $reference,
                ['amount_minor' => $amount, 'evidence' => $data['evidence']]);
        }, 3);
    }

    public function remedy(User $actor, string $sessionId, array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $data): void {
            $user = app(FinancialAccounts::class)->lock($actor, $sessionId);
            $id = (string) Str::uuid();
            DB::table('financial_remedy_cases')->insert(['id' => $id, 'user_id' => $user->id, 'reference' => $data['reference'], 'message' => $data['message'], 'created_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record($user->id, $user->id, 'financial.remedy_requested', 'financial_remedy_case', $id);
        });
    }

    public function reviewRemedy(User $actor, string $sessionId, string $case, #[\SensitiveParameter] array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $case, $data): void {
            $staff = app(FinancialAccounts::class)->lock($actor, $sessionId, true);
            app(FinancialAccounts::class)->confirm($staff, $data);
            $row = DB::table('financial_remedy_cases')->where('id', $case)->lockForUpdate()->firstOrFail();
            abort_if($row->user_id === $staff->id || $row->state === 'Resolved', 409);
            DB::table('financial_remedy_cases')->where('id', $case)->update(['state' => $data['state'], 'review_notes' => $data['review_notes'], 'reviewed_by' => $staff->id, 'updated_at' => now()]);
            app(AuditRecorder::class)->record($staff->id, $row->user_id, 'financial.remedy_reviewed', 'financial_remedy_case', $case, ['state' => $data['state'], 'notes' => $data['review_notes']]);
            app(FinancialNotices::class)->queue($row->user_id, 'remedy:'.$case.':'.$data['state'], 'Financial remedy '.strtolower($data['state']), []);
        });
    }
}
