<?php

namespace App\Console\Commands;

use App\Services\Games\TowerEditorialUpgrade;
use Illuminate\Console\Command;

class UpgradeTowerTeaching extends Command
{
    protected $signature = 'kody:tower-teaching-upgrade {--apply : Publish new revisions for exactly recognized untouched original seed levels}';

    protected $description = 'Review or apply the guarded 2026-10-18 Tower teaching update without replacing custom edits or clears';

    public function handle(TowerEditorialUpgrade $upgrade): int
    {
        $this->line(json_encode($upgrade->run((bool) $this->option('apply')), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
