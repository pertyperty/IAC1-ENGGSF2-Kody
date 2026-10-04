<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Gamification\Achievements;
use App\Services\Gamification\LearningProgression;
use App\Services\Transactions\WalletLedger;
use App\Support\ExactMoney;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('F01 F02 lots conserve backing and immutable ledger entries across rounding and duplicate admission', function () {
    Queue::fake();
    $user = User::factory()->create();
    $creator = User::factory()->create(['account_role' => Role::Instructor]);
    $ledger = app(WalletLedger::class);
    DB::transaction(function () use ($ledger, $user, $creator): void {
        $first = $ledger->credit($user->id, 'confirmed:first', 'Purchase', 105, 8600);
        expect($ledger->credit($user->id, 'confirmed:first', 'Purchase', 105, 8600)->id)->toBe($first->id);
        $sale = $ledger->buy($user->id, $creator->id, 'module', 1, 1, 5, 'Cash');
        expect($sale->value_minor)->toBe(409)->and($sale->creator_minor)->toBe(265)->and($sale->platform_minor)->toBe(144);
        expect($ledger->buy($user->id, $creator->id, 'module', 1, 1, 5, 'Cash'))->toBeNull();
        $rest = $ledger->buy($user->id, $creator->id, 'course', 1, 1, 100, 'Cash');
        expect($rest->value_minor)->toBe(8191);
        expect($ledger->snapshot($user->id)['balance'])->toBe(0);
        expect(DB::table('wallet_lots')->sum('remaining_minor'))->toBe(0);
        expect(DB::table('wallet_entries')->sum('kodebits'))->toBe(0);
        expect(DB::table('wallet_entries')->sum('value_minor'))->toBe(0);
        expect(DB::table('wallet_operations')->sum('value_minor'))->toBe(0);
        expect(DB::table('publisher_earnings')->sum('amount_minor') + DB::table('economy_locks')->value('platform_minor'))->toBe(8600);
    });
    $entry = DB::table('wallet_entries')->first();
    expect(fn () => DB::transaction(fn () => DB::table('wallet_entries')->where('id', $entry->id)->update(['kodebits' => 999])))->toThrow(QueryException::class);
});

test('F02 insufficient self and rolled back audited spending never grant access or lose value', function () {
    Queue::fake();
    $user = User::factory()->create();
    $creator = User::factory()->create();
    $ledger = app(WalletLedger::class);
    DB::transaction(fn () => $ledger->credit($user->id, 'confirmed', 'Purchase', 5, 400));
    expect(fn () => DB::transaction(fn () => $ledger->buy($user->id, $creator->id, 'course', 1, 1, 20, 'Cash')))->toThrow(ValidationException::class);
    expect(fn () => DB::transaction(fn () => $ledger->buy($user->id, $user->id, 'module', 1, 1, 5, 'Cash')))->toThrow(ValidationException::class);
    $this->mock(AuditRecorder::class, fn ($mock) => $mock->shouldReceive('record')->andThrow(new RuntimeException('Unavailable')));
    expect(fn () => DB::transaction(fn () => $ledger->buy($user->id, $creator->id, 'module', 1, 1, 5, 'Cash')))->toThrow(RuntimeException::class);
    expect($ledger->snapshot($user->id)['balance'])->toBe(5);
    $this->assertDatabaseCount('content_purchases', 0);
    $this->assertDatabaseCount('publisher_earnings', 0);
    $this->assertDatabaseCount('financial_deliveries', 0);
});

test('E05 cash backing and monthly paid spend both limit idempotent reward issuance', function () {
    Queue::fake();
    $user = User::factory()->create();
    $creator = User::factory()->create();
    $ledger = app(WalletLedger::class);
    $this->travelTo(CarbonImmutable::parse('2026-09-10T04:00:00Z'));
    DB::transaction(function () use ($ledger, $user, $creator): void {
        $ledger->credit($user->id, 'old purchase', 'Purchase', 1000, 100000);
        $ledger->buy($user->id, $creator->id, 'course', 1, 1, 1000, 'Cash');
    });
    $this->travelTo(CarbonImmutable::parse('2026-10-04T04:00:00Z'));
    DB::transaction(function () use ($ledger, $user): void {
        expect($ledger->rewardAllowance()['available'])->toBe(40);
        $ledger->reward($user->id, 'weekly:1:user:'.$user->id, 20);
        $ledger->reward($user->id, 'weekly:1:user:'.$user->id, 20);
        expect($ledger->rewardAllowance()['available'])->toBe(20);
        expect($ledger->snapshot($user->id)['balance'])->toBe(20);
    });
    expect(fn () => DB::transaction(fn () => $ledger->reward($user->id, 'over-cap', 21)))->toThrow(LogicException::class);
});

test('E05 XP records first validated wins once and keeps role ranks separate from progression', function () {
    $user = moduleAccount(Role::Learner);
    $service = app(LearningProgression::class);
    $win = ['program' => ['right', 'right', 'up', 'right', 'right']];
    $service->record($user, 'module-test-session', 'sequences', 'game', $win);
    $service->record($user, 'module-test-session', 'sequences', 'game', $win);
    expect(app(Achievements::class)->snapshot($user->id)['xp'])->toBe(20);
    expect($user->fresh()->account_role)->toBe(Role::Learner);
    $this->assertDatabaseCount('xp_awards', 1);
});

test('exact monetary parsing preserves decimals and rejects floats exponents and oversized amounts', function () {
    expect(ExactMoney::minor('100.12'))->toBe(10012)->and(ExactMoney::decimal(10012))->toBe('100.12');
    expect(ExactMoney::decode('{"amount":100.12,"description":"value 100.12","nested":{"amount":0.01}}'))
        ->toBe(['amount' => '100.12', 'description' => 'value 100.12', 'nested' => ['amount' => '0.01']]);
    foreach ([100.12, '1e4', '1.234', '-1', '100000000', '00.20'] as $invalid) {
        expect(fn () => ExactMoney::minor($invalid))->toThrow(InvalidArgumentException::class);
    }
});
