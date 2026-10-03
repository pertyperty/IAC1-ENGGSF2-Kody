<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function update(User $actor, User $account): bool
    {
        return $actor->id === $account->id && $actor->account_status === AccountStatus::Active
            && $actor->email_verified_at !== null;
    }

    public function archive(User $actor, User $account): bool
    {
        return $this->update($actor, $account) && in_array($actor->account_role, [Role::Learner, Role::Contributor, Role::Instructor], true);
    }

    public function delete(User $actor, User $account): bool
    {
        return $this->archive($actor, $account);
    }
}
