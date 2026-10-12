<?php

use App\Enums\Role;
use App\Mail\Transactions\FinancialReceipt;
use App\Services\Challenges\ChallengeSubmissions;
use App\Services\Transactions\Payments;
use App\Services\Transactions\ProviderWebhooks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('F01 owned status never treats a browser return as payment and verified receipts are downloadable and mailed', function () {
    economyProvider();
    $user = moduleAccount(Role::Learner);
    moduleSignIn($this, $user);
    $id = app(Payments::class)->purchase($user->fresh(), session()->getId(), economyConfirm(['package' => 'starter']));
    $this->get(route('wallet.purchase.show', $id).'?status=SUCCEEDED')->assertOk();
    $this->getJson(route('wallet.purchase.status', $id))->assertJson(['state' => 'Queued', 'verified' => false, 'available' => 0, 'receipt' => null]);
    $this->get(route('wallet.receipt', $id))->assertNotFound();
    $proof = economyPaymentProof($id);
    economyCallback('payment.capture', $proof);
    Http::fake(['https://api.xendit.co/v3/payment_requests/*' => Http::response($proof)]);
    app(ProviderWebhooks::class)->reconcile('test-event');
    $this->getJson(route('wallet.purchase.status', $id))->assertJson(['state' => 'Succeeded', 'verified' => true, 'available' => 105]);
    $this->get(route('wallet.receipt', $id))->assertOk()->assertSee($id)->assertHeader('Cache-Control', 'no-store, private');
    $pdf = $this->get(route('wallet.receipt', [$id, 'pdf' => 1]))->assertOk();
    expect($pdf->getContent())->toStartWith('%PDF-');
    $notice = DB::table('financial_deliveries')->where('reference', 'purchase:'.$id)->sole();
    expect((new FinancialReceipt($notice->id, $notice->title, json_decode($notice->details, true)))->attachments())->toHaveCount(1);
    moduleSignIn($this, moduleAccount(Role::Learner));
    foreach (['wallet.purchase.show', 'wallet.purchase.status', 'wallet.receipt'] as $route) {
        $this->get(route($route, $id))->assertNotFound();
    }
});

test('C03 global admission caps are serialized across quests and idempotent retries do not consume more work', function () {
    readyJudge();
    config(['judge0.pending_per_user' => 1, 'judge0.confirmations_per_24h' => 3]);
    $user = moduleAccount(Role::Learner);
    $first = challengeFixture(true);
    $next = challengeFixture(true);
    $data = attemptData($first);
    $service = app(ChallengeSubmissions::class);
    $submission = $service->submit($user, 'module-test-session', $first, $data);
    expect($service->submit($user, 'module-test-session', $first, $data)->id)->toBe($submission->id);
    expect(fn () => submitAttempt($next, $user))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('challenge_submissions', 1);
    evaluateAttempt($submission);
    config(['judge0.confirmations_per_24h' => 1]);
    expect(fn () => submitAttempt($next, $user))->toThrow(ValidationException::class);
    $this->travel(25)->hours();
    $user->forceFill(['active_session_expires_at' => now()->addHour()])->save();
    expect(submitAttempt($next, $user)->attempt)->toBe(1);
});
