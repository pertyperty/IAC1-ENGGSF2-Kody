<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Administration\SystemReports;
use App\Services\Documents\ExportFiles;
use App\Services\Transactions\FinanceGovernance;
use App\Services\Transactions\WalletLedger;
use App\Support\ExactMoney;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(DatabaseMigrations::class);
afterEach(function () {
    $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
});

test('business cash reports reconcile expenses and opening closing balances with bounded nonpersonal registers', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-02 12:00:00 UTC'));
    $admin = moduleAccount(Role::Administrator);
    $learner = moduleAccount(Role::Learner);
    DB::transaction(fn () => app(WalletLedger::class)->cash('owner-funding:private-account-reference', 10001));
    $this->travelTo(CarbonImmutable::parse('2026-10-03 00:00:00 UTC'));
    $admin->forceFill(['active_session_expires_at' => now()->addHour()])->save();
    app(FinanceGovernance::class)->cash($admin, 'module-test-session', economyConfirm(['amount' => '20.25', 'reference' => 'private-invoice', 'evidence' => 'Private statement.']), true);
    economyCredit($learner, 100, 10000);
    DB::table('operations_budget_reports')->insert(['month' => '2026-10-01', 'reported_minor' => 40000, 'reported_by' => $admin->id, 'record_version' => 1, 'updated_at' => now()]);
    $report = app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters('economy'));
    $totals = array_column($report['financials']['totals'], 'minor', 'metric');
    expect($totals['Opening posted platform cash'])->toBe(10001)
        ->and($totals['Platform cash outflows'])->toBe(2025)
        ->and($totals['Paid operating expenses (included in outflows)'])->toBe(2025)
        ->and($totals['Net cash movement'])->toBe(-2025)
        ->and($totals['Closing posted platform cash'])->toBe(7976);
    expect($report['financials']['registers']['cash']['rows'][0][3])->toBe('-20.25')
        ->and($report['financials']['registers']['costs']['rows'][0][1])->toBe('400.00')
        ->and($report['trends']['rows'][0]['expenses_minor'])->toBe(2025);
    expect(json_encode($report['financials']))->not->toContain($learner->email, 'private-invoice', 'Private statement.', 'provider_request_id');
    moduleSignIn($this, $admin);
    $this->get(route('finance.index'))->assertOk()->assertSee('-20.25');
    $this->get(route('system-reports', reportFilters('economy')))->assertOk()->assertSee('Operating expense')->assertSee('Monthly cost estimates');
});

test('business trend reports fill empty periods and group exact Manila boundaries by day month and year', function (string $group, array $periods) {
    $admin = moduleAccount(Role::Administrator);
    $admin->forceFill(['created_at' => '2025-01-01 00:00:00'])->save();
    foreach (['2026-09-29 16:00:00', '2026-09-30 16:00:00', '2026-10-01 15:59:59', '2026-10-01 16:00:00'] as $date) {
        User::factory()->create(['created_at' => $date]);
    }
    $report = app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters('accounts', ['from' => '2026-09-30', 'to' => '2026-10-01', 'group' => $group]));
    expect(array_column($report['trends']['rows'], 'period'))->toBe($periods)
        ->and(array_sum(array_column($report['trends']['rows'], 'accounts')))->toBe(3)
        ->and(array_sum(array_column($report['trends']['rows'], 'completions')))->toBe(0)
        ->and($report['financials'])->toBeNull();
})->with([['day', ['2026-09-30', '2026-10-01']], ['month', ['2026-09', '2026-10']], ['year', ['2026']]]);

test('financial extracts expose only latest bounded internal records and disclose complete matching counts', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-03 00:00:00 UTC'));
    $admin = moduleAccount(Role::Administrator);
    $rows = [];
    for ($index = 0; $index < 205; $index++) {
        $rows[] = ['id' => (string) Str::uuid(), 'reference' => 'owner-funding:'.$index, 'amount_minor' => 1, 'created_at' => now()->addSeconds($index)];
    }
    DB::table('platform_cash_entries')->insert($rows);
    $report = app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters('economy'));
    expect($report['financials']['registers']['cash']['count'])->toBe(205)
        ->and($report['financials']['registers']['cash']['rows'])->toHaveCount(200)
        ->and($report['financials']['registers']['cash']['rows'][0][0])->toBe($rows[204]['id'])
        ->and($report['financials']['totals'][1]['minor'])->toBe(205);
});

test('report export temporary workspace cleans owned private files even after writer failure', function () {
    $directory = null;
    expect(fn () => app(ExportFiles::class)->within(function ($path) use (&$directory) {
        $directory = $path;
        expect($path)->toStartWith(storage_path('app/private/report-exports/'));
        File::put($path.'/incomplete.xlsx', 'private bytes');
        throw new RuntimeException('Simulated export failure.');
    }))->toThrow(RuntimeException::class);
    expect(File::exists($directory))->toBeFalse();
});

test('financial request registers distinguish requested values and current states without exporting provider or recipient data', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-03 00:00:00 UTC'));
    $admin = moduleAccount(Role::Administrator);
    $buyer = moduleAccount(Role::Learner);
    DB::table('payment_purchases')->insert(['id' => (string) Str::uuid(), 'user_id' => $buyer->id, 'confirmation_id' => (string) Str::uuid(),
        'package' => 'starter', 'kodebits' => 105, 'gross_minor' => 10000, 'fee_minor' => 300, 'state' => 'Review', 'provider_request_id' => 'PRIVATE_PROVIDER_REFERENCE', 'checkout_url' => 'https://provider.test/private-checkout', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('payout_requests')->insert(['id' => (string) Str::uuid(), 'user_id' => $buyer->id, 'confirmation_id' => (string) Str::uuid(),
        'gross_minor' => 60000, 'fee_minor' => 1000, 'state' => 'PendingReview', 'recipient' => 'PRIVATE_ENCRYPTED_RECIPIENT', 'created_at' => now(), 'updated_at' => now()]);
    $report = app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters('economy'));
    expect($report['financials']['registers']['payments']['rows'][0][2])->toBe('Review')
        ->and($report['financials']['registers']['payments']['rows'][0][3])->toBe('100.00')
        ->and($report['financials']['registers']['payouts']['rows'][0][2])->toBe('PendingReview')
        ->and($report['financials']['totals'][1]['minor'])->toBe(0);
    expect(json_encode($report))->not->toContain('PRIVATE_PROVIDER_REFERENCE', 'private-checkout', 'PRIVATE_ENCRYPTED_RECIPIENT', $buyer->email);
});

test('reports retain valid grouping and reject unsupported grouping without altering selections', function () {
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $this->get(route('system-reports', reportFilters('overview', ['group' => 'month'])))->assertOk();
    $this->getJson(route('system-reports', ['group' => 'hour']))->assertUnprocessable();
    expect(session('system_report_filters.group'))->toBe('month');
    $this->get(route('system-reports'))->assertOk()->assertViewHas('report', fn ($report) => $report['trends']['group'] === 'month');
});

test('signed money presentation handles expenses and large totals without changing positive provider validation', function () {
    expect(ExactMoney::signedDecimal(-1))->toBe('-0.01')
        ->and(ExactMoney::signedDecimal(-2025))->toBe('-20.25')
        ->and(ExactMoney::signedDecimal(10000000000))->toBe('100000000.00');
    expect(fn () => ExactMoney::decimal(-1))->toThrow(InvalidArgumentException::class);
});
