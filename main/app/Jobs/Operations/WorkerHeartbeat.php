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

    public function handle(): void
    {
        DB::table('operations_heartbeats')->where('component', 'worker')->update(['last_success_at' => now()]);
    }
}
