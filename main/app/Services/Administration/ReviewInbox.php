<?php

namespace App\Services\Administration;

use App\Models\CodingChallenge;
use App\Models\ContributorApplication;
use App\Models\InstructorApplication;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReviewInbox
{
    public const CATEGORIES = [
        'lessons' => ['label' => 'Lessons', 'route' => 'module-reviews.show', 'index' => 'module-reviews.index', 'model' => LearningModule::class],
        'courses' => ['label' => 'Courses', 'route' => 'course-reviews.show', 'index' => 'course-reviews.index', 'model' => LearningCourse::class],
        'challenges' => ['label' => 'Coding challenges', 'route' => 'challenge-reviews.show', 'index' => 'challenge-reviews.index', 'model' => CodingChallenge::class],
        'instructors' => ['label' => 'Instructor applications', 'route' => 'instructor-reviews.show', 'index' => 'instructor-reviews.index', 'model' => InstructorApplication::class],
        'contributors' => ['label' => 'Contributor applications', 'route' => 'contributor-reviews.show', 'index' => 'contributor-reviews.index', 'model' => ContributorApplication::class],
    ];

    public function pending(User $user, string $category): LengthAwarePaginator
    {
        $queries = [];
        foreach ([
            'lessons' => ['learning_modules', 'module_revisions', 'module_id'],
            'courses' => ['learning_courses', 'course_revisions', 'course_id'],
            'challenges' => ['coding_challenges', 'coding_challenge_revisions', 'challenge_id'],
        ] as $kind => [$contents, $revisions, $foreign]) {
            if (($category !== 'all' && $category !== $kind) || ! Gate::forUser($user)->allows('viewAny', self::CATEGORIES[$kind]['model'])) {
                continue;
            }
            // Review pages open the latest revision. Do not link a superseded submission.
            $queries[] = DB::table($revisions.' as revision')->join($contents.' as content', 'content.id', '=', 'revision.'.$foreign)
                ->join('users as creator', 'creator.id', '=', 'content.created_by')
                ->where('revision.review_status', 'Pending')->whereIn('content.status', ['Draft', 'Published'])
                ->where('content.created_by', '!=', $user->id)
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from($revisions.' as newer')
                    ->whereColumn('newer.'.$foreign, 'content.id')->whereColumn('newer.id', '>', 'revision.id'))
                ->selectRaw('? as kind, content.id as target_id, revision.title, creator.username, revision.number, revision.updated_at as submitted_at', [$kind]);
        }
        foreach (['instructors' => ['instructor_applications', 'verification_status'], 'contributors' => ['contributor_applications', 'approval_status']] as $kind => [$table, $status]) {
            if (($category !== 'all' && $category !== $kind) || ! Gate::forUser($user)->allows('viewAny', self::CATEGORIES[$kind]['model'])) {
                continue;
            }
            $queries[] = DB::table($table.' as application')->join('users as creator', 'creator.id', '=', 'application.user_id')
                ->where('application.'.$status, 'Pending')->where('application.user_id', '!=', $user->id)
                ->selectRaw('? as kind, application.id as target_id, creator.username as title, creator.username, null::integer as number, application.updated_at as submitted_at', [$kind]);
        }
        $union = array_shift($queries);
        abort_if($union === null, 403);
        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        return DB::query()->fromSub($union, 'pending')->orderBy('submitted_at')->orderBy('kind')->orderBy('target_id')->paginate(15)->withQueryString();
    }
}
