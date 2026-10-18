<?php

namespace App\Services\Games;

use App\Models\TowerLevel;
use App\Models\TowerRevision;
use App\Services\Administration\AuditRecorder;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Support\Facades\DB;

class TowerEditorialUpgrade
{
    public function suggestion(TowerLevel $level): ?array
    {
        $seed = new TowerLevelSeeder;
        $original = $seed->legacyCurriculum()[$level->position - 1] ?? null;

        return $original && $this->isOriginal($level->currentRevision, $original)
            ? $seed->curriculum()[$level->position - 1] : null;
    }

    private function isOriginal(?TowerRevision $revision, array $original): bool
    {
        if (! $revision || $revision->created_by !== null || $revision->number !== 1
            || $revision->source_notes !== 'Original Kody curriculum and scenarios, 2026-10-10–11. Editorial learning material; not copied course or repository content.') {
            return false;
        }
        // PostgreSQL JSONB reorders object keys; preserve list order and scalar types.
        $canonical = function (mixed $value) use (&$canonical): mixed {
            if (! is_array($value)) {
                return $value;
            }
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map($canonical, $value);
        };

        return collect($original)->every(fn ($value, $key) => $canonical($value) === $canonical($revision->$key));
    }

    public function run(bool $apply = false): array
    {
        return DB::transaction(function () use ($apply): array {
            DB::select("SELECT pg_advisory_xact_lock(hashtext('kody_tower_seed'))");
            $seeder = new TowerLevelSeeder;
            $legacy = $seeder->legacyCurriculum();
            $updated = $seeder->curriculum();
            $result = ['eligible' => [], 'skipped' => [], 'published' => []];
            foreach (TowerLevel::with('currentRevision')->whereBetween('position', [1, 50])->orderBy('position')->lockForUpdate()->get() as $level) {
                $revision = $level->currentRevision;
                $original = $legacy[$level->position - 1];
                if (! $this->isOriginal($revision, $original)) {
                    $result['skipped'][] = $level->position;

                    continue;
                }
                $result['eligible'][] = $level->position;
                if (! $apply) {
                    continue;
                }
                foreach (app(TowerCurriculumAudit::class)->entry($updated[$level->position - 1], $level->position)['stages'] as $stage) {
                    if (! $stage['reference_verified']) {
                        throw new \RuntimeException('An editorial reference failed validation; no revisions were published.');
                    }
                }
                $new = TowerRevision::create($updated[$level->position - 1] + ['level_id' => $level->id, 'number' => 2,
                    'source_notes' => $revision->source_notes.' Original Kody editorial update 2026-10-18: scenario-aware instructions, hints and learning objectives.', 'created_by' => null]);
                $level->update(['current_revision_id' => $new->id, 'record_version' => $level->record_version + 1]);
                app(AuditRecorder::class)->record(null, null, 'tower.editorial-updated', 'tower_level', (string) $level->id,
                    ['previous_revision_id' => $revision->id, 'revision_id' => $new->id, 'editorial_version' => '2026-10-18']);
                $result['published'][] = $level->position;
            }

            return $result;
        });
    }
}
