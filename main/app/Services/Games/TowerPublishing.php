<?php

namespace App\Services\Games;

use App\Models\TowerLevel;
use App\Models\TowerRevision;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TowerPublishing
{
    public function save(User $actor, string $sessionId, TowerLevel $level, array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $level, $data): void {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            Gate::forUser($user)->authorize('manage', TowerLevel::class);
            $current = TowerLevel::whereKey($level->id)->lockForUpdate()->firstOrFail();
            if ($current->record_version !== (int) $data['record_version']) {
                throw ValidationException::withMessages(['record_version' => 'This level changed. Reload before publishing your revision.']);
            }
            try {
                $decoded = json_decode($data['stages_json'], true, 16, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw ValidationException::withMessages(['stages_json' => 'Use valid stage JSON with no more than 16 nested layers.']);
            }
            if (! is_array($decoded)) {
                throw ValidationException::withMessages(['stages_json' => 'Supply a list of typed stages.']);
            }
            $stages = app(TowerAuthoring::class)->stages($decoded);
            if ($current->position % 10 === 0 && count($stages) < 2) {
                throw ValidationException::withMessages(['stages_json' => 'Boss levels need at least two stages.']);
            }
            $revision = TowerRevision::create(['level_id' => $current->id, 'number' => $current->currentRevision->number + 1,
                'title' => $data['title'], 'concept' => $data['concept'], 'description' => $data['description'],
                'source_notes' => $data['source_notes'], 'stages' => $stages, 'created_by' => $user->id]);
            $current->update(['current_revision_id' => $revision->id, 'record_version' => $current->record_version + 1]);
            app(AuditRecorder::class)->record($user->id, null, 'tower.level_updated', 'tower_level', (string) $current->id, ['revision_id' => $revision->id, 'version' => $current->record_version]);
        });
    }
}
