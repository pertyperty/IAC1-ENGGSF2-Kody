<?php

namespace App\Services\Operations;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class Heartbeats
{
    public function workers(): array
    {
        return array_unique(['worker' => config('queue.connections.database.queue', 'default'),
            'worker:payments' => 'payments', 'worker:notifications' => 'notifications', 'worker:code-execution' => 'code-execution']);
    }

    public function snapshot(): array
    {
        $rows = DB::table('operations_heartbeats')->get()->keyBy('component');
        $result = [];
        $labels = ['scheduler' => 'Scheduler'];
        foreach ($this->workers() as $key => $queue) {
            $labels[$key] = 'Queue probe: '.$queue;
        }
        foreach ($labels as $key => $label) {
            $last = isset($rows[$key]) && $rows[$key]->last_success_at !== null ? CarbonImmutable::parse($rows[$key]->last_success_at) : null;
            $result[] = ['label' => $label, 'state' => $last === null ? 'Unknown' : ($last->betweenIncluded(now()->subMinutes(3), now()) ? 'Recent' : 'Stale'),
                'queue' => $this->workers()[$key] ?? null,
                'pending_since' => isset($rows[$key]) && $rows[$key]->probe_id !== null ? $rows[$key]->last_dispatched_at : null,
                'last' => $last?->setTimezone('Asia/Manila')->format('M j, g:i A')];
        }

        return $result;
    }
}
