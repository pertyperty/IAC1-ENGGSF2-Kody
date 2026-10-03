<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;

class WeeklyEventPolicy
{
    // E02 names Moderators; no additional event-management role is inferred.
    public function manage(User $user): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null
            && $user->account_role === Role::Moderator;
    }
}
