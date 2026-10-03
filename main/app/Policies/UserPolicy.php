<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Models\User;

class UserPolicy
{
    public function update(User $actor, User $account): bool
    {
        return $actor->id === $account->id && $actor->account_status === AccountStatus::Active
            && $actor->email_verified_at !== null;
    }
}
