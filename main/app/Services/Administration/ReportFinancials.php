<?php

namespace App\Services\Administration;

use App\Support\ExactMoney;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReportFinancials
{
    public const int DETAIL_LIMIT = 200;

    public function snapshot(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $cash = DB::table('platform_cash_entries')->where('created_at', '>=', $start)->where('created_at', '<', $end);
        $opening = (int) DB::table('platform_cash_entries')->where('created_at', '<', $start)->sum('amount_minor');
        $inflows = (int) (clone $cash)->where('amount_minor', '>', 0)->sum('amount_minor');
        $outflows = -(int) (clone $cash)->where('amount_minor', '<', 0)->sum('amount_minor');
        $expenses = -(int) (clone $cash)->where('reference', 'like', 'operating-expense:%')->sum('amount_minor');
        $current = DB::table('economy_locks')->find(1);
        $totals = [
            ['metric' => 'Opening posted platform cash', 'minor' => $opening],
            ['metric' => 'Platform cash inflows', 'minor' => $inflows],
            ['metric' => 'Platform cash outflows', 'minor' => $outflows],
            ['metric' => 'Paid operating expenses (included in outflows)', 'minor' => $expenses],
            ['metric' => 'Net cash movement', 'minor' => $inflows - $outflows],
            ['metric' => 'Closing posted platform cash', 'minor' => $opening + $inflows - $outflows],
            ['metric' => 'Current reserved platform cash (snapshot)', 'minor' => (int) ($current?->reserved_minor ?? 0)],
            ['metric' => 'Current wallet backing liability (snapshot)', 'minor' => (int) DB::table('wallet_lots')->sum('remaining_minor')],
            ['metric' => 'Current unclaimed cash creator earnings (snapshot)', 'minor' => (int) DB::table('publisher_earnings')->where('kind', 'Cash')->where('status', 'Available')->selectRaw('COALESCE(SUM(amount_minor - claimed_minor), 0) AS total')->value('total')],
        ];
        foreach ($totals as &$total) {
            $total['display'] = 'PHP '.ExactMoney::signedDecimal($total['minor']);
        }
        unset($total);

        $registers = [];
        foreach ([
            'cash' => ['platform_cash_entries', ['id', 'created_at', 'reference', 'amount_minor'], ['Record ID', 'Posted (Manila)', 'Classification', 'PHP signed amount']],
            'wallet' => ['wallet_operations', ['id', 'created_at', 'kind', 'kodebits', 'value_minor'], ['Record ID', 'Posted (Manila)', 'Operation', 'KB signed', 'PHP backing signed']],
            'payments' => ['payment_purchases', ['id', 'created_at', 'state', 'gross_minor', 'fee_minor', 'kodebits'], ['Record ID', 'Created (Manila)', 'Current state', 'PHP gross requested', 'PHP fee configured', 'KB requested']],
            'payouts' => ['payout_requests', ['id', 'created_at', 'state', 'gross_minor', 'fee_minor'], ['Record ID', 'Created (Manila)', 'Current state', 'PHP gross requested', 'PHP fee configured']],
            'refunds' => ['refund_requests', ['id', 'created_at', 'state', 'kind'], ['Record ID', 'Created (Manila)', 'Current state', 'Refund type']],
        ] as $key => [$table, $columns, $headers]) {
            $query = DB::table($table)->where('created_at', '>=', $start)->where('created_at', '<', $end);
            $count = (clone $query)->count();
            $rows = $query->orderByDesc('created_at')->orderByDesc('id')->limit(self::DETAIL_LIMIT)->get($columns)->map(function ($row) use ($key): array {
                $values = [$row->id, CarbonImmutable::parse($row->created_at)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s')];

                return match ($key) {
                    'cash' => [...$values, $this->cashKind($row->reference), ExactMoney::signedDecimal((int) $row->amount_minor)],
                    'wallet' => [...$values, $row->kind, (int) $row->kodebits, ExactMoney::signedDecimal((int) $row->value_minor)],
                    'payments' => [...$values, $row->state, ExactMoney::signedDecimal((int) $row->gross_minor), ExactMoney::signedDecimal((int) $row->fee_minor), (int) $row->kodebits],
                    'payouts' => [...$values, $row->state, ExactMoney::signedDecimal((int) $row->gross_minor), ExactMoney::signedDecimal((int) $row->fee_minor)],
                    'refunds' => [...$values, $row->state, $row->kind],
                };
            })->all();
            $registers[$key] = ['title' => ucfirst($key).' register', 'headers' => $headers, 'rows' => $rows, 'count' => $count];
        }
        $budgets = DB::table('operations_budget_reports')->where('month', '>=', $start->setTimezone('Asia/Manila')->startOfMonth()->toDateString())
            ->where('month', '<', $end->setTimezone('Asia/Manila')->toDateString())->orderBy('month')->get(['month', 'reported_minor', 'updated_at']);
        $registers['costs'] = ['title' => 'Monthly cost estimates', 'headers' => ['Month', 'PHP reported estimate', 'Last updated (Manila)'],
            'rows' => $budgets->map(fn ($row) => [$row->month, ExactMoney::signedDecimal((int) $row->reported_minor), CarbonImmutable::parse($row->updated_at)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s')])->all(), 'count' => $budgets->count()];

        return ['totals' => $totals, 'registers' => $registers];
    }

    private function cashKind(string $reference): string
    {
        return match (true) {
            str_starts_with($reference, 'operating-expense:') => 'Operating expense',
            str_starts_with($reference, 'owner-funding:') => 'Owner funding',
            str_starts_with($reference, 'sale:') => 'Content platform allocation',
            str_starts_with($reference, 'backing:') => 'Reward backing',
            default => 'Other posting / reversal',
        };
    }
}
