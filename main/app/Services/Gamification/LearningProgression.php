<?php

namespace App\Services\Gamification;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\Games\CommandGarden;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
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

        return ['current_streak' => $current, 'longest_streak' => $progress?->longest_streak ?? 0,
            'last_activity_date' => $progress?->last_activity_date, 'levels' => $levels,
            'completed_count' => count(array_intersect(array_keys($levels), $completed))];
    }

    public function record(User $actor, string $sessionId, string $level, string $kind, array $input): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $level, $kind, $input): array {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            if ($user->account_status !== AccountStatus::Active || $user->email_verified_at === null
                || $user->active_session_hash === null || ! hash_equals($user->active_session_hash, hash('sha256', $sessionId))
                || ! $user->active_session_expires_at?->isFuture()) {
                throw new AuthorizationException('Sign in with your current account session.');
            }
            $state = $this->snapshot($user->id);
            if (! ($state['levels'][$level]['unlocked'] ?? false)) {
                throw new AuthorizationException('Clear the previous level first.');
            }
            $instance = $kind === 'game' ? config('learning.instances')[$level] : config('learning.quizzes')[$level];
            $valid = $kind === 'game'
                ? app(CommandGarden::class)->succeeds($instance, $input['program'], $input['repeat'] ?? false, $input['conditional'] ?? false)
                : $input['answer'] === $instance['answer'];
            if (! $valid) {
                throw ValidationException::withMessages(['completion' => 'That attempt did not complete the objective. Try again.']);
            }
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
            if ($kind === 'game' && ! $state['levels'][$level]['completed']) {
                DB::table('learning_level_completions')->insert([
                    'id' => (string) Str::uuid(), 'user_id' => $user->id, 'level' => $level,
                    'template_version' => $instance['version'], 'completed_at' => now(),
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

            return ['message' => $kind === 'game' ? 'Level cleared and saved. Your streak is up to date.' : 'Quiz win saved. Your streak is up to date.', 'progress' => $this->snapshot($user->id)];
        });
    }
}
