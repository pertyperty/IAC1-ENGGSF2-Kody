<?php

namespace App\Services\Content;

use App\Models\CourseRevision;
use App\Models\LearningCourse;
use Illuminate\Support\Collection;

class CourseTrail
{
    /** Present an already-authorized, revision-pinned course snapshot. */
    public function snapshot(LearningCourse $course, CourseRevision $revision, Collection $progress, Collection $unlocked, bool $accessible, ?int $current = null): array
    {
        $steps = $revision->modules->map(function ($slot) use ($course, $progress, $unlocked, $accessible): array {
            $available = $slot->module->status === 'Published' && ! $slot->module->isWithdrawn();
            $completed = $progress->get($slot->id)?->completed_at !== null;
            $ready = $accessible && $available && $unlocked->get($slot->id);

            return ['id' => $slot->id, 'position' => $slot->position,
                'title' => $slot->module->isWithdrawn() ? 'Adventure unavailable' : $slot->revision->title,
                'kind' => $slot->revision->assessment ? 'Play & clear' : 'Read & reflect',
                'state' => ! $available ? 'Unavailable' : (! $accessible ? 'Join to play' : ($completed ? 'Cleared' : ($ready ? 'Ready' : 'Locked'))),
                'completed' => $completed, 'visited' => $progress->has($slot->id), 'url' => $ready ? route('course-learning.lesson', [$course, $slot->id]) : null];
        });
        $total = $steps->count();
        $completed = $steps->where('completed', true)->count();
        $next = $steps->first(fn ($step) => ! $step['completed'] && $step['url'] !== null);
        $currentCompleted = $current === null || $progress->get($current)?->completed_at !== null;

        return ['course_id' => $course->id, 'completed' => $completed, 'total' => $total,
            'finished' => $accessible && $total > 0 && $completed === $total,
            'current_completed' => $currentCompleted,
            'next' => $currentCompleted ? $next : null, 'steps' => $steps->all()];
    }
}
