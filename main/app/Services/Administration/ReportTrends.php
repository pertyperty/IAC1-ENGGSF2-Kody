<?php

namespace App\Services\Administration;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;

class ReportTrends
{
    public function generate(CarbonImmutable $start, CarbonImmutable $end, string $group): array
    {
        if (! in_array($group, ['day', 'month', 'year'], true)) {
            throw new LogicException('Unsupported report interval.');
        }
        // Identifiers and date_trunc units are fixed; all calendar window values are bound.
        $series = [];
        foreach ([
            'accounts' => ['users', 'created_at', 'COUNT(*)'],
            'completions' => ['learning_activity_days', 'completed_at', 'COUNT(*)'],
            'articles' => ['module_read_completions', 'completed_at', 'COUNT(*)'],
            'attempts' => ['challenge_submissions', 'submitted_at', 'COUNT(*)'],
            'xp' => ['xp_awards', 'created_at', 'SUM(xp)'],
            'expenses_minor' => ['platform_cash_entries', 'created_at', "-SUM(CASE WHEN reference LIKE 'operating-expense:%' THEN amount_minor ELSE 0 END)"],
            'cash_minor' => ['platform_cash_entries', 'created_at', 'SUM(amount_minor)'],
        ] as $key => [$table, $time, $aggregate]) {
            // Laravel's original users timestamps are UTC timestamp-without-time-zone;
            // domain activity/ledger timestamps are timestamptz. Interpret both explicitly.
            $localTime = $table === 'users' ? "($time AT TIME ZONE 'UTC') AT TIME ZONE 'Asia/Manila'" : "$time AT TIME ZONE 'Asia/Manila'";
            $period = "to_char(date_trunc('$group', $localTime), 'YYYY-MM-DD')";
            $series[$key] = DB::table($table)->where($time, '>=', $start)->where($time, '<', $end)
                ->selectRaw("$period AS period, $aggregate AS total")->groupByRaw($period)->pluck('total', 'period');
        }
        $cursor = $start->setTimezone('Asia/Manila');
        $cursor = match ($group) {
            'month' => $cursor->startOfMonth(), 'year' => $cursor->startOfYear(), default => $cursor->startOfDay()
        };
        $rows = [];
        while ($cursor->utc()->lessThan($end)) {
            $date = $cursor->toDateString();
            $row = ['period' => match ($group) {
                'month' => $cursor->format('Y-m'), 'year' => $cursor->format('Y'), default => $date
            }];
            foreach ($series as $key => $values) {
                $row[$key] = (int) ($values[$date] ?? 0);
            }
            $rows[] = $row;
            $cursor = match ($group) {
                'month' => $cursor->addMonth(), 'year' => $cursor->addYear(), default => $cursor->addDay()
            };
        }

        return ['group' => $group, 'rows' => $rows, 'peak' => max(1, ...array_column($rows, 'completions'))];
    }
}
