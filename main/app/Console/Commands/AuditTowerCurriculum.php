<?php

namespace App\Console\Commands;

use App\Models\TowerRevision;
use App\Services\Games\TowerCurriculumAudit;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Console\Command;

class AuditTowerCurriculum extends Command
{
    protected $signature = 'kody:tower-curriculum-audit {--seed-only : Omit persisted revisions}';

    protected $description = 'Print a private, read-only teaching matrix with reference validation; includes practice answers';

    public function handle(TowerCurriculumAudit $audit): int
    {
        $report = ['editorial_version' => '2026-10-18', 'private_editorial_material' => true, 'seed' => [], 'persisted' => [], 'truncated' => false];
        foreach ((new TowerLevelSeeder)->curriculum() as $index => $entry) {
            $report['seed'][] = $audit->entry($entry, $index + 1) + ['provenance' => 'Original Kody curriculum; no third-party course material copied.', 'publication' => 'Seed proposal; reseeding never overwrites existing levels.'];
        }
        if (! $this->option('seed-only')) {
            $revisions = TowerRevision::with('level')->orderBy('id')->limit(5001)->get();
            $report['truncated'] = $revisions->count() > 5000;
            foreach ($revisions->take(5000) as $revision) {
                $report['persisted'][] = $audit->entry($revision->toArray(), $revision->level->position)
                    + ['revision_id' => $revision->id, 'number' => $revision->number, 'current' => $revision->level->current_revision_id === $revision->id,
                        'provenance' => $revision->source_notes, 'publication' => 'Retained immutable revision; inspect editorial accuracy separately from seed proposals.'];
            }
        }
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return collect($report['seed'])->every(fn ($entry) => collect($entry['stages'])->every(fn ($stage) => $stage['reference_verified'])) ? self::SUCCESS : self::FAILURE;
    }
}
