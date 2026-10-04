<?php

use App\Enums\Role;
use App\Services\Transactions\WalletLedger;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);

afterEach(function () {
    // Disposable test database only; deployed financial migrations refuse data loss.
    $this->artisan('migrate:fresh')->assertSuccessful();
});

test('F02 overlapping purchases cannot spend the same last five KodeBits', function () {
    $first = moduleFixture(true);
    $second = moduleFixture(true);
    foreach ([$first, $second] as $module) {
        $module->publishedRevision->update(['price_kb' => 5]);
    }
    $user = moduleAccount(Role::Learner);
    economyCredit($user, 5, 500);
    $results = simultaneousAccountRequests('economy-unlock', ['actor_id' => $user->id, 'content_ids' => [$first->id, $second->id],
        'revision_ids' => [$first->published_revision_id, $second->published_revision_id]], fn () => DB::table('economy_locks')->where('id', 1)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'unlocked'])->and(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(0);
    expect(DB::table('content_purchases')->count())->toBe(1)->and(DB::table('content_entitlements')->count())->toBe(1);
    expect(DB::table('wallet_entries')->sum('kodebits'))->toBe(0);
});

test('F04 simultaneous payout requests reserve available earnings once', function () {
    economyProvider();
    $creator = moduleAccount(Role::Instructor);
    $buyer = moduleAccount(Role::Learner);
    economyCredit($buyer);
    DB::transaction(fn () => app(WalletLedger::class)->buy($buyer->id, $creator->id, 'course', 1, 1, 1000, 'Cash'));
    $this->travel(15)->days();
    $creator->forceFill(['active_session_expires_at' => now()->addHour()])->save();
    $results = simultaneousAccountRequests('economy-payout', ['actor_id' => $creator->id, 'data' => economyPayoutData(), 'xendit' => config('xendit'), 'clock' => now()->toIso8601String()],
        fn () => DB::table('economy_locks')->where('id', 1)->lockForUpdate()->first());
    sort($results);
    expect($results)->toBe(['duplicate', 'reserved'])->and(DB::table('payout_requests')->count())->toBe(1);
    expect(DB::table('publisher_earnings')->sole()->reserved_minor)->toBe(50000)->and(DB::table('publisher_earnings')->sole()->claimed_minor)->toBe(0);
});
