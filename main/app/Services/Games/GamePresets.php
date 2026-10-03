<?php

namespace App\Services\Games;

use App\Models\GamePreset;
use App\Models\GamePresetRevision;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GamePresets
{
    public function save(User $actor, string $sessionId, array $data, ?GamePreset $preset = null): GamePreset
    {
        $data = app(PresetConfiguration::class)->validated($data);
        try {
            return DB::transaction(function () use ($actor, $sessionId, $data, $preset): GamePreset {
                $user = $this->account($actor, $sessionId);
                if ($preset === null) {
                    Gate::forUser($user)->authorize('create', GamePreset::class);
                    $current = GamePreset::create(['name' => $data['name'], 'created_by' => $user->id]);
                    $number = 1;
                } else {
                    $current = GamePreset::whereKey($preset->id)->lockForUpdate()->firstOrFail();
                    Gate::forUser($user)->authorize('update', $current);
                    $this->version($current, (int) $data['record_version']);
                    $number = $current->currentRevision->number + 1;
                }
                $revision = GamePresetRevision::create(['preset_id' => $current->id, 'number' => $number, 'created_by' => $user->id,
                    'instance' => app(PresetConfiguration::class)->instance($data)]);
                $current->update(['name' => $data['name'], 'current_revision_id' => $revision->id,
                    'record_version' => $preset === null ? 1 : $current->record_version + 1]);
                app(AuditRecorder::class)->record($user->id, null, $preset === null ? 'game_preset.created' : 'game_preset.updated',
                    'game_preset', (string) $current->id, ['revision_id' => $revision->id, 'version' => $current->record_version, 'reward_mode' => 'Deferred']);

                return $current->unsetRelation('currentRevision');
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505' && str_contains($exception->getMessage(), 'game_presets_name_unique')) {
                throw ValidationException::withMessages(['name' => 'A preset already uses this name. Choose another name.']);
            }
            throw $exception;
        }
    }

    public function inactivate(User $actor, string $sessionId, GamePreset $preset, int $version, bool $confirmed): void
    {
        DB::transaction(function () use ($actor, $sessionId, $preset, $version, $confirmed): void {
            $user = $this->account($actor, $sessionId);
            $current = GamePreset::whereKey($preset->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('viewAny', GamePreset::class);
            $this->version($current, $version);
            Gate::forUser($user)->authorize('update', $current);
            if (! $confirmed) {
                throw ValidationException::withMessages(['confirmed' => 'Confirm preset inactivation.']);
            }
            $current->update(['status' => 'Inactive', 'record_version' => $current->record_version + 1]);
            app(AuditRecorder::class)->record($user->id, null, 'game_preset.inactivated', 'game_preset', (string) $current->id,
                ['revision_id' => $current->current_revision_id, 'version' => $current->record_version]);
        });
    }

    public function snapshot(int $revisionId): array
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Preset snapshots must share the module write transaction.');
        }
        // Share the preset lock with updates/inactivation before copying a new instance.
        $revision = GamePresetRevision::findOrFail($revisionId);
        $preset = GamePreset::whereKey($revision->preset_id)->lockForUpdate()->firstOrFail();
        if ($preset->status !== 'Active' || $preset->current_revision_id !== $revision->id) {
            throw ValidationException::withMessages(['managed_preset' => 'This preset changed or is inactive. Reload and choose an available preset.']);
        }

        return $revision->instance;
    }

    private function account(User $actor, string $sessionId): User
    {
        $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
        app(CurrentAccountSession::class)->assert($user, $sessionId);

        return $user;
    }

    private function version(GamePreset $preset, int $version): void
    {
        if ($preset->record_version !== $version) {
            throw ValidationException::withMessages(['record_version' => 'This preset changed. Reload before confirming.']);
        }
    }
}
