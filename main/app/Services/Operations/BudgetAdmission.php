<?php

namespace App\Services\Operations;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Transactions\FinancialAccounts;
use App\Services\Transactions\FinancialNotices;
use App\Services\Transactions\WalletLedger;
use App\Support\ExactMoney;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetAdmission
{
    public function assert(string $provider): void
    {
        $month = CarbonImmutable::now('Asia/Manila')->startOfMonth()->toDateString();
        $report = DB::table('operations_budget_reports')->where('month', $month)->first();
        if (config('operations.provider_admission_paused') || (config('operations.budget_enforced')
            && ($report === null || $report->reported_minor >= config('operations.monthly_budget_minor')))) {
            throw ValidationException::withMessages([$provider => 'New provider-backed activity is paused for operational review. Committed work continues.']);
        }
    }

    public function report(User $actor, string $sessionId, #[\SensitiveParameter] array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $data): void {
            $staff = app(FinancialAccounts::class)->lock($actor, $sessionId, true);
            app(FinancialAccounts::class)->confirm($staff, $data);
            app(WalletLedger::class)->lock();
            $month = CarbonImmutable::now('Asia/Manila')->startOfMonth()->toDateString();
            $row = DB::table('operations_budget_reports')->where('month', $month)->lockForUpdate()->first();
            $minor = ExactMoney::minor($data['amount']);
            if ((int) ($data['record_version'] ?? -1) !== ($row?->record_version ?? 0)) {
                throw ValidationException::withMessages(['record_version' => 'The cost report changed. Reload before recording it.']);
            }
            DB::table('operations_budget_reports')->updateOrInsert(['month' => $month], ['reported_minor' => $minor,
                'reported_by' => $staff->id, 'record_version' => ($row?->record_version ?? 0) + 1, 'updated_at' => now()]);
            app(AuditRecorder::class)->record($staff->id, $staff->id, 'operations.cost_reported', 'budget_month', $month, ['amount_minor' => $minor, 'version' => ($row?->record_version ?? 0) + 1, 'source' => $data['source']]);
            foreach ([50, 80, 100] as $threshold) {
                if ($minor * 100 < config('operations.monthly_budget_minor') * $threshold) {
                    continue;
                }
                foreach (User::where('account_role', Role::Administrator->value)->where('account_status', AccountStatus::Active->value)->whereNotNull('email_verified_at')->pluck('id') as $userId) {
                    app(FinancialNotices::class)->queue($userId, 'budget:'.$month.':'.$threshold.':'.$userId, 'Pilot budget reached '.$threshold.'%', ['amount_minor' => $minor]);
                }
            }
        }, 3);
    }
}
