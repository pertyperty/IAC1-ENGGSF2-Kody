<?php

use App\Enums\Role;
use App\Jobs\Transactions\SendFinancialNotice;
use App\Mail\Transactions\FinancialReceipt;
use App\Models\User;
use App\Services\Account\SecureAccountMailer;
use App\Services\Transactions\Payments;
use App\Services\Transactions\ProviderWebhooks;
use App\Services\Transactions\PublisherSettlements;
use App\Services\Transactions\WalletLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('presentation rehearsal: purchase KodeBits, unlock courses and settle instructor earnings', function () {
    $snapshotDirectory = getenv('KODY_PRESENTATION_SNAPSHOTS');
    $capture = static function (string $step, TestResponse $response) use ($snapshotDirectory): void {
        if ($snapshotDirectory !== false && is_dir($snapshotDirectory)) {
            file_put_contents($snapshotDirectory.DIRECTORY_SEPARATOR.$step.'.html', $response->getContent());
        }
    };
    economyProvider();
    Mail::fake();
    Http::preventStrayRequests();
    config(['account.notifications.mailer' => 'array']);
    $buyer = moduleAccount(Role::Learner);
    $buyer->forceFill(['username' => 'demo_learner', 'email' => 'learner@example.test'])->save();
    $module = moduleFixture(true);
    $creator = User::findOrFail($module->created_by);
    $creator->forceFill(['username' => 'demo_instructor', 'email' => 'instructor@example.test'])->save();
    $courses = [
        courseFixture(true, $module, ['title' => 'Intro to programming', 'price_kb' => 500]),
        courseFixture(true, $module, ['title' => 'Practice with algorithms', 'price_kb' => 500]),
    ];
    $ledger = app(WalletLedger::class);
    $payments = app(Payments::class);
    $webhooks = app(ProviderWebhooks::class);
    $settlements = app(PublisherSettlements::class);
    moduleSignIn($this, $buyer);
    $capture('01-buy', $this->get(route('wallet.index'))->assertOk()->assertSee('1.15 KB per PHP')->assertSee('Email me the receipt'));

    foreach ($courses as $index => $course) {
        $consent = $index === 0;
        $data = economyConfirm(['package' => 'explorer'] + ($consent ? ['email_receipt' => '1'] : []));
        $response = $this->post(route('wallet.purchase'), $data)->assertRedirect();
        $id = $payments->purchase($buyer->fresh(), session()->getId(), $data);
        $response->assertRedirect(route('wallet.purchase.show', $id));
        $this->assertDatabaseHas('payment_purchases', ['id' => $id, 'email_receipt' => $consent]);
        expect(fn () => $payments->purchase($buyer->fresh(), session()->getId(), array_replace($data, ['email_receipt' => ! $consent])))->toThrow(ValidationException::class);
        $proof = array_replace(economyPaymentProof($id), [
            'payment_request_id' => 'pr-'.Str::uuid(), 'latest_payment_id' => 'py-'.Str::uuid(), 'request_amount' => 500,
            'captures' => [['capture_id' => 'cptr-'.Str::uuid(), 'capture_amount' => 500]],
        ]);
        Http::fake(['https://api.xendit.co/v3/payment_requests/'.$proof['payment_request_id'] => Http::response($proof)]);
        foreach (['capture', 'redelivery'] as $delivery) {
            economyCallback('payment.capture', $proof, "demo-$delivery-$index");
            $webhooks->reconcile("demo-$delivery-$index");
        }
        $notice = DB::table('financial_deliveries')->where('reference', 'purchase:'.$id)->sole();
        expect($notice->state)->toBe($consent ? 'Pending' : 'Suppressed');
        expect(DB::table('notifications')->where('id', $notice->id)->exists())->toBeTrue();
        expect(Queue::pushed(SendFinancialNotice::class, fn ($job) => $job->deliveryId === $notice->id))->toHaveCount($consent ? 1 : 0);
        $job = new SendFinancialNotice($notice->id);
        $job->handle(app(SecureAccountMailer::class));
        $job->handle(app(SecureAccountMailer::class));
        expect($this->get(route('wallet.receipt', [$id, 'pdf' => 1]))->assertOk()->getContent())->toStartWith('%PDF-');
        expect($ledger->snapshot($buyer->id)['available'])->toBe(575 + $index * 75);

        $coursePage = $this->get(route('course-learning.show', $course))->assertOk();
        if ($index === 0) {
            $capture('01-receipt', $this->get(route('wallet.receipt', $id))->assertOk());
            $capture('02-spend', $coursePage);
        }
        $this->post(route('course-learning.enroll', $course), ['revision_id' => $course->published_revision_id, 'confirmed' => '1'])
            ->assertRedirect(route('course-learning.show', $course));
        expect($ledger->snapshot($buyer->id)['available'])->toBe(($index + 1) * 75);
        $this->assertDatabaseHas('content_purchases', ['content_id' => $course->id, 'user_id' => $buyer->id, 'creator_id' => $creator->id,
            'kodebits' => 500, 'value_minor' => 42130, 'creator_minor' => 27384]);
    }
    Mail::assertSentCount(1);
    Mail::assertSent(FinancialReceipt::class, fn (FinancialReceipt $mail) => $mail->hasTo($buyer->email));

    moduleSignIn($this, $creator);
    $capture('03-sales', $this->get(route('wallet.index'))->assertOk()->assertSee('PHP 421.30 backing')->assertSee('PHP 273.84 earned'));
    $capture('04-maturing', $this->get(route('earnings.index'))->assertOk()->assertSee('PHP 547.68')->assertSee('You need PHP 500.00 more')->assertDontSee('Request recipient review'));
    $this->postJson(route('earnings.payout'), economyPayoutData())->assertUnprocessable()->assertJsonValidationErrors('amount');
    $this->travel(14)->days();
    moduleSignIn($this, $creator->fresh());
    $capture('05-ready', $this->get(route('earnings.index'))->assertOk()->assertSee('Request recipient review')->assertSee('PHP 546.68'));
    $this->postJson(route('earnings.payout'), economyPayoutData(['amount' => '499.99']))->assertUnprocessable()->assertJsonValidationErrors('amount');
    $this->post(route('earnings.payout'), economyPayoutData())->assertRedirect(route('earnings.index'));
    $payout = DB::table('payout_requests')->sole();
    expect($settlements->payoutSummary($creator->id, true))->toMatchArray(['available' => 4768, 'pending' => 0, 'reserved' => 50000]);

    $reviewer = moduleAccount(Role::Administrator);
    $reviewer->forceFill(['username' => 'demo_admin', 'email' => 'admin@example.test'])->save();
    moduleSignIn($this, $reviewer);
    $capture('06-review', $this->get(route('finance.show', ['payout', $payout->id]))->assertOk());
    $this->post(route('finance.review', ['payout', $payout->id]), economyConfirm(['decision' => 'Approved', 'recipient_verified' => '1', 'review_notes' => 'Rehearsal only.']))
        ->assertRedirect(route('finance.index'));
    $proof = ['payout_id' => 'po-'.Str::uuid(), 'reference_id' => $payout->id, 'status' => 'ACCEPTED', 'source_currency' => 'PHP',
        'destination_currency' => 'PHP', 'source_amount' => 49900, 'destination_amount' => 49900,
        'business_id' => 'test-merchant', 'recipient' => json_decode(Crypt::decryptString($payout->recipient), true, flags: JSON_THROW_ON_ERROR)];
    Http::fake(['https://api.xendit.co/v3/payouts*' => Http::sequence()->push($proof)->push(array_replace($proof, ['status' => 'SUCCEEDED']))]);
    $settlements->create($payout->id);
    economyCallback('v3_payout.succeeded', array_replace($proof, ['status' => 'SUCCEEDED']), 'demo-payout');
    $webhooks->reconcile('demo-payout');
    $this->assertDatabaseHas('payout_requests', ['id' => $payout->id, 'state' => 'Succeeded']);
    expect($settlements->payoutSummary($creator->id, true))->toMatchArray(['available' => 4768, 'pending' => 0, 'reserved' => 0]);
    moduleSignIn($this, $creator->fresh());
    $capture('07-settled', $this->get(route('earnings.index'))->assertOk()->assertSee('Net amount: PHP 499.00')->assertSee('PHP 47.68'));
});
