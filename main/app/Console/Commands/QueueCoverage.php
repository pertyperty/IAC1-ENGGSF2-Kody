<?php

namespace App\Console\Commands;

use App\Services\Operations\Heartbeats;
use Illuminate\Console\Command;

class QueueCoverage extends Command
{
    protected $signature = 'kody:queue-check';

    protected $description = 'Check recent processing probes for each application queue (no provider calls)';

    public function handle(Heartbeats $heartbeats): int
    {
        $workers = array_values(array_filter($heartbeats->snapshot(), fn ($row) => $row['queue'] !== null));
        $this->table(['Queue', 'Probe', 'Last success (Manila)'], array_map(fn ($row) => [$row['queue'], $row['state'], $row['last'] ?? 'Never'], $workers));
        $this->info('A recent probe proves queue consumption, not successful provider delivery or evaluation.');

        return count(array_filter($workers, fn ($row) => $row['state'] !== 'Recent')) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
