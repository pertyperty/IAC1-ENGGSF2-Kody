<?php

namespace App\Console\Commands;

use App\Services\Gamification\WeeklyEvents;
use Illuminate\Console\Command;

class SynchronizeWeeklyEvents extends Command
{
    protected $signature = 'kody:weekly-events-sync';

    protected $description = 'Activate the Manila weekly event and close previous weeks';

    public function handle(WeeklyEvents $events): int
    {
        $events->synchronize();

        return self::SUCCESS;
    }
}
