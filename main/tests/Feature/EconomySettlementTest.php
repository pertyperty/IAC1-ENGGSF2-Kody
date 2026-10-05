<?php

use App\Enums\Role;
use App\Services\Transactions\Payments;
use App\Services\Transactions\ProviderWebhooks;
use App\Services\Transactions\PublisherSettlements;
use App\Services\Transactions\Refunds;
use App\Services\Transactions\WalletClosure;
use App\Services\Transactions\WalletLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function economyPayoutData(array $overrides = []): array
{
    return economyConfirm(array_replace(['amount' => '500.00', 'quoted_fee_minor' => 100, 'given_name' => 'Example', 'surname' => 'Creator',
        'account_holder_name' => 'Example Creator', 'mobile' => '09171234567', 'street' => 'Test address', 'city' => 'Manila', 'province' => 'Metro Manila', 'postal_code' => '1000'], $overrides));
}

test('F04 payout reservations prevent double spending and only verified success deducts matured earnings', function () {
    economyProvider();
    $creator = moduleAccount(Role::Instructor);
    $buyer = moduleAccount(Role::Learner);
    economyCredit($buyer);
    DB::transaction(fn () => app(WalletLedger::class)->buy($buyer->id, $creator->id, 'course', 1, 1, 1000, 'Cash'));
    expect(fn () => app(PublisherSettlements::class)->request($creator, 'module-test-session', economyPayoutData()))->toThrow(ValidationException::class);
    $this->travel(15)->days();
    $creator->forceFill(['active_session_expires_at' => now()->addHour()])->save();
    $data = economyPayoutData();
    $id = app(PublisherSettlements::class)->request($creator, 'module-test-session', $data);
    expect(app(PublisherSettlements::class)->request($creator, 'module-test-session', $data))->toBe($id);
    expect(fn () => app(PublisherSettlements::class)->request($creator, 'module-test-session', economyPayoutData()))->toThrow(ValidationException::class);
    $row = DB::table('payout_requests')->find($id);
    expect($row->recipient)->not->toContain('09171234567');
    expect(DB::table('publisher_earnings')->sole()->reserved_minor)->toBe(50000)->and(DB::table('publisher_earnings')->sole()->claimed_minor)->toBe(0);
    $staff = moduleAccount(Role::Administrator);
    app(PublisherSettlements::class)->review($staff, 'module-test-session', $id, economyConfirm(['decision' => 'Approved', 'recipient_verified' => true, 'review_notes' => 'Verified the owner and GCash recipient.']));
    $recipient = json_decode(Crypt::decryptString($row->recipient), true);
    $status = 'ACCEPTED';
    $provider = fn () => ['payout_id' => 'po-00000000-0000-4000-8000-000000000004', 'reference_id' => $id, 'status' => $status,
        'source_currency' => 'PHP', 'destination_currency' => 'PHP', 'source_amount' => 49900, 'destination_amount' => 49900, 'business_id' => 'test-merchant', 'recipient' => $recipient];
    Http::fake(fn () => Http::response($provider()));
    app(PublisherSettlements::class)->create($id);
    Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['payout_details']['source_amount'] === 49900 && $request->hasHeader('idempotency-key', $id));
    expect(DB::table('publisher_earnings')->sole()->claimed_minor)->toBe(0);
    $proof = array_replace($provider(), ['status' => 'SUCCEEDED']);
    Http::swap(new Factory);
    Http::fake(['*' => Http::response($proof)]);
    economyCallback('v3_payout.succeeded', $proof, 'payout-outcome');
    app(ProviderWebhooks::class)->reconcile('payout-outcome');
    app(ProviderWebhooks::class)->reconcile('payout-outcome');
    expect(DB::table('publisher_earnings')->sole()->claimed_minor)->toBe(50000)->and(DB::table('publisher_earnings')->sole()->reserved_minor)->toBe(0);
    $this->assertDatabaseHas('payout_requests', ['id' => $id, 'state' => 'Succeeded']);
    $reversed = array_replace($proof, ['status' => 'REVERSED']);
    Http::swap(new Factory);
    Http::fake(['*' => Http::response($reversed)]);
    economyCallback('v3_payout.reversed', $reversed, 'payout-reversal');
    app(ProviderWebhooks::class)->reconcile('payout-reversal');
    app(ProviderWebhooks::class)->reconcile('payout-reversal');
    expect(DB::table('publisher_earnings')->sole()->claimed_minor)->toBe(100);
    expect(DB::table('payout_reversals')->sole()->returned_minor)->toBe(49900);
    $this->assertDatabaseHas('payout_requests', ['id' => $id, 'state' => 'Reversed']);
});

