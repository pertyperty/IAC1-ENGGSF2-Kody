<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\ContributorApplication;
use App\Models\User;

class ContributorApplicationPolicy
{
    public function viewOwn(User $user): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null
            && in_array($user->account_role, [Role::Learner, Role::Contributor, Role::Instructor], true);
    }

    public function create(User $user): bool
    {
        return $user->account_role === Role::Learner && $user->account_status === AccountStatus::Active && $user->email_verified_at !== null;
    }

    public function viewAny(User $user): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null
            && in_array($user->account_role, [Role::Moderator, Role::Administrator], true);
    }

    public function view(User $user, ContributorApplication $application): bool
    {
        return $this->viewAny($user) && $user->id !== $application->user_id;
    }

    public function review(User $user, ContributorApplication $application): bool
    {
        return $this->view($user, $application);
    }
}
