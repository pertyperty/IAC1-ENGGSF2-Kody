<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\GamePreset;
use App\Models\User;

class GamePresetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->account_role === Role::Administrator && $user->account_status === AccountStatus::Active && $user->email_verified_at !== null;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, GamePreset $preset): bool
    {
        return $this->viewAny($user) && $preset->status === 'Active';
    }
}
