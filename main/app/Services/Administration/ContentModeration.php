<?php

namespace App\Services\Administration;

use App\Enums\AccountStatus;
use App\Models\CodingChallenge;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Notifications\ContentModerated;
use App\Services\Account\CurrentAccountSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContentModeration
{
    public function model(string $kind): string
    {
        return match ($kind) {
            'module' => LearningModule::class, 'course' => LearningCourse::class, 'challenge' => CodingChallenge::class,
            default => abort(404),
        };
    }

    public function change(User $actor, string $sessionId, string $kind, int $id, array $data): void
    {
        $class = $this->model($kind);
        $candidate = $class::findOrFail($id);
        DB::transaction(function () use ($actor, $sessionId, $kind, $class, $candidate, $data): void {
            $users = User::whereIn('id', [$actor->id, $candidate->created_by])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $staff = $users->get($actor->id);
            app(CurrentAccountSession::class)->assert($staff, $sessionId);
            $content = $class::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($staff)->authorize('moderate', $content);
            $withdraw = $data['action'] === 'Withdrawn';
            if (! in_array($data['action'], ['Withdrawn', 'Restored'], true) || $withdraw === $content->isWithdrawn()
                || $content->record_version !== (int) $data['record_version']) {
                throw ValidationException::withMessages(['record_version' => 'This content changed or that action was already applied. Reload before confirming.']);
            }
            foreach ($withdraw ? ['confirmed', 'flagged'] : ['confirmed'] as $confirmation) {
                if (! in_array($data[$confirmation] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)) {
                    throw ValidationException::withMessages([$confirmation => 'Confirm your reviewed moderation decision and flagged content.']);
                }
            }
            $content->forceFill(['staff_withdrawn_at' => $withdraw ? now() : null, 'record_version' => $content->record_version + 1])->save();
            $actionId = (string) Str::uuid();
            DB::table('content_moderation_actions')->insert(['id' => $actionId, 'actor_id' => $staff->id, 'kind' => $kind,
                $kind.'_id' => $content->id, 'action' => $data['action'], 'lifecycle' => $content->status,
                'record_version' => $content->record_version, 'created_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record($staff->id, $content->created_by, 'content.'.strtolower($data['action']), 'content_moderation_action', $actionId,
                ['kind' => $kind, 'content_id' => $content->id, 'version' => $content->record_version, 'flagged' => $withdraw]);
            $owner = $users->get($content->created_by);
            if ($owner !== null && $owner->account_status !== AccountStatus::Deleted) {
                // Durable database-channel delivery shares this transaction; no remote mail blocks moderation.
                $owner->notify(new ContentModerated($actionId, $kind, $content->id, $data['action']));
            }
        });
    }
}
