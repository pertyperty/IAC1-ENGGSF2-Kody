<?php

namespace App\Services\Operations;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class Heartbeats
{
    public function snapshot(): array
    {
        $rows = DB::table('operations_heartbeats')->get()->keyBy('component');
        $result = [];
        foreach (['scheduler' => 'Scheduler', 'worker' => 'Database worker'] as $key => $label) {
            $last = isset($rows[$key]) && $rows[$key]->last_success_at !== null ? CarbonImmutable::parse($rows[$key]->last_success_at) : null;
            $result[] = ['label' => $label, 'state' => $last === null ? 'Unknown' : ($last->betweenIncluded(now()->subMinutes(3), now()) ? 'Recent' : 'Stale'),
                'last' => $last?->setTimezone('Asia/Manila')->format('M j, g:i A')];
        }

        return $result;
    }
}
