<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\ChallengeSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChallengeSubmissionPolicy
{
    public function create(User $user): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null
            && in_array($user->account_role, [Role::Learner, Role::Contributor, Role::Instructor], true);
    }

    public function view(User $user, ChallengeSubmission $submission): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null
            && DB::table('challenge_participations')->where('id', $submission->participation_id)->where('user_id', $user->id)->exists();
    }
}
