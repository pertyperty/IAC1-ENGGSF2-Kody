<?php

namespace App\Services\Gamification;

use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Games\GameAssessment;
use App\Services\Games\QuizAuthoring;
use App\Services\Transactions\ContentAccess;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LearningProgression
{
    public function snapshot(int $userId): array
    {
        $progress = DB::table('learning_progress')->where('user_id', $userId)->first();
        $completed = DB::table('learning_level_completions')->where('user_id', $userId)->pluck('level')->all();
        $today = CarbonImmutable::now('Asia/Manila')->startOfDay();
        $current = $progress !== null && $progress->last_activity_date !== null
            && CarbonImmutable::parse($progress->last_activity_date, 'Asia/Manila')->greaterThanOrEqualTo($today->subDay()) ? $progress->current_streak : 0;
        $levels = [];
        $previousComplete = true;
        foreach (config('learning.modules') as $slug => $module) {
            $isComplete = in_array($slug, $completed, true);
            $levels[$slug] = $module + ['completed' => $isComplete, 'unlocked' => $previousComplete];
            $previousComplete = $previousComplete && $isComplete;
        }

        $next = collect($levels)->filter(fn (array $level) => $level['unlocked'] && ! $level['completed'])->keys()->first();

        return ['current_streak' => $current, 'longest_streak' => $progress?->longest_streak ?? 0,
            'next_level' => $next, 'active_today' => $progress?->last_activity_date === $today->toDateString(),
            'last_activity_date' => $progress?->last_activity_date, 'levels' => $levels,
            'completed_count' => count(array_intersect(array_keys($levels), $completed))];
    }

    public function record(User $actor, string $sessionId, string $level, string $kind, array $input): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $level, $kind, $input): array {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $state = $this->snapshot($user->id);
            if (! ($state['levels'][$level]['unlocked'] ?? false)) {
                throw new AuthorizationException('Clear the previous level first.');
            }
            $instance = $kind === 'game' ? config('learning.instances')[$level] : config('learning.quizzes')[$level];
            $valid = $kind === 'game'
                ? app(GameAssessment::class)->succeeds($instance, $input)
                : app(QuizAuthoring::class)->succeeds($instance, $input);
            if (! $valid) {
                throw ValidationException::withMessages(['completion' => 'That attempt did not complete the objective. Try again.']);
            }
            if ($kind === 'game' && ! $state['levels'][$level]['completed']) {
                DB::table('learning_level_completions')->insert([
                    'id' => (string) Str::uuid(), 'user_id' => $user->id, 'level' => $level,
                    'template_version' => $instance['version'], 'completed_at' => now(),
                ]);
            }
            $this->saveActivity($user, $state, $level, $kind, $instance, $input);
            app(Achievements::class)->award($user->id, 'starter:'.$level.':'.$kind, config('economy.xp.starter'));

            return ['message' => $kind === 'game' ? 'Level cleared and saved. Your streak is up to date.' : 'Quiz win saved. Your streak is up to date.', 'progress' => $this->snapshot($user->id)];
        });
    }

    public function recordModule(User $actor, string $sessionId, int $moduleId, int $revisionId, string $kind, array $input): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $moduleId, $revisionId, $kind, $input): array {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
            $module = LearningModule::whereKey($moduleId)->lockForUpdate()->firstOrFail();
            abort_unless($module->status === 'Published' && ! $module->isWithdrawn() && $module->published_revision_id === $revisionId, 409, 'This adventure changed. Reload before trying again.');

            app(ContentAccess::class)->assertAccessible($user, 'module', $module, $module->publishedRevision);

            return $this->recordApprovedModule($user, $sessionId, $moduleId, $revisionId, $kind, $input);
        });
    }

    /** Content must authorize access to a pinned revision before invoking this writer. */
    public function recordApprovedModule(User $actor, string $sessionId, int $moduleId, int $revisionId, string $kind, array $input): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $moduleId, $revisionId, $kind, $input): array {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
            $module = LearningModule::whereKey($moduleId)->lockForUpdate()->firstOrFail();
            abort_unless($module->status === 'Published' && ! $module->isWithdrawn(), 404);
            $revision = ModuleRevision::where('module_id', $moduleId)->findOrFail($revisionId);
            $instance = $revision->assessment;
            abort_unless(in_array($kind, ['game', 'quiz'], true) && $revision->review_status === 'Approved' && $instance !== null
                && ($kind === 'game' ? app(GameAssessment::class)->supports($instance) : $instance['template'] === 'choice-quiz'), 404);
            $valid = $kind === 'game'
                ? app(GameAssessment::class)->succeeds($instance, $input)
                : app(QuizAuthoring::class)->succeeds($instance, $input);
            if (! $valid) {
                throw ValidationException::withMessages(['completion' => 'That attempt did not complete the objective. Try again.']);
            }
            $this->saveActivity($user, $this->snapshot($user->id), 'module-'.$moduleId, $kind, $instance, $input + ['revision_id' => $revisionId]);
            app(Achievements::class)->award($user->id, 'module:'.$moduleId, config('economy.xp.module'));

            return ['message' => 'Adventure win saved. Your streak is up to date.', 'progress' => $this->snapshot($user->id)];
        });
    }

    /** Tower owns objective replay and the locked level revision; this writer grants streak credit only. */
    public function recordTowerActivity(User $user, string $sessionId, int $levelId, array $instance, array $input): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Tower activity must share the clearance transaction.');
        }
        app(CurrentAccountSession::class)->assert($user, $sessionId);
        Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
        $this->saveActivity($user, $this->snapshot($user->id), 'tower-'.$levelId,
            $instance['template'] === 'choice-quiz' ? 'quiz' : 'game', $instance, $input);
    }

    private function saveActivity(User $user, array $state, string $level, string $kind, array $instance, array $input): void
    {
        $today = CarbonImmutable::now('Asia/Manila')->startOfDay();
        // The locked account serializes all its level and daily activity writes.
        $alreadyRecorded = DB::table('learning_activity_days')->where('user_id', $user->id)->where('level', $level)
            ->where('kind', $kind)->where('business_date', $today->toDateString())->exists();
        if (! $alreadyRecorded) {
            DB::table('learning_activity_days')->insert([
                'id' => (string) Str::uuid(), 'user_id' => $user->id, 'level' => $level, 'kind' => $kind,
                'template_version' => $instance['version'], 'business_date' => $today->toDateString(),
                'validated_input' => json_encode($input, JSON_THROW_ON_ERROR), 'completed_at' => now(),
            ]);
        }
        $lastDate = $state['last_activity_date'];
        if ($lastDate !== $today->toDateString()) {
            $streak = $lastDate === $today->subDay()->toDateString() ? $state['current_streak'] + 1 : 1;
            DB::table('learning_progress')->updateOrInsert(['user_id' => $user->id], [
                'current_streak' => $streak, 'longest_streak' => max($streak, $state['longest_streak']),
                'last_activity_date' => $today->toDateString(),
                'created_at' => DB::table('learning_progress')->where('user_id', $user->id)->value('created_at') ?? now(), 'updated_at' => now(),
            ]);
        }

    }
}
