<?php

namespace App\Services\Content;

use App\Models\LearningModule;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use App\Services\Games\GameAssessment;
use App\Services\Games\GamePresets;
use App\Services\Games\QuizAuthoring;
use App\Services\Notifications\InAppNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ModulePublishing
{
    public function save(User $actor, string $sessionId, array $data, ?LearningModule $module = null): LearningModule
    {
        return DB::transaction(function () use ($actor, $sessionId, $data, $module): LearningModule {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            if ($module === null) {
                Gate::forUser($user)->authorize('create', LearningModule::class);
                $current = LearningModule::create(['created_by' => $user->id]);
                $number = 1;
            } else {
                $current = LearningModule::whereKey($module->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($user)->authorize('update', $current);
                $this->version($current, (int) $data['record_version']);
                $latest = $current->latestRevision;
                if ($latest->review_status === 'Pending') {
                    throw ValidationException::withMessages(['module' => 'This revision is awaiting review. Save changes after the review finishes.']);
                }
                $number = $latest->number + 1;
                $current->increment('record_version');
            }
            ModuleRevision::create(['module_id' => $current->id, 'number' => $number,
                'title' => $data['title'], 'description' => $data['description'], 'content' => $data['content'],
                'type' => $data['type'], 'video_url' => $data['type'] === 'Video' ? $data['video_url'] : null,
                'assessment' => $this->assessment($data),
                'game_preset_revision_id' => $data['assessment_kind'] === 'preset' ? (int) $data['managed_preset'] : null]);
            $this->audit($user, $current, 'module.saved', ['revision' => $number]);

            return $current;
        });
    }

    public function submit(User $actor, string $sessionId, LearningModule $module, int $version): void
    {
        DB::transaction(function () use ($actor, $sessionId, $module, $version): void {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $current = LearningModule::whereKey($module->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('update', $current);
            $this->version($current, $version);
            $revision = $current->latestRevision;
            if ($revision->review_status !== 'Draft') {
                throw ValidationException::withMessages(['module' => 'Save a new draft before submitting for review.']);
            }
            $revision->update(['review_status' => 'Pending']);
            $current->increment('record_version');
            $this->audit($user, $current, 'module.submitted', ['revision' => $revision->number]);
        });
    }

    public function review(User $actor, string $sessionId, LearningModule $module, int $version, string $decision, ?string $notes): void
    {
        DB::transaction(function () use ($actor, $sessionId, $module, $version, $decision, $notes): void {
            $users = User::whereIn('id', [$actor->id, $module->created_by])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $user = $users->get($actor->id);
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $current = LearningModule::whereKey($module->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('review', $current);
            $this->version($current, $version);
            $revision = $current->latestRevision;
            if ($revision->review_status !== 'Pending' || ! in_array($current->status, ['Draft', 'Published'], true)) {
                throw ValidationException::withMessages(['module' => 'Only the current pending revision can be reviewed.']);
            }
            if (! in_array($decision, ['Approved', 'Rejected'], true) || ($decision === 'Rejected' && trim($notes ?? '') === '')) {
                throw ValidationException::withMessages(['decision' => 'Choose a decision and give a reason for rejection.']);
            }
            if ($decision === 'Approved' && ! Gate::forUser($users->get($current->created_by))->allows('create', LearningModule::class)) {
                throw ValidationException::withMessages(['module' => 'The author must retain verified Active Instructor access before publication.']);
            }
            $revision->update(['review_status' => $decision, 'reviewed_by' => $user->id, 'reviewed_at' => now(), 'review_notes' => $notes]);
            $changes = ['record_version' => $current->record_version + 1];
            if ($decision === 'Approved') {
                $changes += ['status' => 'Published', 'published_revision_id' => $revision->id];
            }
            $current->update($changes);
            $this->audit($user, $current, 'module.reviewed', ['revision' => $revision->number, 'decision' => $decision]);
            app(InAppNotifications::class)->moduleReviewed($users->get($current->created_by), $revision);
        });
    }

    public function archive(User $actor, string $sessionId, LearningModule $module, int $version): void
    {
        DB::transaction(function () use ($actor, $sessionId, $module, $version): void {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $current = LearningModule::whereKey($module->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('archive', $current);
            $this->version($current, $version);
            $current->update(['status' => 'Archived', 'record_version' => $current->record_version + 1]);
            $this->audit($user, $current, 'module.archived', ['published_revision_id' => $current->published_revision_id]);
        });
    }

    private function version(LearningModule $module, int $version): void
    {
        if ($module->record_version !== $version) {
            throw ValidationException::withMessages(['record_version' => 'This module changed. Reload before saving or reviewing.']);
        }
    }

    private function assessment(array $data): ?array
    {
        return match ($data['assessment_kind']) {
            'preset' => array_replace(app(GamePresets::class)->snapshot((int) $data['managed_preset']), ['title' => $data['preset_title']]),
            'game' => array_replace(app(GameAssessment::class)->instance($data), ['preset' => $data['game_preset'],
                'title' => $data['game_title'], 'instructions' => $data['game_instructions'], 'hint' => $data['game_hint'], 'learningIdea' => $data['game_learning_idea']]),
            'quiz' => isset($data['quiz_questions']) ? app(QuizAuthoring::class)->instance($data['quiz_title'], $data['quiz_questions']) : ['template' => 'choice-quiz', 'version' => 1, 'title' => $data['quiz_title'], 'question' => $data['quiz_question'],
                'options' => [['id' => 'a', 'label' => $data['quiz_a']], ['id' => 'b', 'label' => $data['quiz_b']]],
                'answer' => $data['quiz_answer'], 'explanation' => $data['quiz_explanation']],
            default => null,
        };
    }

    private function audit(User $actor, LearningModule $module, string $event, array $context): void
    {
        app(AuditRecorder::class)->record($actor->id, $module->created_by, $event, 'learning_module', (string) $module->id,
            $context + ['version' => $module->record_version]);
    }
}
