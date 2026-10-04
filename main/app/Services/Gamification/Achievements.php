<?php

namespace App\Services\Gamification;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\ChallengeSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Achievements
{
    public function award(int $userId, string $reference, int $xp): void
    {
        DB::transaction(function () use ($userId, $reference, $xp): void {
            $user = User::find($userId);
            if ($xp <= 0 || $user === null || $user->account_status !== AccountStatus::Active || $user->email_verified_at === null
                || ! in_array($user->account_role, [Role::Learner, Role::Contributor, Role::Instructor], true)) {
                return;
            }
            DB::table('xp_totals')->insertOrIgnore(['user_id' => $userId, 'xp' => 0]);
            DB::table('xp_totals')->where('user_id', $userId)->lockForUpdate()->firstOrFail();
            if (DB::table('xp_awards')->where('user_id', $userId)->where('reference', $reference)->exists()) {
                return;
            }
            DB::table('xp_awards')->insert(['id' => (string) Str::uuid(), 'user_id' => $userId, 'reference' => $reference,
                'xp' => $xp, 'created_at' => now()]);
            DB::table('xp_totals')->where('user_id', $userId)->increment('xp', $xp);
        }, 3);
    }

    public function challenge(ChallengeSubmission $submission): void
    {
        if ($submission->status !== 'Passed') {
            return;
        }
        $userId = (int) DB::table('challenge_participations')->where('id', $submission->participation_id)->value('user_id');
        $difficulty = DB::table('coding_challenge_revisions')->where('id', $submission->revision_id)->value('difficulty');
        $this->award($userId, 'challenge:'.$submission->challenge_id, config('economy.xp.challenge.'.$difficulty, 0));
        if ($submission->weekly_event_id !== null) {
            $this->award($userId, 'weekly:'.$submission->weekly_event_id, config('economy.xp.weekly_pass'));
        }
    }

    public function snapshot(int $userId): array
    {
        $xp = (int) DB::table('xp_totals')->where('user_id', $userId)->value('xp');

        return $this->fromXp($xp);
    }

    public function fromXp(int $xp): array
    {
        $rank = config('economy.ranks')[0];
        $next = null;
        foreach (config('economy.ranks') as $threshold => $name) {
            if ($xp >= $threshold) {
                $rank = $name;
            } else {
                $next = ['name' => $name, 'threshold' => $threshold];
                break;
            }
        }

        return compact('xp', 'rank', 'next');
    }
}
