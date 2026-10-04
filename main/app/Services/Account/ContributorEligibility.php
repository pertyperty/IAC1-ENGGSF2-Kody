<?php

namespace App\Services\Account;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ContributorEligibility
{
    public function snapshot(User $user): array
    {
        // Reading completion advances courses but is not a server-validated win. Union by module identity,
        // so course reuse, revisions and daily replay cannot inflate the threshold.
        $standalone = DB::table('learning_activity_days')->where('user_id', $user->id)
            ->whereRaw("level ~ '^module-[1-9][0-9]*$'")->selectRaw('substring(level from 8) AS module_id');
        $courses = DB::table('course_module_progress as progress')
            ->join('course_enrollments as enrollment', 'enrollment.id', '=', 'progress.enrollment_id')
            ->join('course_revision_modules as assignment', 'assignment.id', '=', 'progress.assignment_id')
            ->where('enrollment.user_id', $user->id)->whereNotNull('progress.completed_at')
            ->whereRaw("progress.validated_input->>'kind' IS DISTINCT FROM 'reading'")
            ->selectRaw('CAST(assignment.module_id AS text) AS module_id');
        $modules = DB::query()->fromSub($standalone->union($courses), 'completions')->count();
        $challenges = DB::table('challenge_submissions as submission')
            ->join('challenge_participations as participation', 'participation.id', '=', 'submission.participation_id')
            ->where('participation.user_id', $user->id)->where('submission.status', 'Passed')->whereNotNull('submission.completed_at')
            ->distinct()->count('submission.challenge_id');
        $days = max(0, (int) $user->created_at->diffInDays(now(), false));

        return ['account_age_days' => $days, 'completed_modules_count' => $modules, 'completed_challenges_count' => $challenges,
            'eligible' => $user->account_role === Role::Learner && $user->account_status === AccountStatus::Active
                && $user->email_verified_at !== null && $days >= 30 && $modules >= 25 && $challenges >= 50];
    }
}
