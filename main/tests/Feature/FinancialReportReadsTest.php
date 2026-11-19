<?php

use App\Services\Administration\ReportFinancials;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function financialReadPosting(int $minor, CarbonImmutable $at, string $reference): array
{
    return ['id' => (string) Str::uuid(), 'reference' => $reference, 'amount_minor' => $minor, 'created_at' => $at];
}

test('financial projection preserves exact half-open Manila windows and signed expense reversals', function () {
    $start = CarbonImmutable::parse('2026-11-01', 'Asia/Manila')->startOfDay()->utc();
    $end = $start->addDay();
    $postings = [
        financialReadPosting(10000000001, $start->subSeconds(2), 'owner-funding:PRIVATE-OPENING'),
        financialReadPosting(-10, $start->subSecond(), 'operating-expense:PRIVATE-OLD-EXPENSE'),
        financialReadPosting(10001, $start, 'owner-funding:PRIVATE-WINDOW'),
        financialReadPosting(-2025, $start->addHour(), 'operating-expense:PRIVATE-INVOICE'),
        financialReadPosting(25, $start->addHours(2), 'operating-expense:PRIVATE-REVERSAL'),
        financialReadPosting(-1100, $start->addHours(3), 'backing:PRIVATE-BACKING'),
        financialReadPosting(-7, $end->subSecond(), 'sale:PRIVATE-ADJUSTMENT'),
        financialReadPosting(99999999, $end, 'owner-funding:PRIVATE-EXCLUSIVE-END'),
        financialReadPosting(-99999999, $end->addSecond(), 'operating-expense:PRIVATE-AFTER'),
    ];
    DB::table('platform_cash_entries')->insert($postings);
    $report = app(ReportFinancials::class)->snapshot($start, $end);
    $totals = array_column($report['totals'], 'minor', 'metric');
    expect($totals)->toMatchArray([
        'Opening posted platform cash' => 9999999991,
        'Platform cash inflows' => 10026,
        'Platform cash outflows' => 3132,
        'Paid operating expenses (included in outflows)' => 2000,
        'Net cash movement' => 6894,
        'Closing posted platform cash' => 10000006885,
    ]);
    expect($report['registers']['cash']['count'])->toBe(5)
        ->and(array_column($report['registers']['cash']['rows'], 0))->toBe(array_reverse(array_column(array_slice($postings, 2, 5), 'id')))
        ->and(json_encode($report))->not->toContain('PRIVATE-');
    foreach ($report['totals'] as $total) {
        expect($total['minor'])->toBeInt();
    }
    expect(array_column($report['totals'], 'display', 'metric')['Closing posted platform cash'])->toBe('PHP 100000068.85');
});

test('financial aggregates coalesce empty and one-sided cash windows without changing signed classification', function (array $amounts, int $inflows, int $outflows, int $expenses) {
    $start = CarbonImmutable::parse('2026-11-01', 'Asia/Manila')->startOfDay()->utc();
    foreach ($amounts as $index => $minor) {
        DB::table('platform_cash_entries')->insert(financialReadPosting($minor, $start, 'operating-expense:sample-'.$index));
    }
    $report = app(ReportFinancials::class)->snapshot($start, $start->addDay());
    $totals = array_column($report['totals'], 'minor', 'metric');
    expect($totals['Opening posted platform cash'])->toBe(0)
        ->and($totals['Platform cash inflows'])->toBe($inflows)
        ->and($totals['Platform cash outflows'])->toBe($outflows)
        ->and($totals['Paid operating expenses (included in outflows)'])->toBe($expenses)
        ->and($totals['Closing posted platform cash'])->toBe($inflows - $outflows);
})->with([
    'empty' => [[], 0, 0, 0],
    'inflow only' => [[150], 150, 0, -150],
    'outflow only' => [[-25], 0, 25, 25],
    'zero posting' => [[0], 0, 0, 0],
]);

test('financial projection stays within its query budget while retaining complete totals and bounded detail', function () {
    $start = CarbonImmutable::parse('2026-11-01', 'Asia/Manila')->startOfDay()->utc();
    $postings = [];
    for ($index = 0; $index < 200; $index++) {
        $postings[] = financialReadPosting(10, $start->subSeconds($index + 1), 'owner-funding:history-'.$index);
    }
    for ($index = 0; $index < 205; $index++) {
        $postings[] = financialReadPosting(1, $start->addSeconds($index), 'owner-funding:window-'.$index);
    }
    DB::table('platform_cash_entries')->insert($postings);
    DB::flushQueryLog();
    DB::enableQueryLog();
    try {
        $report = app(ReportFinancials::class)->snapshot($start, $start->addDay());
        $queries = collect(DB::getQueryLog());
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
    $cashSums = $queries->filter(fn ($query) => str_contains($query['query'], '"platform_cash_entries"') && str_contains(strtolower($query['query']), 'sum('));
    expect($queries->count())->toBeLessThanOrEqual(16)
        ->and($cashSums->count())->toBeLessThanOrEqual(2);
    expect($report['registers']['cash']['count'])->toBe(205)
        ->and($report['registers']['cash']['rows'])->toHaveCount(ReportFinancials::DETAIL_LIMIT)
        ->and($report['registers']['cash']['rows'][0][0])->toBe($postings[404]['id']);
    $totals = array_column($report['totals'], 'minor', 'metric');
    expect($totals['Opening posted platform cash'])->toBe(2000)
        ->and($totals['Platform cash inflows'])->toBe(205)
        ->and($totals['Closing posted platform cash'])->toBe(2205);
});
