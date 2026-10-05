<?php

namespace App\Console\Commands;

use App\Services\Transactions\WalletLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectLedger extends Command
{
    protected $signature = 'kody:ledger-check';

    protected $description = 'Read-only reconciliation of wallet lots, immutable entries and platform cash';

    public function handle(): int
    {
        $counts = DB::transaction(function (): array {
            app(WalletLedger::class)->lock();

            return [
                'Wallet/lot balance mismatches' => DB::selectOne(<<<'SQL'
SELECT count(*) AS total FROM wallet_accounts a LEFT JOIN
(SELECT user_id,sum(remaining_kb) AS kb,sum(reserved_kb) AS reserved FROM wallet_lots GROUP BY user_id) l ON l.user_id=a.user_id
WHERE a.balance <> coalesce(l.kb,0) OR a.reserved <> coalesce(l.reserved,0)
SQL)->total,
                'Lot/entry conservation mismatches' => DB::selectOne(<<<'SQL'
SELECT count(*) AS total FROM wallet_lots l LEFT JOIN
(SELECT lot_id,sum(kodebits) AS kb,sum(value_minor) AS value FROM wallet_entries GROUP BY lot_id) e ON e.lot_id=l.id
WHERE l.remaining_kb <> coalesce(e.kb,0) OR l.remaining_minor <> coalesce(e.value,0)
SQL)->total,
                'Platform cash mismatch' => (int) (DB::table('economy_locks')->value('platform_minor') !== (int) DB::table('platform_cash_entries')->sum('amount_minor')),
            ];
        });
        $this->table(['Integrity check', 'Mismatch count'], collect($counts)->map(fn ($count, $name) => [$name, $count])->all());
        $this->line('Read-only accounting check; no provider calls or balance repairs. Investigate mismatches against retained references.');

        return array_sum($counts) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
