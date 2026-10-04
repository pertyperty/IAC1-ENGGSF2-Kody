<?php

namespace App\Services\Transactions;

use App\Jobs\Transactions\CreateProviderOperation;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Operations\BudgetAdmission;
use App\Services\Transactions\Xendit\Client;
use App\Services\Transactions\Xendit\ProviderResult;
use App\Services\Transactions\Xendit\Readiness;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class Payments
{
    public function purchase(User $actor, string $sessionId, #[\SensitiveParameter] array $data): string
    {
        return DB::transaction(function () use ($actor, $sessionId, $data): string {
            $user = app(FinancialAccounts::class)->lock($actor, $sessionId);
            app(FinancialAccounts::class)->confirm($user, $data);
            abort_unless(app(Readiness::class)->available(), 503, 'Payments are being prepared.');
            app(BudgetAdmission::class)->assert('payments');
            if (! Str::isUuid($data['confirmation_id'] ?? '') || ! in_array($data['confirmed'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)
                || ! isset(config('economy.packages')[$data['package'] ?? ''])) {
                throw ValidationException::withMessages(['purchase' => 'Choose and confirm a Kody package.']);
            }
            $package = config('economy.packages.'.$data['package']);
            $existing = DB::table('payment_purchases')->where('user_id', $user->id)->where('confirmation_id', $data['confirmation_id'])->first();
            if ($existing !== null) {
                if ($existing->package !== $data['package']) {
                    throw ValidationException::withMessages(['purchase' => 'This confirmation already identifies another package.']);
                }

                return $existing->id;
            }
            $id = (string) Str::uuid();
            DB::table('payment_purchases')->insert(['id' => $id, 'user_id' => $user->id, 'confirmation_id' => $data['confirmation_id'],
                'package' => $data['package'], 'kodebits' => $package['kodebits'], 'gross_minor' => $package['price_minor'],
                'fee_minor' => app(Readiness::class)->fee($package['price_minor']), 'fee_schedule' => json_encode(['basis_points' => config('xendit.payment_fee_basis_points'), 'fixed_minor' => config('xendit.payment_fee_fixed_minor'), 'policy_version' => config('economy.policy_version')], JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record($user->id, $user->id, 'payment.requested', 'payment_purchase', $id, ['package' => $data['package']]);
            Queue::connection('database')->pushOn('payments', (new CreateProviderOperation('payment', $id))->beforeCommit());

            return $id;
        }, 3);
    }

    public function create(string $id): void
    {
        $purchase = DB::transaction(function () use ($id): ?object {
            $row = DB::table('payment_purchases')->where('id', $id)->lockForUpdate()->first();
            if ($row === null || $row->state !== 'Queued') {
                return null;
            }
            abort_unless(app(Readiness::class)->available(), 503);
            DB::table('payment_purchases')->where('id', $id)->update(['state' => 'Creating', 'updated_at' => now()]);

            return $row;
        });
        if ($purchase === null) {
            return;
        }
        try {
            $proof = app(Client::class)->createPayment($purchase);
            $this->match($purchase, $proof);
            DB::transaction(function () use ($id, $proof): void {
                $row = DB::table('payment_purchases')->where('id', $id)->lockForUpdate()->firstOrFail();
                if ($row->provider_request_id !== null && $row->provider_request_id !== $proof->id) {
                    throw new RuntimeException('Conflicting payment reference.');
                }
                // Creation and browser return never credit KodeBits.
                $state = in_array($row->state, ['Succeeded', 'Refunding', 'Refunded'], true) ? $row->state : 'Pending';
                DB::table('payment_purchases')->where('id', $id)->update(['provider_request_id' => $proof->id, 'checkout_url' => $proof->checkout,
                    'state' => $state, 'updated_at' => now()]);
            });
        } catch (Throwable) {
            DB::table('payment_purchases')->where('id', $id)->where('state', 'Creating')->update(['state' => 'Review', 'updated_at' => now()]);
            throw new RuntimeException('Payment creation requires reconciliation; do not resend blindly.');
        }
    }

    public function reconcile(object $event): void
    {
        $callback = json_decode($event->proof, true, flags: JSON_THROW_ON_ERROR);
        $proof = app(Client::class)->retrievePayment($event->object_id);
        DB::transaction(function () use ($event, $callback, $proof): void {
            app(WalletLedger::class)->lock();
            $row = DB::table('payment_purchases')->where('id', $callback['reference'])->lockForUpdate()->firstOrFail();
            $this->match($row, $proof);
            if (($row->provider_request_id !== null && $row->provider_request_id !== $proof->id) || $proof->id !== $event->object_id
                || $callback['amount_minor'] !== $row->gross_minor || $callback['business_id'] !== $proof->businessId) {
                throw new RuntimeException('Payment proofs do not agree.');
            }
            if ($event->event === 'payment.capture') {
                if ($proof->status !== 'SUCCEEDED' || $callback['status'] !== 'SUCCEEDED' || $proof->paymentId !== $callback['payment_id']
                    || $proof->captures !== $callback['captures'] || array_sum($proof->captures) !== $row->gross_minor || $proof->captures === []) {
                    throw new RuntimeException('Captured payment is not fully verified.');
                }
                if ($row->state === 'Failed') {
                    throw new RuntimeException('Conflicting terminal payment state.');
                }
                if ($row->lot_id === null) {
                    $lot = app(WalletLedger::class)->credit($row->user_id, 'purchase:'.$row->id, 'Purchase', $row->kodebits, $row->gross_minor - $row->fee_minor);
                    DB::table('payment_purchases')->where('id', $row->id)->update(['state' => 'Succeeded', 'lot_id' => $lot->id,
                        'provider_request_id' => $proof->id, 'provider_payment_id' => $proof->paymentId, 'updated_at' => now()]);
                    app(AuditRecorder::class)->record(null, $row->user_id, 'payment.succeeded', 'payment_purchase', $row->id, ['gross_minor' => $row->gross_minor, 'fee_minor' => $row->fee_minor, 'kodebits' => $row->kodebits]);
                    app(FinancialNotices::class)->queue($row->user_id, 'purchase:'.$row->id, 'KodeBit purchase confirmed', ['kodebits' => $row->kodebits, 'amount_minor' => $row->gross_minor, 'fee_minor' => $row->fee_minor]);
                }
            } elseif ($row->lot_id === null && in_array($proof->status, ['FAILED', 'EXPIRED', 'CANCELED'], true)) {
                DB::table('payment_purchases')->where('id', $row->id)->update(['state' => 'Failed', 'provider_request_id' => $proof->id, 'updated_at' => now()]);
                app(FinancialNotices::class)->queue($row->user_id, 'purchase-failed:'.$row->id, 'KodeBit purchase unsuccessful', ['amount_minor' => $row->gross_minor]);
            } else {
                throw new RuntimeException('The provider status is not terminal or conflicts with the callback.');
            }
            DB::table('provider_webhooks')->where('id', $event->id)->update(['state' => 'Processed', 'processed_at' => now()]);
        }, 3);
    }

    private function match(object $purchase, ProviderResult $proof): void
    {
        if ($proof->reference !== $purchase->id || $proof->businessId !== config('xendit.business_id') || $proof->currency !== 'PHP'
            || $proof->amountMinor !== $purchase->gross_minor) {
            throw new RuntimeException('Unexpected purchase proof.');
        }
    }
}
