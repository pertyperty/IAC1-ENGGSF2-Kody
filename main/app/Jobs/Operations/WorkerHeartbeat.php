<?php

namespace App\Jobs\Operations;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class WorkerHeartbeat implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 10;

    // A declared default also supports retained pre-consolidation serialized jobs.
    public string $component = 'worker';

    public ?string $probeId = null;

    public function __construct(string $component = 'worker', ?string $probeId = null)
    {
        $this->component = $component;
        $this->probeId = $probeId;
    }

    public function handle(): void
    {
        DB::table('operations_heartbeats')->where('component', $this->component)->where('probe_id', $this->probeId)
            ->update(['last_success_at' => now(), 'probe_id' => null]);
    }
}
