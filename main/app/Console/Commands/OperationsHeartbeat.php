<?php

namespace App\Console\Commands;

use App\Jobs\Operations\WorkerHeartbeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class OperationsHeartbeat extends Command
{
    protected $signature = 'kody:operations-heartbeat';

    protected $description = 'Record scheduler activity and dispatch at most one outstanding database-worker probe';

    public function handle(): int
    {
        if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
            $this->error('Heartbeat requires the application database queue.');

            return self::FAILURE;
        }
        DB::transaction(function (): void {
            DB::table('operations_heartbeats')->updateOrInsert(['component' => 'scheduler'], ['last_success_at' => now()]);
            DB::table('operations_heartbeats')->insertOrIgnore(['component' => 'worker']);
            $worker = DB::table('operations_heartbeats')->where('component', 'worker')->lockForUpdate()->first();
            if ($worker->last_dispatched_at !== null && ($worker->last_success_at === null || $worker->last_success_at < $worker->last_dispatched_at)) {
                return; // A stopped worker must not accumulate a queue of probes.
            }
            Queue::connection('database')->push((new WorkerHeartbeat)->beforeCommit());
            DB::table('operations_heartbeats')->where('component', 'worker')->update(['last_dispatched_at' => now()]);
        });
        $this->info('Scheduler heartbeat recorded. Worker success is recorded only when its probe runs.');

        return self::SUCCESS;
    }
}
