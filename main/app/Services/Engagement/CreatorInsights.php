<?php

namespace App\Services\Engagement;

use App\Enums\Role;
use App\Models\CodingChallenge;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreatorInsights
{
    public function snapshot(User $actor, string $sessionId, int $days): array
    {
        abort_unless(in_array($days, [7, 30, 90], true), 422);
        $user = User::findOrFail($actor->id);
        app(CurrentAccountSession::class)->assert($user, $sessionId);
        Gate::forUser($user)->authorize('create', CodingChallenge::class);
        $start = CarbonImmutable::now('Asia/Manila')->startOfDay()->subDays($days - 1);
        $end = $start->addDays($days);
        $modules = DB::table('learning_modules')->where('created_by', $user->id)->select('id');
        $challenges = DB::table('coding_challenges')->where('created_by', $user->id)->select('id');
        $opens = DB::table('content_accesses')->where('kind', 'module')->whereIn('module_id', $modules)->where('user_id', '!=', $user->id);
        $wins = DB::table('learning_activity_days as activity')->join('learning_modules as module', DB::raw("concat('module-', module.id)"), '=', 'activity.level')
            ->where('module.created_by', $user->id)->where('activity.user_id', '!=', $user->id)->whereIn('activity.kind', ['game', 'quiz']);
        $reads = DB::table('module_read_completions')->whereIn('module_id', $modules)->where('user_id', '!=', $user->id);
        $attempts = DB::table('challenge_submissions as submission')->join('challenge_participations as participation', 'participation.id', '=', 'submission.participation_id')
            ->whereIn('submission.challenge_id', $challenges)->where('participation.user_id', '!=', $user->id);
        foreach ([[$opens, 'first_opened_at'], [$wins, 'activity.completed_at'], [$reads, 'completed_at'], [$attempts, 'submission.submitted_at']] as [$query, $column]) {
            $query->where($column, '>=', $start->utc())->where($column, '<', $end->utc());
        }
        $confirmed = (clone $attempts)->count();
        $passed = (clone $attempts)->where('submission.status', 'Passed')->count();
        $terminal = (clone $attempts)->whereIn('submission.status', ['Passed', 'Failed'])->count();
        if ($user->account_role === Role::Instructor) {
            $metrics = [['label' => 'First-time readers', 'value' => (clone $opens)->distinct()->count('user_id'), 'note' => 'Distinct other participants whose first module opening falls in this window.'],
                ['label' => 'Validated game / quiz wins', 'value' => (clone $wins)->count(), 'note' => 'Saved daily activity; replays on different days can count again.'],
                ['label' => 'First article completions', 'value' => (clone $reads)->count(), 'note' => 'Explicit reading completions, kept separate from assessed wins.'],
                ['label' => 'Course starts', 'value' => DB::table('course_enrollments')->whereIn('course_id', DB::table('learning_courses')->where('created_by', $user->id)->select('id'))->where('user_id', '!=', $user->id)->where('enrolled_at', '>=', $start->utc())->where('enrolled_at', '<', $end->utc())->count(), 'note' => 'New enrollments in your courses during this window.']];
            $charts = [['title' => 'First module openings', 'note' => 'A first recorded opening is not a page-view counter.', 'values' => $this->daily($opens, 'first_opened_at')],
                ['title' => 'Validated learning activity', 'note' => 'Game and quiz wins only. Article reading is excluded.', 'values' => $this->daily($wins, 'activity.completed_at')]];
        } else {
            $metrics = [['label' => 'Quest participants', 'value' => (clone $attempts)->distinct()->count('participation.user_id'), 'note' => 'Distinct other participants who confirmed a submission.'],
                ['label' => 'Confirmed attempts', 'value' => $confirmed, 'note' => 'Durable submissions to your quests, including weekly contexts.'],
                ['label' => 'Verified passes', 'value' => $passed, 'note' => 'Current Passed verdicts for attempts submitted in this window.'],
                ['label' => 'Pass rate', 'value' => $terminal ? round($passed * 100 / $terminal).'%' : 'No results', 'note' => 'Passed / (Passed + Failed); pending and unavailable results excluded.']];
            $charts = [['title' => 'Confirmed quest attempts', 'note' => 'Grouped by submission day, not evaluation completion day.', 'values' => $this->daily($attempts, 'submission.submitted_at')],
                ['title' => 'Verified passing attempts', 'note' => 'Current Passed results grouped by their original submission day.', 'values' => $this->daily((clone $attempts)->where('submission.status', 'Passed'), 'submission.submitted_at')]];
        }
        foreach ($charts as &$chart) {
            $chart['points'] = [];
            for ($i = 0; $i < $days; $i++) {
                $date = $start->addDays($i);
                $chart['points'][] = ['date' => $date->toDateString(), 'label' => $date->format('M j'), 'value' => (int) ($chart['values'][$date->toDateString()] ?? 0)];
            }
            unset($chart['values']);
        }
        unset($chart);

        return compact('metrics', 'charts', 'days') + ['from' => $start->toDateString(), 'through' => $end->subDay()->toDateString()];
    }

    private function daily(Builder $query, string $column): array
    {
        // Identifiers are fixed above; timestamps have explicit Manila business-day grouping.
        return (clone $query)->selectRaw("({$column} AT TIME ZONE 'Asia/Manila')::date as day, count(*) as total")
            ->groupByRaw("({$column} AT TIME ZONE 'Asia/Manila')::date")->orderBy('day')->pluck('total', 'day')->all();
    }
}
