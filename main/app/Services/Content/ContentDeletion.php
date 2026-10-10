<?php

namespace App\Services\Content;

use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\ModuleRevision;
use App\Services\Publishing\OwnedContentDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContentDeletion extends OwnedContentDeletion
{
    protected function modelClass(string $kind): string
    {
        return match ($kind) {
            'module' => LearningModule::class, 'course' => LearningCourse::class, default => abort(404)
        };
    }

    protected function specificDependencies(string $kind, Model $content): array
    {
        if ($kind === 'course') {
            return [
                'Course enrollment and progress' => DB::table('course_enrollments')->where('course_id', $content->id)->exists(),
                'Learner enrollment audit history' => DB::table('audit_events')->where('event', 'course.enrolled')->where('context->course_id', $content->id)->exists(),
            ];
        }

        return [
            'Pinned course revisions' => DB::table('course_revision_modules')->where('module_id', $content->id)->exists(),
            'Validated learner activity' => DB::table('learning_activity_days')->where('level', 'module-'.$content->id)->exists(),
            'Learner completion history' => DB::table('learning_level_completions')->where('level', 'module-'.$content->id)->exists(),
        ];
    }

    protected function removeDefinition(string $kind, Model $content): void
    {
        if ($kind === 'module') {
            foreach (ModuleRevision::where('module_id', $content->id)->cursor() as $revision) {
                foreach ($revision->attachments as $asset) {
                    app(ModuleMedia::class)->queueRemoval($content->created_by, $asset);
                }
            }
            DB::table('module_revisions')->where('module_id', $content->id)->delete();

            return;
        }
        $revisions = DB::table('course_revisions')->where('course_id', $content->id)->select('id');
        // These are this course's own composition rows; reusable modules are preserved.
        DB::table('course_revision_modules')->whereIn('course_revision_id', $revisions)->delete();
        DB::table('course_revisions')->where('course_id', $content->id)->delete();
    }
}
