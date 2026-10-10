<?php

namespace App\Services\Gamification;

use App\Models\LearningCourse;
use App\Models\TowerLevel;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Games\GameAssessment;
use App\Services\Games\QuizAuthoring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TowerProgression
{
    public function counts(int $userId): array
    {
        return ['completed_count' => DB::table('tower_clearances')->where('user_id', $userId)->count(),
            'total' => DB::table('tower_levels')->whereNotNull('current_revision_id')->count()];
    }

    public function snapshot(?int $userId): array
    {
        $completed = $userId === null ? [] : DB::table('tower_clearances')->where('user_id', $userId)->pluck('level_id')->all();
        $previous = true;
        $levels = TowerLevel::with('currentRevision')->whereNotNull('current_revision_id')->orderBy('position')->limit(1000)->get();
        $steps = $levels->map(function ($level) use ($completed, &$previous): array {
            $cleared = in_array($level->id, $completed, true);
            $step = ['level' => $level, 'completed' => $cleared, 'unlocked' => $previous];
            $previous = $previous && $cleared;

            return $step;
        });

        return ['steps' => $steps, 'completed_count' => count($completed), 'total' => $levels->count(),
            'next' => $steps->first(fn ($step) => $step['unlocked'] && ! $step['completed'])['level'] ?? null];
    }

    public function assertUnlocked(User $user, TowerLevel $level): void
    {
        Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
        $missing = TowerLevel::where('position', '<', $level->position)->whereNotNull('current_revision_id')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('tower_clearances')->whereColumn('level_id', 'tower_levels.id')->where('user_id', $user->id))->exists();
        abort_if($missing, 403, 'Clear the previous tower levels first.');
    }

    public function complete(User $actor, string $sessionId, TowerLevel $level, int $revisionId, int $stage, array $input): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $level, $revisionId, $stage, $input): array {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $current = TowerLevel::whereKey($level->id)->lockForUpdate()->firstOrFail();
            $this->assertUnlocked($user, $current);
            abort_unless($current->current_revision_id === $revisionId, 409, 'This level changed. Reload to play the latest version. Your earlier clears are safe.');
            $stages = $current->currentRevision->stages;
            abort_unless(isset($stages[$stage]), 404);
            $done = DB::table('tower_stage_completions')->where('user_id', $user->id)->where('revision_id', $revisionId)->pluck('stage')->all();
            for ($index = 0; $index < $stage; $index++) {
                abort_unless(in_array($index, $done, true), 403, 'Complete the previous stage first.');
            }
            $instance = $stages[$stage];
            $valid = $instance['template'] === 'choice-quiz'
                ? app(QuizAuthoring::class)->succeeds($instance, $input)
                : (isset($input['program']) && app(GameAssessment::class)->succeeds($instance, $input));
            if (! $valid) {
                throw ValidationException::withMessages(['completion' => 'The objective is not complete yet. Adjust your attempt and try again.']);
            }
            if (! in_array($stage, $done, true)) {
                DB::table('tower_stage_completions')->insert(['user_id' => $user->id, 'revision_id' => $revisionId, 'stage' => $stage, 'completed_at' => now()]);
            }
            $cleared = $stage === count($stages) - 1;
            if ($cleared) {
                DB::table('tower_clearances')->insertOrIgnore(['user_id' => $user->id, 'level_id' => $current->id, 'revision_id' => $revisionId, 'completed_at' => now()]);
                app(LearningProgression::class)->recordTowerActivity($user, $sessionId, $current->id, $instance,
                    $input + ['tower_revision_id' => $revisionId, 'tower_stage' => $stage]);
            }
            $next = TowerLevel::where('position', '>', $current->position)->whereNotNull('current_revision_id')->orderBy('position')->first();

            return ['message' => $cleared ? 'Tower level cleared! Your streak is saved.' : 'Stage cleared. Continue to the next stage.',
                'tower' => ['cleared' => $cleared, 'next' => $cleared ? ($next ? route('tower.show', $next) : route('home')) : null, 'stage' => $stage]];
        });
    }
}
