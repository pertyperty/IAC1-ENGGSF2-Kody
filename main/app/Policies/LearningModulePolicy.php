<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\LearningModule;
use App\Models\User;

class LearningModulePolicy
{
    private function active(User $user): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null;
    }

    public function create(User $user): bool
    {
        return $this->active($user) && $user->account_role === Role::Instructor;
    }

    public function update(User $user, LearningModule $module): bool
    {
        return $this->create($user) && $module->created_by === $user->id && in_array($module->status, ['Draft', 'Published'], true);
    }

    public function viewOwned(User $user, LearningModule $module): bool
    {
        return $this->create($user) && $module->created_by === $user->id && $module->status !== 'Deleted';
    }

    public function delete(User $user, LearningModule $module): bool
    {
        return $this->viewOwned($user, $module);
    }

    public function archive(User $user, LearningModule $module): bool
    {
        return $this->viewOwned($user, $module) && $module->status === 'Published';
    }

    public function viewAny(User $user): bool
    {
        return $this->active($user) && in_array($user->account_role, [Role::Moderator, Role::Administrator], true);
    }

    public function review(User $user, LearningModule $module): bool
    {
        return $this->viewAny($user) && $module->created_by !== $user->id;
    }

    public function moderate(User $user, LearningModule $content): bool
    {
        return $this->review($user, $content) && in_array($content->status, ['Published', 'Archived'], true);
    }
}
