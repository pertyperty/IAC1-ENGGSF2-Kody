<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->account_status === AccountStatus::Active && $actor->email_verified_at !== null
            && in_array($actor->account_role, [Role::Moderator, Role::Administrator], true);
    }

    public function manage(User $actor, User $account): bool
    {
        $roles = [Role::Learner, Role::Contributor, Role::Instructor];
        if ($actor->account_role === Role::Administrator) {
            $roles[] = Role::Moderator;
        }

        return $this->viewAny($actor) && $actor->id !== $account->id && $account->anonymized_at === null
            && in_array($account->account_role, $roles, true);
    }

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
