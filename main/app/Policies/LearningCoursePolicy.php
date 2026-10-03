<?php

namespace App\Policies;

use App\Models\LearningCourse;
use App\Models\User;

class LearningCoursePolicy
{
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

    public function review(User $user, LearningCourse $course): bool
    {
        return $this->viewAny($user) && $course->created_by !== $user->id;
    }
}