test('F04 rejection releases reservations and Contributor cash payouts remain forbidden', function () {
    economyProvider();
    $creator = moduleAccount(Role::Instructor);
    $buyer = moduleAccount(Role::Learner);
    economyCredit($buyer);
    DB::transaction(fn () => app(WalletLedger::class)->buy($buyer->id, $creator->id, 'course', 1, 1, 1000, 'Cash'));
    $this->travel(15)->days();
    $creator->forceFill(['active_session_expires_at' => now()->addHour()])->save();
    $id = app(PublisherSettlements::class)->request($creator, 'module-test-session', economyPayoutData());
    app(PublisherSettlements::class)->review(moduleAccount(Role::Administrator), 'module-test-session', $id, economyConfirm(['decision' => 'Rejected', 'review_notes' => 'Recipient evidence needs correction.']));
    expect(DB::table('publisher_earnings')->sole()->reserved_minor)->toBe(0)->and(DB::table('publisher_earnings')->sole()->claimed_minor)->toBe(0);
    expect(fn () => app(PublisherSettlements::class)->request(moduleAccount(Role::Contributor), 'module-test-session', economyPayoutData()))->toThrow(HttpException::class);
});

test('F01 full unused purchase refund reserves tokens and removes them only after authenticated provider success', function () {
    economyProvider();
    $user = moduleAccount(Role::Learner);
    $id = app(Payments::class)->purchase($user, 'module-test-session', economyConfirm(['package' => 'starter']));
    $capture = economyPaymentProof($id);
    Http::fake(['*' => Http::response($capture)]);
    economyCallback('payment.capture', $capture);
    app(ProviderWebhooks::class)->reconcile('test-event');
    $refund = app(Refunds::class)->request($user, 'module-test-session', 'Purchase', $id, economyConfirm(['reason' => 'Unused package.']));
    expect(app(WalletLedger::class)->snapshot($user->id))->toBe(['balance' => 105, 'reserved' => 105, 'available' => 0]);
    DB::transaction(fn () => app(WalletLedger::class)->cash('owner-funding:test', 1000));
    app(Refunds::class)->review(moduleAccount(Role::Administrator), 'module-test-session', $refund, economyConfirm(['decision' => 'Approved', 'review_notes' => 'Original lot unspent and within fourteen days.']));
    expect(DB::table('economy_locks')->value('reserved_minor'))->toBe(400);
    $proof = ['id' => 'rfd-00000000-0000-4000-8000-000000000005', 'reference_id' => $refund, 'payment_request_id' => $capture['payment_request_id'], 'payment_id' => $capture['latest_payment_id'],
        'amount' => 100, 'currency' => 'PHP', 'channel_code' => 'GCASH', 'status' => 'PENDING', 'business_id' => 'test-merchant'];
    Http::swap(new Factory);
    Http::fake(['*' => Http::response($proof)]);
    app(Refunds::class)->create($refund);
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(105);
    economyCallback('refund.succeeded', array_replace($proof, ['status' => 'SUCCEEDED', 'refund_fee_amount' => '0.50']), 'refund-outcome');
    app(ProviderWebhooks::class)->reconcile('refund-outcome');
    app(ProviderWebhooks::class)->reconcile('refund-outcome');
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(0);
    expect(DB::table('economy_locks')->value('platform_minor'))->toBe(600)->and(DB::table('economy_locks')->value('reserved_minor'))->toBe(0);
    $this->assertDatabaseHas('payment_purchases', ['id' => $id, 'state' => 'Refunded']);
    expect(DB::table('wallet_entries')->sum('kodebits'))->toBe(0)->and(DB::table('wallet_entries')->sum('value_minor'))->toBe(0);
});

test('A08 optional closure requires exact consent and a current snapshot and never silently forfeits balances', function () {
    Queue::fake();
    $user = moduleAccount(Role::Learner);
    economyCredit($user, 2, 170);
    $closure = app(WalletClosure::class);
    $state = $closure->snapshot($user->id);
    expect(fn () => $closure->relinquish($user, 'module-test-session', economyConfirm(['fingerprint' => $state['fingerprint'], 'confirmation_phrase' => 'wrong'])))->toThrow(ValidationException::class);
    $data = economyConfirm(['fingerprint' => $state['fingerprint'], 'confirmation_phrase' => 'RELINQUISH MY BALANCES']);
    $closure->relinquish($user, 'module-test-session', $data);
    $closure->relinquish($user, 'module-test-session', $data);
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(0)->and(DB::table('economy_locks')->value('platform_minor'))->toBe(170);
    expect(DB::table('wallet_entries')->sum('value_minor'))->toBe(0);
});
