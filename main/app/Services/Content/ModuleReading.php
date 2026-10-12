<?php

namespace App\Services\Content;

use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use App\Services\Gamification\Achievements;
use App\Services\Transactions\ContentAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;

class ModuleReading
{
    public function standalone(User $actor, string $sessionId, int $moduleId, int $revisionId): void
    {
        DB::transaction(function () use ($actor, $sessionId, $moduleId, $revisionId): void {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
            $module = LearningModule::whereKey($moduleId)->lockForUpdate()->firstOrFail();
            abort_unless($module->status === 'Published' && ! $module->isWithdrawn(), 404);
            abort_unless($module->published_revision_id === $revisionId, 409, 'This article changed. Open its current edition first.');
            app(ContentAccess::class)->assertAccessible($user, 'module', $module, $module->publishedRevision);
            abort_unless(DB::table('content_accesses')->where('user_id', $user->id)->where('kind', 'module')->where('module_id', $moduleId)->exists(), 409, 'Open the article before marking it as read.');
            $this->recordAuthorized($user, $sessionId, $moduleId, $revisionId);
        });
    }

    /** Course/standalone callers retain their locked access and authorized opening. */
    public function recordAuthorized(User $user, string $sessionId, int $moduleId, int $revisionId): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Article completion requires its authorized content transaction.');
        }
        app(CurrentAccountSession::class)->assert($user, $sessionId);
        Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
        $module = LearningModule::whereKey($moduleId)->lockForUpdate()->firstOrFail();
        $revision = ModuleRevision::where('module_id', $moduleId)->where('review_status', 'Approved')->findOrFail($revisionId);
        abort_unless($module->status === 'Published' && ! $module->isWithdrawn(), 404);
        abort_unless($revision->type === 'Article' && $revision->assessment === null, 409, 'Use this lesson’s assessment to complete it.');
        if (DB::table('module_read_completions')->insertOrIgnore(['user_id' => $user->id, 'module_id' => $moduleId, 'revision_id' => $revisionId, 'completed_at' => now()])) {
            app(Achievements::class)->award($user->id, 'module:'.$moduleId, config('economy.xp.module'));
            app(AuditRecorder::class)->record($user->id, $user->id, 'module.article_read', 'learning_module', (string) $moduleId, ['revision_id' => $revisionId]);
        }
        // No activity-day/assessment-completion rows: reading never earns streak or eligibility credit.
    }
}
