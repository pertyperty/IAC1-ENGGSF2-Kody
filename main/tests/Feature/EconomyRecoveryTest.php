<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Transactions\SendFinancialNotice;
use App\Services\Account\SecureAccountMailer;
use App\Services\Operations\BudgetAdmission;
use App\Services\Transactions\FinanceGovernance;
use App\Services\Transactions\FinancialNotices;
use App\Services\Transactions\FinancialRecovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

test('financial mail for deleted or unverified recipients is suppressed without claiming delivery', function (bool $deleted) {
    Queue::fake();
    Mail::fake();
    $user = moduleAccount(Role::Learner);
    DB::transaction(fn () => app(FinancialNotices::class)->queue($user->id, 'receipt:suppressed', 'Test receipt', ['kodebits' => 1]));
    $user->forceFill($deleted ? ['account_status' => AccountStatus::Deleted] : ['account_status' => AccountStatus::Unverified, 'email_verified_at' => null])->save();
    $id = DB::table('financial_deliveries')->value('id');
    $job = new SendFinancialNotice($id);
    $job->handle(app(SecureAccountMailer::class));
    $job->handle(app(SecureAccountMailer::class));
    $this->assertDatabaseHas('financial_deliveries', ['id' => $id, 'state' => 'Suppressed', 'sent_at' => null, 'lease_expires_at' => null]);
    Mail::assertNothingSent();
})->with([true, false]);

test('F01 F04 uncertain notices require explicit staff reconciliation while pending work recovers', function () {
    Queue::fake();
    $user = moduleAccount(Role::Learner);
    DB::transaction(fn () => app(FinancialNotices::class)->queue($user->id, 'receipt:test', 'Test receipt', ['kodebits' => 1]));
    $id = DB::table('financial_deliveries')->value('id');
    DB::table('financial_deliveries')->where('id', $id)->update(['state' => 'Sending', 'lease_expires_at' => now()->subMinute()]);
    app(FinancialRecovery::class)->sweep();
    $this->assertDatabaseHas('financial_deliveries', ['id' => $id, 'state' => 'Review']);
    Queue::assertPushed(SendFinancialNotice::class, 1);
    expect(fn () => app(FinancialRecovery::class)->retry(moduleAccount(Role::Moderator), 'module-test-session', 'email', $id, economyConfirm(['notes' => 'Checked mail provider.'])))->toThrow(HttpException::class);
    app(FinancialRecovery::class)->retry(moduleAccount(Role::Administrator), 'module-test-session', 'email', $id, economyConfirm(['notes' => 'Checked provider acceptance; explicitly allow a possible duplicate.']));
    Queue::assertPushed(SendFinancialNotice::class, 2);
    $this->assertDatabaseHas('financial_deliveries', ['id' => $id, 'state' => 'Pending']);
    expect(DB::table('audit_events')->where('event', 'financial.reconciliation_requested')->count())->toBe(1);
});

test('operations spending cannot use reserved or immature cash and is statement-idempotent', function () {
    Queue::fake();
    $staff = moduleAccount(Role::Administrator);
    $service = app(FinanceGovernance::class);
    $data = economyConfirm(['amount' => '100.00', 'reference' => 'statement-1', 'evidence' => 'Actual deposited funds.']);
    $service->cash($staff, 'module-test-session', $data);
    $service->cash($staff, 'module-test-session', $data);
    expect(DB::table('platform_cash_entries')->count())->toBe(1);
    $expense = economyConfirm(['amount' => '50.00', 'reference' => 'invoice-1', 'evidence' => 'Settled operating invoice.']);
    $service->cash($staff, 'module-test-session', $expense, true);
    $service->cash($staff, 'module-test-session', $expense, true);
    expect(DB::table('economy_locks')->value('platform_minor'))->toBe(5000);
    expect(fn () => $service->cash($staff, 'module-test-session', array_replace($expense, ['reference' => 'invoice-2', 'amount' => '50.01']), true))->toThrow(ValidationException::class);
});

test('operations budget alerts are durable once per threshold and stop only new admission', function () {
    Queue::fake();
    config(['operations.budget_enforced' => true]);
    $staff = moduleAccount(Role::Administrator);
    $service = app(BudgetAdmission::class);
    expect(fn () => $service->assert('payments'))->toThrow(ValidationException::class);
    $service->report($staff, 'module-test-session', economyConfirm(['amount' => '12000.00', 'record_version' => 0, 'source' => 'Invoices and remaining contracted usage.']));
    $service->report($staff, 'module-test-session', economyConfirm(['amount' => '12000.00', 'record_version' => 1, 'source' => 'Same total verified.']));
    expect(DB::table('financial_deliveries')->count())->toBe(3);
    expect(fn () => $service->assert('challenge'))->toThrow(ValidationException::class);
    expect(fn () => $service->report($staff, 'module-test-session', economyConfirm(['amount' => '100.00', 'record_version' => 0, 'source' => 'Stale form.'])))->toThrow(ValidationException::class);
});
