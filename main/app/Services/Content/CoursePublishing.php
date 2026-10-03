<?php

namespace App\Services\Content;

use App\Models\CourseRevision;
use App\Models\CourseRevisionModule;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use App\Services\Notifications\InAppNotifications;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CoursePublishing
{
    public function save(User $actor, string $sessionId, array $data, ?LearningCourse $course = null): LearningCourse
    {
        return $this->transaction(function () use ($actor, $sessionId, $data, $course): LearningCourse {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            if ($course === null) {
                Gate::forUser($user)->authorize('create', LearningCourse::class);
                $current = LearningCourse::create(['created_by' => $user->id, 'title' => $data['title'], 'category' => $data['category']]);
                $number = 1;
            } else {
                $current = LearningCourse::whereKey($course->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($user)->authorize('update', $current);
                $this->version($current, (int) $data['record_version']);
                $latest = $current->latestRevision;
                if ($latest->review_status === 'Pending') {
                    throw ValidationException::withMessages(['course' => 'This revision is awaiting review. Save changes after the review finishes.']);
                }
                $number = $latest->number + 1;
                $changes = ['record_version' => $current->record_version + 1];
                if ($current->status === 'Draft') {
                    $changes += ['title' => $data['title'], 'category' => $data['category']];
                }
                $current->update($changes);
            }
            if (LearningCourse::whereKeyNot($current->id)->whereRaw('lower(btrim(title)) = lower(btrim(?)) AND lower(btrim(category)) = lower(btrim(?))', [$data['title'], $data['category']])->exists()) {
                throw ValidationException::withMessages(['title' => 'A course already uses that title in this category.']);
            }
            $revision = CourseRevision::create(['course_id' => $current->id, 'number' => $number, 'title' => $data['title'],
                'description' => $data['description'], 'category' => $data['category'], 'difficulty' => $data['difficulty'],
                'estimated_duration' => $data['estimated_duration']]);
            $modules = LearningModule::whereIn('id', $data['module_ids'])->with('publishedRevision')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($data['module_ids'] as $position => $id) {
                $module = $modules->get($id);
                if ($module === null || $module->created_by !== $user->id || $module->status !== 'Published' || $module->publishedRevision?->review_status !== 'Approved') {
                    throw ValidationException::withMessages(['module_ids' => 'Choose your own published adventures. Archived or unapproved modules cannot be assigned.']);
                }
                CourseRevisionModule::create(['course_revision_id' => $revision->id, 'module_id' => $module->id,
                    'module_revision_id' => $module->published_revision_id, 'position' => $position + 1]);
            }
            $this->audit($user, $current, 'course.saved', ['revision' => $number]);

            return $current;
        });
    }

    public function submit(User $actor, string $sessionId, LearningCourse $course, int $version): void
    {
        DB::transaction(function () use ($actor, $sessionId, $course, $version): void {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $current = LearningCourse::whereKey($course->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('update', $current);
            $this->version($current, $version);
            $revision = $current->latestRevision;
            if ($revision->review_status !== 'Draft' || $revision->modules()->count() === 0) {
                throw ValidationException::withMessages(['course' => 'Save a draft with at least one adventure before submitting for review.']);
            }
            $this->availableModules($revision, $current->created_by);
            $revision->update(['review_status' => 'Pending']);
            $current->increment('record_version');
            $this->audit($user, $current, 'course.submitted', ['revision' => $revision->number]);
        });
    }

    public function review(User $actor, string $sessionId, LearningCourse $course, int $version, string $decision, ?string $notes): void
    {
        $this->transaction(function () use ($actor, $sessionId, $course, $version, $decision, $notes): void {
            $users = User::whereIn('id', [$actor->id, $course->created_by])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $user = $users->get($actor->id);
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $current = LearningCourse::whereKey($course->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('review', $current);
            $this->version($current, $version);
            $revision = $current->latestRevision;
            if ($revision->review_status !== 'Pending' || ! in_array($current->status, ['Draft', 'Published'], true)) {
                throw ValidationException::withMessages(['course' => 'Only the current pending revision can be reviewed.']);
            }
            if (! in_array($decision, ['Approved', 'Rejected'], true) || ($decision === 'Rejected' && trim($notes ?? '') === '')) {
                throw ValidationException::withMessages(['decision' => 'Choose a valid decision and give a reason for rejection.']);
            }
            if ($decision === 'Approved') {
                Gate::forUser($users->get($current->created_by))->authorize('create', LearningCourse::class);
                $this->availableModules($revision, $current->created_by);
                $current->update(['title' => $revision->title, 'category' => $revision->category,
                    'published_revision_id' => $revision->id, 'status' => 'Published']);
            }
            $revision->update(['review_status' => $decision, 'reviewed_by' => $user->id, 'reviewed_at' => now(), 'review_notes' => $notes]);
            $current->increment('record_version');
            $this->audit($user, $current, 'course.reviewed', ['revision' => $revision->number, 'decision' => $decision]);
            app(InAppNotifications::class)->courseReviewed($users->get($current->created_by), $revision);
        });
    }

    private function availableModules(CourseRevision $revision, int $ownerId): void
    {
        $slots = $revision->modules()->with('revision')->get();
        $modules = LearningModule::whereIn('id', $slots->pluck('module_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($slots as $slot) {
            $module = $modules->get($slot->module_id);
            if ($module === null || $module->created_by !== $ownerId || $module->status !== 'Published' || $slot->revision->review_status !== 'Approved') {
                throw ValidationException::withMessages(['module_ids' => 'A saved adventure is no longer available. Save an updated course draft before publishing.']);
            }
        }
    }

    private function transaction(callable $operation): mixed
    {
        try {
            return DB::transaction($operation);
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505' && str_contains($exception->getMessage(), 'learning_courses_title_category_unique')) {
                throw ValidationException::withMessages(['title' => 'A course already uses that title in this category.']);
            }
            throw $exception;
        }
    }

    private function version(LearningCourse $course, int $version): void
    {
        if ($course->record_version !== $version) {
            throw ValidationException::withMessages(['record_version' => 'This course changed. Reload before saving or reviewing.']);
        }
    }

    private function audit(User $actor, LearningCourse $course, string $event, array $context): void
    {
        app(AuditRecorder::class)->record($actor->id, $course->created_by, $event, 'learning_course', (string) $course->id,
            $context + ['version' => $course->record_version]);
    }
}
