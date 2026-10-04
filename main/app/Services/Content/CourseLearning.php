<?php

namespace App\Services\Content;

use App\Models\CourseEnrollment;
use App\Models\CourseRevisionModule;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use App\Services\Gamification\LearningProgression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CourseLearning
{
    public function enroll(User $actor, string $sessionId, int $courseId, int $revisionId): CourseEnrollment
    {
        return DB::transaction(function () use ($actor, $sessionId, $courseId, $revisionId): CourseEnrollment {
            $user = $this->account($actor, $sessionId);
            $course = LearningCourse::whereKey($courseId)->lockForUpdate()->firstOrFail();
            abort_if($course->isWithdrawn(), 404);
            $existing = CourseEnrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();
            if ($existing !== null && in_array($course->status, ['Published', 'Archived'], true)) {
                return $existing;
            }
            abort_unless($course->status === 'Published' && $course->published_revision_id === $revisionId, 409, 'This course changed. Reload before enrolling.');
            $revision = $course->publishedRevision;
            abort_unless($revision?->review_status === 'Approved', 404);
            $slots = $revision->modules()->with('revision')->get();
            $modules = LearningModule::whereIn('id', $slots->pluck('module_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($slots->isEmpty() || $slots->contains(fn ($slot) => $modules->get($slot->module_id)?->status !== 'Published' || $modules->get($slot->module_id)?->isWithdrawn() || $slot->revision->review_status !== 'Approved')) {
                throw ValidationException::withMessages(['course' => 'An adventure in this course is unavailable. Try another journey.']);
            }
            $enrollment = CourseEnrollment::create(['user_id' => $user->id, 'course_id' => $course->id,
                'course_revision_id' => $revision->id, 'enrolled_at' => now(), 'sequential' => $revision->sequential]);
            app(AuditRecorder::class)->record($user->id, $user->id, 'course.enrolled', 'course_enrollment', (string) $enrollment->id,
                ['course_id' => $course->id, 'revision_id' => $revision->id, 'access' => 'free']);

            return $enrollment;
        });
    }

    public function outline(User $actor, string $sessionId, int $courseId): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $courseId): array {
            $user = $this->account($actor, $sessionId);
            $course = LearningCourse::whereKey($courseId)->lockForUpdate()->firstOrFail();
            abort_if($course->isWithdrawn(), 404);
            $enrollment = CourseEnrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();
            abort_unless($course->status === 'Published' || ($course->status === 'Archived' && $enrollment !== null), 404);
            $revision = $enrollment?->revision ?? $course->publishedRevision;
            abort_unless($revision?->review_status === 'Approved', 404);
            $revision->load('modules.revision', 'modules.module');
            $progress = $enrollment === null ? collect() : DB::table('course_module_progress')->where('enrollment_id', $enrollment->id)->get()->keyBy('assignment_id');

            $unlocked = $revision->modules->mapWithKeys(fn ($slot) => [$slot->id => ! $enrollment?->sequential
                || $revision->modules->where('position', '<', $slot->position)->every(fn ($previous) => $progress->get($previous->id)?->completed_at !== null)]);

            return compact('course', 'enrollment', 'revision', 'progress', 'unlocked');
        });
    }

    public function lesson(User $actor, string $sessionId, int $courseId, int $slotId): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $courseId, $slotId): array {
            $user = $this->account($actor, $sessionId);
            [$course, $enrollment, $slot] = $this->entitlement($user, $courseId, $slotId);
            $this->visit($enrollment, $slot);
            $progress = DB::table('course_module_progress')->where('enrollment_id', $enrollment->id)->where('assignment_id', $slot->id)->first();

            return ['course' => $course, 'enrollment' => $enrollment, 'slot' => $slot, 'revision' => $slot->revision, 'progress' => $progress];
        });
    }

    public function complete(User $actor, string $sessionId, int $courseId, int $slotId, string $kind, array $input): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $courseId, $slotId, $kind, $input): array {
            $user = $this->account($actor, $sessionId);
            [, $enrollment, $slot] = $this->entitlement($user, $courseId, $slotId);
            $result = app(LearningProgression::class)->recordApprovedModule($user, $sessionId, $slot->module_id, $slot->module_revision_id, $kind, $input);
            $this->visit($enrollment, $slot);
            // Preserve the first validated clearance; subsequent wins can qualify a new daily streak.
            DB::table('course_module_progress')->where('enrollment_id', $enrollment->id)->where('assignment_id', $slot->id)->whereNull('completed_at')
                ->update(['completed_at' => now(), 'validated_input' => json_encode($input, JSON_THROW_ON_ERROR)]);

            return $result;
        });
    }

    public function markRead(User $actor, string $sessionId, int $courseId, int $slotId): void
    {
        DB::transaction(function () use ($actor, $sessionId, $courseId, $slotId): void {
            $user = $this->account($actor, $sessionId);
            [, $enrollment, $slot] = $this->entitlement($user, $courseId, $slotId);
            abort_unless($slot->revision->assessment === null, 409, 'Complete the assessment to clear this adventure.');
            $query = DB::table('course_module_progress')->where('enrollment_id', $enrollment->id)->where('assignment_id', $slot->id);
            abort_unless($query->exists(), 409, 'Open this lesson before marking it as read.');
            if ($query->whereNull('completed_at')->update(['completed_at' => now(), 'validated_input' => json_encode(['kind' => 'reading'], JSON_THROW_ON_ERROR)])) {
                app(AuditRecorder::class)->record($user->id, $user->id, 'course.lesson_read', 'course_enrollment', (string) $enrollment->id,
                    ['assignment_id' => $slot->id, 'revision_id' => $slot->module_revision_id]);
            }
        });
    }

    private function account(User $actor, string $sessionId): User
    {
        $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
        app(CurrentAccountSession::class)->assert($user, $sessionId);
        Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);

        return $user;
    }

    private function entitlement(User $user, int $courseId, int $slotId): array
    {
        $course = LearningCourse::whereKey($courseId)->lockForUpdate()->firstOrFail();
        abort_if($course->isWithdrawn(), 404);
        abort_unless(in_array($course->status, ['Published', 'Archived'], true), 404);
        $enrollment = CourseEnrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();
        abort_unless($enrollment !== null, 403, 'Join this course to open its adventures.');
        $slot = CourseRevisionModule::where('course_revision_id', $enrollment->course_revision_id)->with('revision')->findOrFail($slotId);
        $module = LearningModule::whereKey($slot->module_id)->lockForUpdate()->firstOrFail();
        abort_unless($module->status === 'Published' && ! $module->isWithdrawn() && $slot->revision->review_status === 'Approved' && $enrollment->revision->review_status === 'Approved', 404);
        if ($enrollment->sequential) {
            $unfinished = CourseRevisionModule::where('course_revision_id', $enrollment->course_revision_id)->where('position', '<', $slot->position)
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('course_module_progress')
                    ->whereColumn('assignment_id', 'course_revision_modules.id')->where('enrollment_id', $enrollment->id)->whereNotNull('completed_at'))->exists();
            abort_if($unfinished, 403, 'Complete the previous adventures to unlock this lesson.');
        }

        return [$course, $enrollment, $slot];
    }

    private function visit(CourseEnrollment $enrollment, CourseRevisionModule $slot): void
    {
        $query = DB::table('course_module_progress')->where('enrollment_id', $enrollment->id)->where('assignment_id', $slot->id);
        if ($query->exists()) {
            $query->update(['last_accessed_at' => now()]);
        } else {
            DB::table('course_module_progress')->insert(['enrollment_id' => $enrollment->id, 'assignment_id' => $slot->id,
                'course_revision_id' => $enrollment->course_revision_id, 'first_accessed_at' => now(), 'last_accessed_at' => now()]);
        }
    }
}
