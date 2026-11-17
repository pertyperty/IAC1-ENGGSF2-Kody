<?php

namespace App\Console\Commands;

use App\Jobs\Operations\WorkerHeartbeat;
use App\Services\Operations\Heartbeats;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class OperationsHeartbeat extends Command
{
    protected $signature = 'kody:operations-heartbeat';

    protected $description = 'Record scheduler activity and dispatch one bounded probe per application queue';

    public function handle(): int
    {
        if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
            $this->error('Heartbeat requires the application database queue.');

            return self::FAILURE;
        }
        DB::transaction(function (): void {
            DB::table('operations_heartbeats')->updateOrInsert(['component' => 'scheduler'], ['last_success_at' => now()]);
            foreach (app(Heartbeats::class)->workers() as $component => $queue) {
                DB::table('operations_heartbeats')->insertOrIgnore(['component' => $component]);
                $worker = DB::table('operations_heartbeats')->where('component', $component)->lockForUpdate()->first();
                if ($worker->probe_id !== null) {
                    continue; // A stopped consumer cannot accumulate probes.
                }
                $probeId = (string) Str::uuid();
                Queue::connection('database')->pushOn($queue, (new WorkerHeartbeat($component, $probeId))->beforeCommit());
                DB::table('operations_heartbeats')->where('component', $component)->update(['last_dispatched_at' => now(), 'probe_id' => $probeId]);
            }
        });
        $this->info('Scheduler heartbeat recorded. Each queue records success only when its probe runs.');

        return self::SUCCESS;
    }
}
