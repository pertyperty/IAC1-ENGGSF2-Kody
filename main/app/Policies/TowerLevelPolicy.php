<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;

class TowerLevelPolicy
{
    public function manage(User $user): bool
    {
        return $user->account_role === Role::Administrator && $user->account_status === AccountStatus::Active && $user->email_verified_at !== null;
    }
}
