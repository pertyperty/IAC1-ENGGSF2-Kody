<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\CodingChallenge;
use App\Models\User;

class CodingChallengePolicy
{
    public function create(User $user): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null
            && in_array($user->account_role, [Role::Contributor, Role::Instructor], true);
    }

    public function viewOwned(User $user, CodingChallenge $challenge): bool
    {
        return $this->create($user) && $challenge->created_by === $user->id && $challenge->status !== 'Deleted';
    }

    public function update(User $user, CodingChallenge $challenge): bool
    {
        return $this->viewOwned($user, $challenge) && in_array($challenge->status, ['Draft', 'Published'], true);
    }

    public function delete(User $user, CodingChallenge $challenge): bool
    {
        return $this->viewOwned($user, $challenge);
    }

    public function archive(User $user, CodingChallenge $challenge): bool
    {
        return $this->viewOwned($user, $challenge) && $challenge->status === 'Published';
    }

    public function viewAny(User $user): bool
    {
        return app(LearningModulePolicy::class)->viewAny($user);
    }

    public function review(User $user, CodingChallenge $challenge): bool
    {
        return $this->viewAny($user) && $challenge->created_by !== $user->id;
    }

    public function moderate(User $user, CodingChallenge $content): bool
    {
        return $this->review($user, $content) && in_array($content->status, ['Published', 'Archived'], true);
    }
}
