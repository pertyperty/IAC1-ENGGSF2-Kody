<?php

namespace App\Services\Engagement;

use App\Models\CodingChallenge;
use App\Models\CourseEnrollment;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PlatformDashboard
{
    public function snapshot(User $user): array
    {
        $gate = Gate::forUser($user);
        $courses = collect();
        $activity = collect();
        $quests = collect();
        if ($gate->allows('viewLearning', LearningCourse::class)) {
            $courses = CourseEnrollment::where('user_id', $user->id)->with('course', 'revision')
                ->select('course_enrollments.*')
                ->selectSub(DB::table('course_revision_modules')->selectRaw('count(*)')
                    ->whereColumn('course_revision_id', 'course_enrollments.course_revision_id'), 'total_lessons')
                ->selectSub(DB::table('course_module_progress')->selectRaw('count(*)')
                    ->whereColumn('enrollment_id', 'course_enrollments.id')->whereNotNull('completed_at'), 'completed_lessons')
                ->orderByDesc('enrolled_at')->orderByDesc('id')->limit(4)->get();
            $activity = DB::table('course_module_progress as progress')
                ->join('course_enrollments as enrollment', 'enrollment.id', '=', 'progress.enrollment_id')
                ->join('learning_courses as course', 'course.id', '=', 'enrollment.course_id')
                ->join('course_revision_modules as slot', 'slot.id', '=', 'progress.assignment_id')
                ->join('learning_modules as module', 'module.id', '=', 'slot.module_id')
                ->join('module_revisions as revision', 'revision.id', '=', 'slot.module_revision_id')
                ->where('enrollment.user_id', $user->id)->whereIn('course.status', ['Published', 'Archived'])->whereNull('course.staff_withdrawn_at')
                ->where('module.status', 'Published')->whereNull('module.staff_withdrawn_at')
                ->orderByDesc('progress.last_accessed_at')->orderByDesc('progress.id')->limit(6)
                ->get(['course.id as course_id', 'slot.id as slot_id', 'revision.title', 'progress.completed_at', 'progress.last_accessed_at']);
            $quests = DB::table('challenge_submissions as submission')
                ->join('challenge_participations as participation', 'participation.id', '=', 'submission.participation_id')
                ->join('coding_challenge_revisions as revision', 'revision.id', '=', 'submission.revision_id')
                ->where('participation.user_id', $user->id)->orderByDesc('submission.submitted_at')->orderByDesc('submission.id')->limit(5)
                ->get(['submission.id', 'revision.title', 'submission.status', 'submission.attempt', 'submission.submitted_at', 'submission.weekly_event_id']);
        }
        $creator = [];
        foreach ([
            [LearningModule::class, 'learning_modules', 'module_revisions', 'module_id', 'Adventures', 'studio.index'],
            [LearningCourse::class, 'learning_courses', 'course_revisions', 'course_id', 'Journeys', 'courses.index'],
            [CodingChallenge::class, 'coding_challenges', 'coding_challenge_revisions', 'challenge_id', 'Coding quests', 'challenges.index'],
        ] as [$model, $table, $revisions, $foreign, $label, $route]) {
            if (! $gate->allows('create', $model)) {
                continue;
            }
            $rows = DB::table($table.' as content')->join($revisions.' as revision', 'revision.'.$foreign, '=', 'content.id')
                ->where('content.created_by', $user->id)
                ->whereRaw('revision.number = (select max(latest.number) from '.$revisions.' as latest where latest.'.$foreign.' = content.id)')
                ->selectRaw('content.status, revision.review_status, count(*) as total')->groupBy('content.status', 'revision.review_status')->get();
            $creator[] = ['label' => $label, 'route' => $route,
                'published' => (int) $rows->where('status', 'Published')->sum('total'),
                'pending' => (int) $rows->whereIn('status', ['Draft', 'Published'])->where('review_status', 'Pending')->sum('total'),
                'drafts' => (int) $rows->whereIn('status', ['Draft', 'Published'])->whereIn('review_status', ['Draft', 'Rejected'])->sum('total')];
        }
        $unread = $user->unreadNotifications()->count();
        $updates = $user->notifications()->orderByDesc('created_at')->orderByDesc('id')->limit(3)->get();

        return compact('courses', 'activity', 'quests', 'creator', 'unread', 'updates');
    }
}
