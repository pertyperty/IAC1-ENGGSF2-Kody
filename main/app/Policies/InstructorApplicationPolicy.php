<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\InstructorApplication;
use App\Models\User;

class InstructorApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null
            && in_array($user->account_role, [Role::Moderator, Role::Administrator], true);
    }

    public function view(User $user, InstructorApplication $application): bool
    {
        return $this->viewAny($user) && $user->id !== $application->user_id;
    }

    public function review(User $user, InstructorApplication $application): bool
    {
        return $this->view($user, $application);
    }
}
