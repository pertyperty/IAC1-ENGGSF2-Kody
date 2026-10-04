<?php

namespace App\Services\Transactions;

use App\Jobs\Transactions\CreateProviderOperation;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Transactions\Xendit\Client;
use App\Services\Transactions\Xendit\Readiness;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class Refunds
{
    public function request(User $actor, string $sessionId, string $kind, string $target, #[\SensitiveParameter] array $data): string
    {
        return DB::transaction(function () use ($actor, $sessionId, $kind, $target, $data): string {
            $user = app(FinancialAccounts::class)->lock($actor, $sessionId);
            app(FinancialAccounts::class)->confirm($user, $data);
            if (! in_array($kind, ['Purchase', 'Access'], true) || ! Str::isUuid($target) || empty(trim($data['reason'] ?? '')) || mb_strlen($data['reason']) > 500) {
                throw ValidationException::withMessages(['reason' => 'Explain your refund request in up to 500 characters.']);
            }
            app(WalletLedger::class)->lock();
            $existing = DB::table('refund_requests')->where('kind', $kind)->where('target_id', $target)->whereNotIn('state', ['Rejected', 'Failed'])->first();
            if ($existing !== null) {
                abort_unless($existing->user_id === $user->id, 404);

                return $existing->id;
            }
            if ($kind === 'Purchase') {
                abort_unless(app(Readiness::class)->available(), 503, 'Purchase refunds require the configured payment provider.');
                $purchase = DB::table('payment_purchases')->where('id', $target)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                $this->purchaseEligible($purchase);
                $lot = DB::table('wallet_lots')->where('id', $purchase->lot_id)->lockForUpdate()->firstOrFail();
                if ($lot->remaining_kb !== $lot->initial_kb || $lot->remaining_minor !== $lot->initial_minor || $lot->reserved_kb !== 0) {
                    throw ValidationException::withMessages(['refund' => 'The entire original purchase must be unspent and unreserved.']);
                }
                DB::table('wallet_lots')->where('id', $lot->id)->update(['reserved_kb' => $lot->initial_kb]);
                DB::table('wallet_accounts')->where('user_id', $user->id)->increment('reserved', $lot->initial_kb);
                DB::table('payment_purchases')->where('id', $purchase->id)->update(['state' => 'Refunding', 'updated_at' => now()]);
            } else {
                $purchase = DB::table('content_purchases')->where('id', $target)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                $this->accessEligible($purchase);
            }
            $id = (string) Str::uuid();
            DB::table('refund_requests')->insert(['id' => $id, 'user_id' => $user->id, 'kind' => $kind, 'target_id' => $target,
                'reason' => $data['reason'], 'fee_minor' => $kind === 'Purchase' ? config('xendit.refund_fee_minor') : 0, 'created_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record($user->id, $user->id, 'refund.requested', 'refund_request', $id, ['kind' => $kind, 'target_id' => $target]);
            app(FinancialNotices::class)->queue($user->id, 'refund-request:'.$id, 'Refund awaiting review', []);

            return $id;
        }, 3);
    }

    public function review(User $actor, string $sessionId, string $id, #[\SensitiveParameter] array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $id, $data): void {
            $staff = app(FinancialAccounts::class)->lock($actor, $sessionId, true);
            app(FinancialAccounts::class)->confirm($staff, $data);
            $candidate = DB::table('refund_requests')->find($id) ?? abort(404);
            abort_if($candidate->user_id === $staff->id, 403);
            // Serialize assessment/attempt writers before reversing their access.
            User::whereKey($candidate->user_id)->lockForUpdate()->firstOrFail();
            $ledger = app(WalletLedger::class);
            $ledger->lock();
            $row = DB::table('refund_requests')->where('id', $id)->lockForUpdate()->firstOrFail();
            abort_unless($row->state === 'PendingReview', 409);
            if (! in_array($data['decision'] ?? '', ['Approved', 'Rejected'], true) || empty(trim($data['review_notes'] ?? ''))) {
                throw ValidationException::withMessages(['review_notes' => 'Record the refund decision and reason.']);
            }
            $approved = $data['decision'] === 'Approved';
            if ($row->kind === 'Access') {
                if ($approved) {
                    $purchase = DB::table('content_purchases')->where('id', $row->target_id)->lockForUpdate()->firstOrFail();
                    $this->accessEligible($purchase);
                    $earning = DB::table('publisher_earnings')->where('purchase_id', $purchase->id)->lockForUpdate()->firstOrFail();
                    if ($earning->claimed_minor !== 0 || $earning->reserved_minor !== 0 || $earning->claimed_kb_milli !== 0) {
                        throw ValidationException::withMessages(['refund' => 'Settled earnings require an exceptional-remedy review.']);
                    }
                    DB::table('publisher_earnings')->where('id', $earning->id)->update(['status' => 'Reversed']);
                    $ledger->cash('access-refund:'.$row->id, -$purchase->platform_minor);
                    $ledger->credit($purchase->user_id, 'access-refund:'.$row->id, 'Refund', $purchase->kodebits, $purchase->value_minor, $purchase->purchased_kb);
                    DB::table('content_purchases')->where('id', $purchase->id)->update(['status' => 'Refunded', 'refunded_at' => now()]);
                    DB::table('content_entitlements')->where('user_id', $purchase->user_id)->where('content_type', $purchase->content_type)->where('content_id', $purchase->content_id)->update(['revoked_at' => now(), 'updated_at' => now()]);
                }
                $state = $approved ? 'Succeeded' : 'Rejected';
            } elseif ($approved) {
                abort_unless(app(Readiness::class)->available(), 503);
                $purchase = DB::table('payment_purchases')->where('id', $row->target_id)->lockForUpdate()->firstOrFail();
                $this->purchaseEligible($purchase, true);
                $cost = $purchase->fee_minor + $row->fee_minor;
                if ($ledger->maturedCash() < $cost) {
                    throw ValidationException::withMessages(['refund' => 'Fund the platform refund-fee reserve before approving this full-gross refund.']);
                }
                DB::table('economy_locks')->where('id', 1)->increment('reserved_minor', $cost);
                DB::table('refund_requests')->where('id', $id)->update(['reserved_minor' => $cost]);
                Queue::connection('database')->pushOn('payments', (new CreateProviderOperation('refund', $id))->beforeCommit());
                $state = 'Queued';
            } else {
                $this->releasePurchase($row);
                $state = 'Rejected';
            }
            DB::table('refund_requests')->where('id', $id)->update(['state' => $state, 'reviewed_by' => $staff->id, 'review_notes' => $data['review_notes'], 'updated_at' => now()]);
            app(AuditRecorder::class)->record($staff->id, $row->user_id, 'refund.reviewed', 'refund_request', $id, ['decision' => $data['decision'], 'outcome' => $state]);
            app(FinancialNotices::class)->queue($row->user_id, 'refund-review:'.$id, 'Refund '.strtolower($data['decision']), []);
        }, 3);
    }

    public function create(string $id): void
    {
        $row = DB::transaction(function () use ($id): ?object {
            $row = DB::table('refund_requests')->where('id', $id)->lockForUpdate()->first();
            if ($row === null || $row->state !== 'Queued' || $row->kind !== 'Purchase') {
                return null;
            }
            abort_unless(app(Readiness::class)->available(), 503);
            DB::table('refund_requests')->where('id', $id)->update(['state' => 'Creating', 'updated_at' => now()]);

            return $row;
        });
        if ($row === null) {
            return;
        }
        try {
            $purchase = DB::table('payment_purchases')->find($row->target_id);
            $proof = app(Client::class)->createRefund($row, $purchase);
            if ($proof->reference !== $row->id || $proof->paymentRequestId !== $purchase->provider_request_id || $proof->amountMinor !== $purchase->gross_minor) {
                throw new RuntimeException('Unexpected refund response.');
            }
            DB::table('refund_requests')->where('id', $id)->where('state', 'Creating')->update(['state' => 'Pending', 'provider_id' => $proof->id, 'updated_at' => now()]);
        } catch (Throwable) {
            DB::table('refund_requests')->where('id', $id)->where('state', 'Creating')->update(['state' => 'Review', 'updated_at' => now()]);
            throw new RuntimeException('Refund creation requires reconciliation; do not resend blindly.');
        }
    }

    public function reconcile(object $event): void
    {
        $proof = json_decode($event->proof, true, flags: JSON_THROW_ON_ERROR);
        // Refund API documents final status through authenticated callbacks,
        // rather than an invented GET endpoint. Verify every expected field.
        DB::transaction(function () use ($event, $proof): void {
            $ledger = app(WalletLedger::class);
            $ledger->lock();
            $row = DB::table('refund_requests')->where('id', $proof['reference'])->lockForUpdate()->firstOrFail();
            $purchase = DB::table('payment_purchases')->where('id', $row->target_id)->lockForUpdate()->firstOrFail();
            if ($row->kind !== 'Purchase' || $row->reviewed_by === null || ! in_array($row->state, ['Creating', 'Pending', 'Review', 'Succeeded', 'Failed'], true)
                || ($row->provider_id !== null && $row->provider_id !== $event->object_id) || $proof['amount_minor'] !== $purchase->gross_minor
                || $proof['business_id'] !== config('xendit.business_id') || $proof['payment_request_id'] !== $purchase->provider_request_id
                || ($proof['refund_fee_minor'] !== null && $proof['refund_fee_minor'] !== $row->fee_minor)) {
                throw new RuntimeException('Refund callback does not match the approved request.');
            }
            $success = $event->event === 'refund.succeeded';
            if (in_array($row->state, ['Succeeded', 'Failed'], true)) {
                if (($row->state === 'Succeeded') !== $success) {
                    throw new RuntimeException('Conflicting refund outcome.');
                }
            } else {
                if ($success) {
                    $lot = DB::table('wallet_lots')->where('id', $purchase->lot_id)->lockForUpdate()->firstOrFail();
                    if ($lot->reserved_kb !== $lot->initial_kb || $lot->remaining_kb !== $lot->initial_kb) {
                        throw new RuntimeException('Refund lot requires review.');
                    }
                    $operation = $ledger->operation($row->user_id, 'purchase-refund:'.$row->id, 'PurchaseRefund', -$lot->initial_kb, -$lot->initial_minor);
                    $ledger->entry($operation, $lot->id, -$lot->initial_kb, -$lot->initial_minor);
                    DB::table('wallet_lots')->where('id', $lot->id)->update(['remaining_kb' => 0, 'remaining_minor' => 0, 'reserved_kb' => 0, 'remaining_purchased_kb' => 0]);
                    DB::table('wallet_accounts')->where('user_id', $row->user_id)->update(['balance' => DB::raw('balance - '.$lot->initial_kb), 'reserved' => DB::raw('reserved - '.$lot->initial_kb)]);
                    DB::table('economy_locks')->where('id', 1)->decrement('reserved_minor', $row->reserved_minor);
                    $ledger->cash('purchase-refund-fee:'.$row->id, -$row->reserved_minor);
                    DB::table('payment_purchases')->where('id', $purchase->id)->update(['state' => 'Refunded', 'updated_at' => now()]);
                } else {
                    $this->releasePurchase($row);
                }
                DB::table('refund_requests')->where('id', $row->id)->update(['state' => $success ? 'Succeeded' : 'Failed', 'provider_id' => $event->object_id, 'reserved_minor' => 0, 'updated_at' => now()]);
                app(AuditRecorder::class)->record(null, $row->user_id, $success ? 'refund.succeeded' : 'refund.failed', 'refund_request', $row->id, ['gross_minor' => $purchase->gross_minor]);
                app(FinancialNotices::class)->queue($row->user_id, 'refund-outcome:'.$row->id, $success ? 'Purchase refund confirmed' : 'Purchase refund unsuccessful', ['amount_minor' => $purchase->gross_minor]);
            }
            DB::table('provider_webhooks')->where('id', $event->id)->update(['state' => 'Processed', 'processed_at' => now()]);
        }, 3);
    }

    private function releasePurchase(object $refund): void
    {
        $purchase = DB::table('payment_purchases')->where('id', $refund->target_id)->lockForUpdate()->firstOrFail();
        $lot = DB::table('wallet_lots')->where('id', $purchase->lot_id)->lockForUpdate()->firstOrFail();
        DB::table('wallet_lots')->where('id', $lot->id)->update(['reserved_kb' => 0]);
        DB::table('wallet_accounts')->where('user_id', $refund->user_id)->decrement('reserved', $lot->reserved_kb);
        if ($refund->reserved_minor > 0) {
            DB::table('economy_locks')->where('id', 1)->decrement('reserved_minor', $refund->reserved_minor);
        }
        DB::table('payment_purchases')->where('id', $purchase->id)->update(['state' => 'Succeeded', 'updated_at' => now()]);
    }

    private function purchaseEligible(object $purchase, bool $reserved = false): void
    {
        if ($purchase->state !== ($reserved ? 'Refunding' : 'Succeeded') || $purchase->lot_id === null
            || CarbonImmutable::parse($purchase->created_at)->addDays(config('economy.purchase_refund_days'))->lessThan(now())) {
            throw ValidationException::withMessages(['refund' => 'This purchase is outside the unused-purchase courtesy policy. Contact staff for an exceptional remedy.']);
        }
    }

    private function accessEligible(object $purchase): void
    {
        $used = match ($purchase->content_type) {
            'module' => DB::table('learning_activity_days')->where('user_id', $purchase->user_id)->where('level', 'module-'.$purchase->content_id)->exists(),
            'course' => DB::table('course_module_progress as progress')->join('course_enrollments as enrollment', 'enrollment.id', '=', 'progress.enrollment_id')->where('enrollment.user_id', $purchase->user_id)->where('enrollment.course_id', $purchase->content_id)->whereNotNull('progress.completed_at')->exists(),
            'challenge' => DB::table('challenge_participations')->where('user_id', $purchase->user_id)->where('challenge_id', $purchase->content_id)->whereNull('weekly_event_id')->where('attempts', '>', 0)->exists(),
        };
        if ($purchase->status !== 'Active' || $used || CarbonImmutable::parse($purchase->created_at)->addDays(config('economy.access_refund_days'))->lessThan(now())) {
            throw ValidationException::withMessages(['refund' => 'Only unused access within seven days qualifies for the courtesy policy. Contact staff for an exceptional remedy.']);
        }
    }
}
