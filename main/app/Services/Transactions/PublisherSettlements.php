<?php

namespace App\Services\Transactions;

use App\Enums\Role;
use App\Jobs\Transactions\CreateProviderOperation;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Transactions\Xendit\Client;
use App\Services\Transactions\Xendit\ProviderResult;
use App\Services\Transactions\Xendit\Readiness;
use App\Support\ExactMoney;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PublisherSettlements
{
    public function claim(User $actor, string $sessionId, #[\SensitiveParameter] array $data): int
    {
        return DB::transaction(function () use ($actor, $sessionId, $data): int {
            $user = app(FinancialAccounts::class)->lock($actor, $sessionId);
            app(FinancialAccounts::class)->confirm($user, $data);
            abort_unless(in_array($user->account_role, [Role::Contributor, Role::Instructor], true), 403);
            $this->confirmation($data);
            $ledger = app(WalletLedger::class);
            $ledger->lock();
            $reference = 'creator:'.$user->id.':'.$data['confirmation_id'];
            $existing = DB::table('wallet_operations')->where('reference', $reference)->first();
            if ($existing !== null) {
                return $existing->kodebits;
            }
            $earnings = $this->earnings($user->id, 'KodeBits')->get();
            $kb = intdiv((int) $earnings->sum(fn ($row) => $row->kb_milli - $row->claimed_kb_milli), 1000);
            if ($kb < 1) {
                throw ValidationException::withMessages(['earnings' => 'No matured whole KodeBits are available yet. Fractional earnings remain saved.']);
            }
            $remaining = $kb * 1000;
            $minor = 0;
            foreach ($earnings as $row) {
                $available = $row->kb_milli - $row->claimed_kb_milli;
                if ($available === 0) {
                    continue;
                }
                $take = min($remaining, $available);
                $value = $take === $available ? $row->amount_minor - $row->claimed_minor
                    : intdiv(($row->amount_minor - $row->claimed_minor) * $take, $available);
                DB::table('publisher_earnings')->where('id', $row->id)->update(['claimed_kb_milli' => $row->claimed_kb_milli + $take, 'claimed_minor' => $row->claimed_minor + $value]);
                $minor += $value;
                $remaining -= $take;
                if ($remaining === 0) {
                    break;
                }
            }
            $ledger->credit($user->id, $reference, 'Creator', $kb, $minor);
            app(AuditRecorder::class)->record($user->id, $user->id, 'earnings.claimed', 'wallet', (string) $user->id, ['kodebits' => $kb, 'value_minor' => $minor, 'reference' => $reference]);
            app(FinancialNotices::class)->queue($user->id, $reference, 'Creator KodeBits claimed', ['kodebits' => $kb]);

            return $kb;
        }, 3);
    }

    public function request(User $actor, string $sessionId, #[\SensitiveParameter] array $data): string
    {
        return DB::transaction(function () use ($actor, $sessionId, $data): string {
            $user = app(FinancialAccounts::class)->lock($actor, $sessionId);
            abort_unless($user->account_role === Role::Instructor && app(Readiness::class)->available(), $user->account_role === Role::Instructor ? 503 : 403);
            app(FinancialAccounts::class)->confirm($user, $data);
            $this->confirmation($data);
            $gross = ExactMoney::minor($data['amount']);
            $fee = config('xendit.payout_fee_minor');
            if ($gross < config('economy.minimum_payout_minor') || $gross <= $fee || (int) ($data['quoted_fee_minor'] ?? -1) !== $fee) {
                throw ValidationException::withMessages(['amount' => 'Choose at least PHP500 and confirm the current quoted fee.']);
            }
            $recipient = $this->recipient($data);
            $ledger = app(WalletLedger::class);
            $ledger->lock();
            $existing = DB::table('payout_requests')->where('user_id', $user->id)->where('confirmation_id', $data['confirmation_id'])->first();
            if ($existing !== null) {
                if ($existing->gross_minor !== $gross || Crypt::decryptString($existing->recipient) !== json_encode($recipient, JSON_THROW_ON_ERROR)) {
                    throw ValidationException::withMessages(['confirmation_id' => 'This confirmation identifies a different payout.']);
                }

                return $existing->id;
            }
            $earnings = $this->earnings($user->id, 'Cash')->get();
            if ($earnings->sum(fn ($row) => $row->amount_minor - $row->claimed_minor - $row->reserved_minor) < $gross) {
                throw ValidationException::withMessages(['amount' => 'The requested amount exceeds your available matured PHP earnings.']);
            }
            $id = (string) Str::uuid();
            DB::table('payout_requests')->insert(['id' => $id, 'user_id' => $user->id, 'confirmation_id' => $data['confirmation_id'],
                'gross_minor' => $gross, 'fee_minor' => $fee, 'recipient' => Crypt::encryptString(json_encode($recipient, JSON_THROW_ON_ERROR)), 'created_at' => now(), 'updated_at' => now()]);
            $remaining = $gross;
            foreach ($earnings as $row) {
                $take = min($remaining, $row->amount_minor - $row->claimed_minor - $row->reserved_minor);
                if ($take <= 0) {
                    continue;
                }
                DB::table('payout_allocations')->insert(['payout_id' => $id, 'earning_id' => $row->id, 'amount_minor' => $take]);
                DB::table('publisher_earnings')->where('id', $row->id)->increment('reserved_minor', $take);
                $remaining -= $take;
                if ($remaining === 0) {
                    break;
                }
            }
            app(AuditRecorder::class)->record($user->id, $user->id, 'payout.requested', 'payout_request', $id, ['gross_minor' => $gross, 'fee_minor' => $fee]);
            app(FinancialNotices::class)->queue($user->id, 'payout-request:'.$id, 'Creator payout awaiting review', ['amount_minor' => $gross, 'fee_minor' => $fee]);

            return $id;
        }, 3);
    }

    public function review(User $actor, string $sessionId, string $id, #[\SensitiveParameter] array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $id, $data): void {
            $staff = app(FinancialAccounts::class)->lock($actor, $sessionId, true);
            app(FinancialAccounts::class)->confirm($staff, $data);
            app(WalletLedger::class)->lock();
            $row = DB::table('payout_requests')->where('id', $id)->lockForUpdate()->firstOrFail();
            abort_if($row->user_id === $staff->id, 403);
            abort_unless($row->state === 'PendingReview', 409);
            $approved = ($data['decision'] ?? '') === 'Approved';
            if (! in_array($data['decision'] ?? '', ['Approved', 'Rejected'], true) || empty(trim($data['review_notes'] ?? ''))
                || ($approved && ! in_array($data['recipient_verified'] ?? null, [true, 1, '1', 'on', 'yes', 'true'], true))) {
                throw ValidationException::withMessages(['review_notes' => 'Record the decision and credibility/recipient verification notes.']);
            }
            if ($approved) {
                abort_unless(app(Readiness::class)->available(), 503);
                Queue::connection('database')->pushOn('payments', (new CreateProviderOperation('payout', $id))->beforeCommit());
            } else {
                $this->settle($row, false);
            }
            DB::table('payout_requests')->where('id', $id)->update(['state' => $approved ? 'Queued' : 'Rejected', 'reviewed_by' => $staff->id,
                'review_notes' => $data['review_notes'], 'reviewed_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record($staff->id, $row->user_id, 'payout.reviewed', 'payout_request', $id, ['decision' => $data['decision']]);
            app(FinancialNotices::class)->queue($row->user_id, 'payout-review:'.$id, 'Creator payout '.strtolower($data['decision']), ['amount_minor' => $row->gross_minor]);
        }, 3);
    }

    public function create(string $id): void
    {
        $row = DB::transaction(function () use ($id): ?object {
            $row = DB::table('payout_requests')->where('id', $id)->lockForUpdate()->first();
            if ($row === null || $row->state !== 'Queued') {
                return null;
            }
            abort_unless(app(Readiness::class)->available(), 503);
            DB::table('payout_requests')->where('id', $id)->update(['state' => 'Creating', 'updated_at' => now()]);

            return $row;
        });
        if ($row === null) {
            return;
        }
        try {
            $recipient = json_decode(Crypt::decryptString($row->recipient), true, flags: JSON_THROW_ON_ERROR);
            $proof = app(Client::class)->createPayout($row, $recipient);
            $this->verify($row, $proof, $recipient);
            DB::table('payout_requests')->where('id', $id)->where('state', 'Creating')->update(['state' => 'Pending', 'provider_id' => $proof->id, 'updated_at' => now()]);
        } catch (Throwable) {
            DB::table('payout_requests')->where('id', $id)->where('state', 'Creating')->update(['state' => 'Review', 'updated_at' => now()]);
            throw new RuntimeException('Payout creation requires reconciliation.');
        }
    }

    public function reconcile(object $event): void
    {
        $callback = json_decode($event->proof, true, flags: JSON_THROW_ON_ERROR);
        $proof = app(Client::class)->retrievePayout($event->object_id);
        DB::transaction(function () use ($event, $callback, $proof): void {
            app(WalletLedger::class)->lock();
            $row = DB::table('payout_requests')->where('id', $callback['reference'])->lockForUpdate()->firstOrFail();
            $recipient = json_decode(Crypt::decryptString($row->recipient), true, flags: JSON_THROW_ON_ERROR);
            $this->verify($row, $proof, $recipient);
            if ($row->reviewed_by === null || ($row->provider_id !== null && $row->provider_id !== $proof->id)
                || $callback['amount_minor'] !== $proof->amountMinor || $callback['destination_minor'] !== $proof->destinationMinor || $callback['status'] !== $proof->status) {
                throw new RuntimeException('Payout callback does not match the approved request.');
            }
            if ($proof->status === 'REVERSED') {
                $this->reverse($row);
            } elseif ($proof->status === 'PENDING_COMPLIANCE_REVIEW') {
                DB::table('payout_requests')->where('id', $row->id)->update(['state' => 'Review', 'provider_id' => $proof->id, 'updated_at' => now()]);
            } elseif (in_array($proof->status, ['SUCCEEDED', 'FAILED', 'REJECTED'], true)) {
                $success = $proof->status === 'SUCCEEDED';
                if ($row->state === 'Reversed') {
                    throw new RuntimeException('A reversed payout cannot settle again.');
                }
                if (in_array($row->state, ['Succeeded', 'Failed', 'Rejected'], true)) {
                    if (($row->state === 'Succeeded') !== $success) {
                        throw new RuntimeException('Conflicting payout outcome.');
                    }
                } else {
                    $this->settle($row, $success);
                    DB::table('payout_requests')->where('id', $row->id)->update(['state' => $success ? 'Succeeded' : 'Failed', 'provider_id' => $proof->id, 'updated_at' => now()]);
                    app(AuditRecorder::class)->record(null, $row->user_id, $success ? 'payout.succeeded' : 'payout.failed', 'payout_request', $row->id, ['gross_minor' => $row->gross_minor, 'fee_minor' => $row->fee_minor]);
                    app(FinancialNotices::class)->queue($row->user_id, 'payout-outcome:'.$row->id, $success ? 'Creator payout confirmed' : 'Creator payout unsuccessful', ['amount_minor' => $row->gross_minor, 'fee_minor' => $row->fee_minor]);
                }
            } else {
                throw new RuntimeException('Payout is not terminal.');
            }
            DB::table('provider_webhooks')->where('id', $event->id)->update(['state' => 'Processed', 'processed_at' => now()]);
        }, 3);
    }

    private function settle(object $payout, bool $success): void
    {
        $allocations = DB::table('payout_allocations')->where('payout_id', $payout->id)->orderBy('earning_id')->get();
        foreach ($allocations as $allocation) {
            $row = DB::table('publisher_earnings')->where('id', $allocation->earning_id)->lockForUpdate()->firstOrFail();
            if ($row->reserved_minor < $allocation->amount_minor) {
                throw new RuntimeException('Payout reservations require review.');
            }
            DB::table('publisher_earnings')->where('id', $row->id)->update(['reserved_minor' => $row->reserved_minor - $allocation->amount_minor,
                'claimed_minor' => $row->claimed_minor + ($success ? $allocation->amount_minor : 0)]);
        }
    }

    private function reverse(object $payout): void
    {
        if (DB::table('payout_reversals')->where('payout_id', $payout->id)->exists()) {
            return;
        }
        // The authenticated event and backend retrieval must both say REVERSED.
        // Retain the quoted fee; only the returned transfer amount becomes available.
        if (in_array($payout->state, ['Pending', 'Review', 'Creating'], true)) {
            // A verified REVERSED retrieval itself confirms the earlier success;
            // callbacks may arrive out of order. Settle its still-held allocations first.
            $this->settle($payout, true);
        } elseif ($payout->state !== 'Succeeded') {
            throw new RuntimeException('A reversal needs the original confirmed settlement.');
        }
        $returned = $payout->gross_minor - $payout->fee_minor;
        $remaining = $returned;
        foreach (DB::table('payout_allocations')->where('payout_id', $payout->id)->orderBy('earning_id')->get() as $allocation) {
            $earning = DB::table('publisher_earnings')->where('id', $allocation->earning_id)->lockForUpdate()->firstOrFail();
            $take = min($remaining, $allocation->amount_minor);
            if ($earning->claimed_minor < $take) {
                throw new RuntimeException('Returned earnings require reconciliation.');
            }
            DB::table('publisher_earnings')->where('id', $earning->id)->decrement('claimed_minor', $take);
            $remaining -= $take;
        }
        if ($remaining !== 0) {
            throw new RuntimeException('Payout allocation does not cover the reversal.');
        }
        DB::table('payout_reversals')->insert(['payout_id' => $payout->id, 'returned_minor' => $returned, 'created_at' => now()]);
        DB::table('payout_requests')->where('id', $payout->id)->update(['state' => 'Reversed', 'updated_at' => now()]);
        app(AuditRecorder::class)->record(null, $payout->user_id, 'payout.reversed', 'payout_request', $payout->id, ['returned_minor' => $returned, 'retained_fee_minor' => $payout->fee_minor]);
        app(FinancialNotices::class)->queue($payout->user_id, 'payout-reversed:'.$payout->id, 'Creator payout returned', ['amount_minor' => $returned, 'fee_minor' => $payout->fee_minor]);
    }

    private function verify(object $row, ProviderResult $proof, array $recipient): void
    {
        if ($proof->reference !== $row->id || $proof->amountMinor !== $row->gross_minor - $row->fee_minor || $proof->destinationMinor !== $row->gross_minor - $row->fee_minor
            || ($proof->businessId !== null && $proof->businessId !== config('xendit.business_id')) || collect($recipient['account_details'])->contains(fn ($value, $key) => ($proof->recipient[$key] ?? null) !== $value)) {
            throw new RuntimeException('Unexpected payout proof.');
        }
    }

    private function earnings(int $userId, string $kind): Builder
    {
        return DB::table('publisher_earnings')->where('user_id', $userId)->where('kind', $kind)->where('status', 'Available')
            ->where('available_at', '<=', now())->orderBy('created_at')->orderBy('id')->lockForUpdate();
    }

    private function confirmation(array $data): void
    {
        if (! Str::isUuid($data['confirmation_id'] ?? '')) {
            throw ValidationException::withMessages(['confirmation_id' => 'Reload the confirmation form.']);
        }
    }

    private function recipient(#[\SensitiveParameter] array $data): array
    {
        $fields = Validator::make($data, [
            'given_name' => ['required', 'string', 'max:50'], 'surname' => ['required', 'string', 'max:50'],
            'mobile' => ['required', 'regex:/^09[0-9]{9}$/D'], 'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'], 'province' => ['required', 'string', 'max:255'], 'postal_code' => ['required', 'regex:/^[0-9]{4}$/D'],
            'account_holder_name' => ['required', 'string', 'max:255'],
        ])->validate();

        return ['type' => 'INDIVIDUAL', 'given_name' => $fields['given_name'], 'surname' => $fields['surname'], 'relationship' => 'SUPPLIER',
            'details' => ['personal_mobile_number' => '+63'.substr($fields['mobile'], 1)],
            'address' => ['country' => 'PH', 'street_line_1' => $fields['street'], 'city' => $fields['city'], 'province_state' => $fields['province'], 'postal_code' => $fields['postal_code']],
            'account_details' => ['currency' => 'PHP', 'account_country' => 'PH', 'account_holder_name' => $fields['account_holder_name'], 'account_number' => $fields['mobile'], 'routing_type_1' => 'WALLET', 'routing_value_1' => 'PH_GCASH']];
    }
}
