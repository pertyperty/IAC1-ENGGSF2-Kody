<?php

namespace App\Services\Transactions;

use App\Jobs\Transactions\ReconcileProviderEvent;
use App\Services\Transactions\Xendit\Client;
use App\Services\Transactions\Xendit\Readiness;
use App\Support\ExactMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProviderWebhooks
{
    public function receive(#[\SensitiveParameter] string $token, string $raw, ?string $headerId): void
    {
        abort_unless(app(Readiness::class)->available(), 503);
        abort_unless(hash_equals(config('xendit.callback_token'), $token), 403);
        abort_if(strlen($raw) > 65536, 413);
        try {
            $payload = ExactMoney::decode($raw);
            if (($payload['business_id'] ?? null) !== config('xendit.business_id') || ! is_array($payload['data'] ?? null)) {
                throw new RuntimeException('Wrong merchant.');
            }
            $event = $payload['event'] ?? '';
            $data = $payload['data'];
            $data['business_id'] ??= $payload['business_id'];
            $client = app(Client::class);
            if (in_array($event, ['payment.capture', 'payment.failure', 'payment_request.expiry'], true)) {
                $data['latest_payment_id'] ??= $data['payment_id'] ?? null;
                $result = $client->payment($data);
                $object = $result->id;
                $expected = match ($event) {
                    'payment.capture' => 'SUCCEEDED', 'payment.failure' => 'FAILED', default => 'EXPIRED'
                };
            } elseif (in_array($event, ['v3_payout.succeeded', 'v3_payout.failed', 'v3_payout.rejected', 'v3_payout.reversed', 'v3_payout.pending_compliance'], true)) {
                $result = $client->payout($data);
                $object = $result->id;
                $expected = match ($event) {
                    'v3_payout.succeeded' => 'SUCCEEDED', 'v3_payout.failed' => 'FAILED', 'v3_payout.rejected' => 'REJECTED', 'v3_payout.reversed' => 'REVERSED', default => 'PENDING_COMPLIANCE_REVIEW'
                };
            } elseif (in_array($event, ['refund.succeeded', 'refund.failed'], true)) {
                $result = $client->refund($data);
                $object = $result->id;
                $expected = $event === 'refund.succeeded' ? 'SUCCEEDED' : 'FAILED';
            } else {
                throw new RuntimeException('Unsupported provider event.');
            }
            if ($result->status !== $expected || ! Str::isUuid($result->reference) || $result->businessId !== $payload['business_id']) {
                throw new RuntimeException('Incomplete callback proof.');
            }
            $proof = ['reference' => $result->reference, 'amount_minor' => $result->amountMinor, 'currency' => $result->currency,
                'business_id' => $payload['business_id'], 'status' => $result->status, 'payment_id' => $result->paymentId,
                'captures' => $result->captures, 'payment_request_id' => $result->paymentRequestId, 'destination_minor' => $result->destinationMinor];
            if (str_starts_with($event, 'refund.')) {
                $proof['refund_fee_minor'] = isset($data['refund_fee_amount']) ? ExactMoney::minor($data['refund_fee_amount']) : null;
            }
            // Semantic digest ignores delivery timestamps and discards raw recipient/customer data.
            $json = json_encode($proof, JSON_THROW_ON_ERROR);
            $digest = hash('sha256', $event.'|'.$object.'|'.$json);
            $id = $headerId ?? hash('sha256', $event.'|'.$object);
            if (! preg_match('/^[a-zA-Z0-9_-]{1,100}$/D', $id)) {
                throw new RuntimeException('Invalid event reference.');
            }
        } catch (Throwable) {
            abort(422, 'Invalid financial callback.');
        }
        DB::transaction(function () use ($id, $event, $object, $digest, $json): void {
            // The unique key handles simultaneous callbacks without a check/insert race.
            DB::table('provider_webhooks')->insertOrIgnore(['id' => $id, 'event' => $event, 'object_id' => $object,
                'digest' => $digest, 'proof' => $json, 'created_at' => now()]);
            $row = DB::table('provider_webhooks')->where('id', $id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals($row->digest, $digest), 409, 'Conflicting provider event.');
            if ($row->state === 'Pending' && $row->last_queued_at === null) {
                DB::table('provider_webhooks')->where('id', $id)->update(['last_queued_at' => now()]);
                Queue::connection('database')->pushOn('payments', (new ReconcileProviderEvent($id))->beforeCommit());
            }
        }, 3);
    }

    public function reconcile(string $id): void
    {
        $row = DB::table('provider_webhooks')->find($id);
        if ($row === null || $row->state === 'Processed') {
            return;
        }
        try {
            if (str_starts_with($row->event, 'payment')) {
                app(Payments::class)->reconcile($row);
            } elseif (str_starts_with($row->event, 'v3_payout.')) {
                app(PublisherSettlements::class)->reconcile($row);
            } else {
                app(Refunds::class)->reconcile($row);
            }
        } catch (Throwable) {
            DB::table('provider_webhooks')->where('id', $id)->where('state', '<>', 'Processed')->update(['state' => 'Review']);
            throw new RuntimeException('Financial callback requires reconciliation.');
        }
    }
}
