<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\LearningCourse;
use App\Models\User;

class LearningCoursePolicy
{
    public function viewLearning(User $user): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null
            && in_array($user->account_role, [Role::Learner, Role::Contributor, Role::Instructor], true);
    }

    public function create(User $user): bool
    {
        return app(LearningModulePolicy::class)->create($user);
    }

    public function update(User $user, LearningCourse $course): bool
    {
        return $this->create($user) && $course->created_by === $user->id && in_array($course->status, ['Draft', 'Published'], true);
    }

    public function viewAny(User $user): bool
    {
        return app(LearningModulePolicy::class)->viewAny($user);
    }

    public function viewOwned(User $user, LearningCourse $course): bool
    {
        return $this->create($user) && $course->created_by === $user->id && $course->status !== 'Deleted';
    }

    public function delete(User $user, LearningCourse $course): bool
    {
        return $this->viewOwned($user, $course);
    }

    public function archive(User $user, LearningCourse $course): bool
    {
        return $this->viewOwned($user, $course) && $course->status === 'Published';
    }

    public function review(User $user, LearningCourse $course): bool
    {
        return $this->viewAny($user) && $course->created_by !== $user->id;
    }

    public function moderate(User $user, LearningCourse $content): bool
    {
        return $this->review($user, $content) && in_array($content->status, ['Published', 'Archived'], true);
    }
}
