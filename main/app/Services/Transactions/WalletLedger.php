<?php

namespace App\Services\Transactions;

use App\Services\Administration\AuditRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class WalletLedger
{
    public function lock(): object
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Ledger changes require a surrounding atomic domain transaction.');
        }

        return DB::table('economy_locks')->where('id', 1)->lockForUpdate()->firstOrFail();
    }

    public function snapshot(int $userId): array
    {
        $row = DB::table('wallet_accounts')->where('user_id', $userId)->first();

        return ['balance' => (int) ($row?->balance ?? 0), 'reserved' => (int) ($row?->reserved ?? 0),
            'available' => (int) ($row?->balance ?? 0) - (int) ($row?->reserved ?? 0)];
    }

    public function credit(int $userId, string $reference, string $kind, int $kb, int $minor, ?int $purchasedKb = null): object
    {
        $this->lock();
        if (! in_array($kind, ['Purchase', 'Reward', 'Creator', 'Refund'], true) || $kb <= 0 || $minor < 0) {
            throw new LogicException('Invalid internal ledger credit.');
        }
        $purchasedKb ??= $kind === 'Purchase' ? $kb : 0;
        if ($purchasedKb < 0 || $purchasedKb > $kb) {
            throw new LogicException('Invalid purchased-token provenance.');
        }
        $existing = DB::table('wallet_operations')->where('reference', $reference)->first();
        if ($existing !== null) {
            if ($existing->user_id !== $userId || $existing->kodebits !== $kb || $existing->value_minor !== $minor || $existing->kind !== $kind) {
                throw new LogicException('An idempotency reference cannot identify a different credit.');
            }

            $lot = DB::table('wallet_lots')->where('operation_id', $existing->id)->firstOrFail();
            if ($lot->initial_purchased_kb !== $purchasedKb) {
                throw new LogicException('Token provenance is immutable.');
            }

            return $lot;
        }
        DB::table('wallet_accounts')->insertOrIgnore(['user_id' => $userId]);
        $operation = $this->operation($userId, $reference, $kind, $kb, $minor);
        $id = (string) Str::uuid();
        DB::table('wallet_lots')->insert(['id' => $id, 'user_id' => $userId, 'operation_id' => $operation,
            'kind' => $kind, 'initial_kb' => $kb, 'remaining_kb' => $kb, 'initial_minor' => $minor, 'remaining_minor' => $minor, 'initial_purchased_kb' => $purchasedKb, 'remaining_purchased_kb' => $purchasedKb, 'created_at' => now()]);
        $this->entry($operation, $id, $kb, $minor);
        DB::table('wallet_accounts')->where('user_id', $userId)->increment('balance', $kb);

        return DB::table('wallet_lots')->find($id);
    }

    public function hasAccess(int $userId, string $type, int $contentId): bool
    {
        return DB::table('content_purchases')->where('user_id', $userId)->where('content_type', $type)->where('content_id', $contentId)->where('status', 'Active')->exists();
    }

    /** Called only after the owning content service validates current approved access. */
    public function buy(int $userId, int $creatorId, string $type, int $contentId, int $revisionId, int $price, string $settlement): ?object
    {
        $this->lock();
        if ($price === 0 || $this->hasAccess($userId, $type, $contentId)) {
            return null;
        }
        if (! in_array($type, ['module', 'course', 'challenge'], true) || ! in_array($settlement, ['Cash', 'KodeBits'], true) || $price < 0) {
            throw new LogicException('Invalid internal access purchase.');
        }
        if ($userId === $creatorId) {
            throw ValidationException::withMessages(['purchase' => 'Use your creator preview for your own content. Self-purchases are unavailable.']);
        }
        if ($this->snapshot($userId)['available'] < $price) {
            throw ValidationException::withMessages(['purchase' => 'You need '.$price.' available KodeBits for this access.']);
        }
        $id = (string) Str::uuid();
        $remaining = $price;
        $value = 0;
        $purchased = 0;
        $allocations = [];
        $lots = DB::table('wallet_lots')->where('user_id', $userId)->where('remaining_kb', '>', 0)->where('reserved_kb', 0)
            ->orderBy('created_at')->orderBy('id')->lockForUpdate()->get();
        foreach ($lots as $lot) {
            $take = min($remaining, $lot->remaining_kb);
            $allocated = $take === $lot->remaining_kb ? $lot->remaining_minor : intdiv($lot->remaining_minor * $take, $lot->remaining_kb);
            $purchasedTake = $take === $lot->remaining_kb ? $lot->remaining_purchased_kb : intdiv($lot->remaining_purchased_kb * $take, $lot->remaining_kb);
            $allocations[] = compact('lot', 'take', 'allocated', 'purchasedTake');
            $value += $allocated;
            $purchased += $purchasedTake;
            $remaining -= $take;
            if ($remaining === 0) {
                break;
            }
        }
        if ($remaining !== 0) {
            throw new LogicException('Wallet and spendable lots must reconcile.');
        }
        $operation = $this->operation($userId, 'access:'.$id, 'Access', -$price, -$value);
        foreach ($allocations as $allocation) {
            ['lot' => $lot, 'take' => $take, 'allocated' => $allocated, 'purchasedTake' => $purchasedTake] = $allocation;
            DB::table('wallet_lots')->where('id', $lot->id)->update(['remaining_kb' => $lot->remaining_kb - $take, 'remaining_minor' => $lot->remaining_minor - $allocated, 'remaining_purchased_kb' => $lot->remaining_purchased_kb - $purchasedTake]);
            $this->entry($operation, $lot->id, -$take, -$allocated);
        }
        $creator = intdiv($value * config('economy.creator_basis_points'), 10000);
        $platform = $value - $creator;
        DB::table('wallet_accounts')->where('user_id', $userId)->decrement('balance', $price);
        DB::table('content_purchases')->insert(['id' => $id, 'operation_id' => $operation, 'user_id' => $userId, 'creator_id' => $creatorId,
            'content_type' => $type, 'content_id' => $contentId, 'revision_id' => $revisionId, 'kodebits' => $price,
            'purchased_kb' => $purchased, 'value_minor' => $value, 'creator_minor' => $creator, 'platform_minor' => $platform, 'created_at' => now()]);
        DB::table('publisher_earnings')->insert(['id' => (string) Str::uuid(), 'purchase_id' => $id, 'user_id' => $creatorId, 'kind' => $settlement,
            'amount_minor' => $creator, 'kb_milli' => $settlement === 'KodeBits' ? $price * 650 : 0,
            'available_at' => now()->addDays(config('economy.maturity_days')), 'created_at' => now()]);
        $this->cash('sale:'.$id, $platform);
        app(AuditRecorder::class)->record($userId, $userId, 'access.purchased', 'content_purchase', $id,
            ['type' => $type, 'content_id' => $contentId, 'revision_id' => $revisionId, 'kodebits' => $price, 'value_minor' => $value]);
        app(FinancialNotices::class)->queue($userId, 'access:'.$id, 'Learning access purchased', ['kodebits' => $price, 'content_type' => $type, 'content_id' => $contentId]);

        return DB::table('content_purchases')->find($id);
    }

    public function cash(string $reference, int $minor): void
    {
        $this->lock();
        $existing = DB::table('platform_cash_entries')->where('reference', $reference)->first();
        if ($existing !== null) {
            if ($existing->amount_minor !== $minor) {
                throw new LogicException('Cash references are immutable.');
            }

            return;
        }
        DB::table('platform_cash_entries')->insert(['id' => (string) Str::uuid(), 'reference' => $reference, 'amount_minor' => $minor, 'created_at' => now()]);
        DB::table('economy_locks')->where('id', 1)->increment('platform_minor', $minor);
    }

    public function maturedCash(): int
    {
        $cash = $this->lock();
        $held = (int) DB::table('content_purchases')->where('status', 'Active')->where('created_at', '>', now()->subDays(config('economy.maturity_days')))->sum('platform_minor');

        return max(0, $cash->platform_minor - $cash->reserved_minor - $held);
    }

    public function rewardAllowance(): array
    {
        $this->lock();
        $month = CarbonImmutable::now('Asia/Manila')->startOfMonth();
        $paidSpent = (int) DB::table('content_purchases')->where('status', 'Active')->where('created_at', '>=', $month->subMonth()->utc())
            ->where('created_at', '<', $month->utc())->sum('purchased_kb');
        DB::table('reward_months')->insertOrIgnore(['month' => $month->toDateString()]);
        $issued = (int) DB::table('reward_months')->where('month', $month->toDateString())->value('issued_kb');
        $cap = intdiv($paidSpent * config('economy.reward_cap_basis_points'), 10000);
        $available = min(max(0, $cap - $issued), intdiv($this->maturedCash(), config('economy.reward_backing_minor')));

        return ['month' => $month->toDateString(), 'cap' => $cap, 'issued' => $issued, 'available' => $available];
    }

    public function reward(int $userId, string $reference, int $kb): void
    {
        $this->lock();
        $existing = DB::table('wallet_operations')->where('reference', $reference)->first();
        if ($existing !== null) {
            if ($existing->user_id !== $userId || $existing->kind !== 'Reward' || $existing->kodebits !== $kb) {
                throw new LogicException('A reward reference must identify the same grant.');
            }

            return;
        }
        $allowance = $this->rewardAllowance();
        if ($kb <= 0 || $kb > $allowance['available']) {
            throw new LogicException('Rewards must fit both the cash backing and monthly cap.');
        }
        $minor = $kb * config('economy.reward_backing_minor');
        $this->cash('backing:'.$reference, -$minor);
        $this->credit($userId, $reference, 'Reward', $kb, $minor);
        DB::table('reward_months')->where('month', $allowance['month'])->increment('issued_kb', $kb);
    }

    public function operation(int $userId, string $reference, string $kind, int $kb, int $minor): string
    {
        $this->lock();
        $id = (string) Str::uuid();
        DB::table('wallet_operations')->insert(['id' => $id, 'user_id' => $userId, 'kind' => $kind, 'reference' => $reference,
            'kodebits' => $kb, 'value_minor' => $minor, 'policy_version' => config('economy.policy_version'), 'created_at' => now()]);

        return $id;
    }

    public function entry(string $operation, string $lot, int $kb, int $minor): void
    {
        $this->lock();
        DB::table('wallet_entries')->insert(['id' => (string) Str::uuid(), 'operation_id' => $operation, 'lot_id' => $lot,
            'kodebits' => $kb, 'value_minor' => $minor, 'created_at' => now()]);
    }
}
