<?php

namespace App\Services\Gamification;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\ChallengeSubmission;
use App\Models\User;
use App\Models\WeeklyEvent;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use App\Services\Transactions\FinancialNotices;
use App\Services\Transactions\WalletLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WeeklyResults
{
    public function capture(ChallengeSubmission $submission): void
    {
        if ($submission->weekly_event_id === null || $submission->completed_at === null || ! in_array($submission->status, ['Passed', 'Failed'], true)) {
            return;
        }
        $userId = DB::table('challenge_participations')->where('id', $submission->participation_id)->value('user_id');
        DB::table('weekly_attempt_scores')->insertOrIgnore(['id' => $submission->id, 'submission_id' => $submission->id, 'event_id' => $submission->weekly_event_id,
            'user_id' => $userId, 'score' => intdiv($submission->passed_cases * 10000, $submission->total_cases), 'completed_at' => $submission->completed_at]);
    }

    public function prepare(int $eventId): void
    {
        DB::transaction(function () use ($eventId): void {
            app(WeeklyEvents::class)->lockCalendar();
            $event = WeeklyEvent::whereKey($eventId)->lockForUpdate()->firstOrFail();
            if ($event->ends_at->isFuture() || $event->status !== 'Ended'
                || ChallengeSubmission::where('weekly_event_id', $eventId)->whereNull('completed_at')->exists()) {
                throw ValidationException::withMessages(['results' => 'Wait until the event closes and every committed evaluation finishes.']);
            }
            $set = DB::table('weekly_result_sets')->where('event_id', $eventId)->lockForUpdate()->first();
            if ($set?->status === 'Published') {
                return;
            }
            // Import retained pre-amendment terminal outcomes as scores, never XP grants.
            ChallengeSubmission::where('weekly_event_id', $eventId)->whereIn('status', ['Passed', 'Failed'])->chunkById(100, function ($rows): void {
                foreach ($rows as $submission) {
                    $this->capture($submission);
                }
            });
            DB::table('weekly_result_sets')->insertOrIgnore(['event_id' => $eventId, 'created_at' => now()]);
            DB::table('weekly_results')->where('event_id', $eventId)->delete();
            DB::statement(<<<'SQL'
INSERT INTO weekly_results (event_id,user_id,submission_id,score,rank)
SELECT event_id,user_id,submission_id,score,rank() OVER (ORDER BY score DESC)
FROM (
    SELECT DISTINCT ON (user_id) event_id,user_id,submission_id,score
    FROM weekly_attempt_scores WHERE event_id = ?
    ORDER BY user_id,score DESC,completed_at,id
) AS best
SQL, [$eventId]);
        }, 3);
    }

    public function publish(User $actor, string $sessionId, int $eventId, int $version, bool $confirmed): void
    {
        DB::transaction(function () use ($actor, $sessionId, $eventId, $version, $confirmed): void {
            $staff = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($staff, $sessionId);
            abort_unless(in_array($staff->account_role, [Role::Moderator, Role::Administrator], true), 403);
            if (! $confirmed) {
                throw ValidationException::withMessages(['confirmed' => 'Confirm publishing immutable weekly results.']);
            }
            $this->prepare($eventId);
            $event = WeeklyEvent::findOrFail($eventId);
            $set = DB::table('weekly_result_sets')->where('event_id', $eventId)->lockForUpdate()->firstOrFail();
            if ($set->status === 'Published') {
                return;
            }
            if ($event->record_version !== $version) {
                throw ValidationException::withMessages(['record_version' => 'The event changed. Reload its final results.']);
            }
            $results = DB::table('weekly_results')->where('event_id', $eventId)->orderBy('rank')->orderBy('user_id')->get();
            $users = User::whereIn('id', $results->pluck('user_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $ledger = app(WalletLedger::class);
            $allowance = $ledger->rewardAllowance()['available'];
            $nominal = $this->shares($results->all());
            $total = array_sum($nominal);
            foreach ($results as $row) {
                $user = $users->get($row->user_id);
                $eligible = $user?->account_status === AccountStatus::Active && $user->email_verified_at !== null
                    && in_array($user->account_role, [Role::Learner, Role::Contributor, Role::Instructor], true);
                $reward = $event->reward_mode === 'Capped' && $event->economy_policy_version === config('economy.policy_version') && $eligible
                    ? ($allowance >= $total ? ($nominal[$row->user_id] ?? 0) : ($total === 0 ? 0 : intdiv(($nominal[$row->user_id] ?? 0) * $allowance, $total))) : 0;
                if ($reward > 0) {
                    $ledger->reward($row->user_id, 'weekly:'.$eventId.':user:'.$row->user_id, $reward);
                    app(FinancialNotices::class)->queue($row->user_id, 'weekly:'.$eventId.':user:'.$row->user_id, 'Weekly KodeBit reward confirmed', ['kodebits' => $reward]);
                }
                DB::table('weekly_results')->where('event_id', $eventId)->where('user_id', $row->user_id)->update(['reward_kb' => $reward]);
            }
            DB::table('weekly_result_sets')->where('event_id', $eventId)->update(['status' => 'Published', 'published_by' => $staff->id, 'published_at' => now()]);
            app(AuditRecorder::class)->record($staff->id, null, 'weekly.results_published', 'weekly_event', (string) $eventId,
                ['policy_version' => $event->economy_policy_version, 'participants' => $results->count(), 'available_kb' => $allowance]);
        }, 3);
    }

    public function shares(array $rows): array
    {
        $shares = [];
        foreach (collect($rows)->groupBy('rank') as $rank => $group) {
            $pool = 0;
            for ($position = (int) $rank; $position < (int) $rank + $group->count(); $position++) {
                $pool += config('economy.weekly_prizes.'.$position, 0);
            }
            foreach ($group as $row) {
                $shares[$row->user_id] = $row->score === 10000 ? intdiv($pool, $group->count()) : 0;
            }
        }

        return $shares;
    }
}
