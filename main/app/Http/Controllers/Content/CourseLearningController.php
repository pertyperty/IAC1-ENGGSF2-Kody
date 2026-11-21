<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\EnrollCourseRequest;
use App\Http\Requests\Learning\CompleteGameRequest;
use App\Http\Requests\Learning\CompleteQuizRequest;
use App\Http\Requests\Learning\MarkLessonReadRequest;
use App\Models\CourseEnrollment;
use App\Models\CourseRevision;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Services\Content\CourseLearning;
use App\Services\Content\LearningHistory;
use App\Services\Engagement\ContentFeedback;
use App\Services\Gamification\TowerProgression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CourseLearningController extends Controller
{
    public function catalog(Request $request): Response
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80', 'not_regex:/\x00/'], 'difficulty' => ['nullable', 'in:Beginner,Intermediate,Advanced']]);
        $query = $filters['q'] ?? '';
        $courses = CourseRevision::select(['id', 'course_id', 'title', 'description', 'category', 'difficulty', 'estimated_duration', 'price_kb', 'minimum_xp'])
            ->with(['course.creator:id,username,account_role,account_status,email_verified_at', 'course.creator.creatorVerification'])->where('review_status', 'Approved')->whereHas('course', fn ($builder) => $builder->where('status', 'Published')->whereNull('staff_withdrawn_at')->whereColumn('published_revision_id', 'course_revisions.id'))
            ->when(trim($query) !== '', fn ($builder) => $builder->where(fn ($search) => $search->where('title', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')->orWhere('description', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')->orWhere('category', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')))
            ->when($filters['difficulty'] ?? null, fn ($builder, $difficulty) => $builder->where('difficulty', $difficulty))
            ->orderByDesc('id')->paginate(12)->withQueryString();

        $participant = app(LearningHistory::class)->participant($request);
        $enrollments = $participant === null ? new Collection : CourseEnrollment::where('user_id', $participant->id)->whereIn('course_id', $courses->pluck('course_id'))->with('course', 'revision')->get();
        $saved = $participant === null ? collect() : app(CourseLearning::class)->summaries($participant, $enrollments);
        $courseProgress = $enrollments->mapWithKeys(fn ($enrollment) => [$enrollment->course_id => $saved->get($enrollment->id) + ['edition' => $enrollment->course_revision_id]]);

        return response()->view('learning.courses', compact('courses', 'query', 'courseProgress', 'participant', 'filters'))->header('Cache-Control', 'no-store, private');
    }

    public function mine(Request $request): Response
    {
        Gate::authorize('viewLearning', LearningCourse::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80', 'not_regex:/\x00/'], 'type' => ['nullable', 'in:all,courses,modules,bookmarks']]);
        $enrollments = CourseEnrollment::where('user_id', $request->user()->id)
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->whereHas('revision', fn ($revision) => $revision->where('title', 'ilike', '%'.addcslashes($search, '%_\\').'%')))
            ->with('course', 'revision')->orderByDesc('enrolled_at')->orderByDesc('id')->paginate(15)->withQueryString();
        $history = app(LearningHistory::class);
        $participant = $history->participant($request);
        $courseProgress = app(CourseLearning::class)->summaries($participant, $enrollments->getCollection());
        $recentModules = $history->recentModules($participant);
        if (trim($filters['q'] ?? '') !== '') {
            $search = mb_strtolower(trim($filters['q']));
            $recentModules = $recentModules->filter(fn ($module) => $module->status === 'Published' && ! $module->isWithdrawn() && str_contains(mb_strtolower($module->publishedRevision->title ?? ''), $search));
        }
        $moduleProgress = $history->modules($participant, $recentModules->pluck('publishedRevision')->filter());

        $bookmarks = DB::table('learning_bookmarks')->where('user_id', $participant->id)->orderByDesc('created_at')->limit(100)->get();
        $bookmarkContent = collect();
        foreach (['module' => LearningModule::class, 'course' => LearningCourse::class] as $kind => $model) {
            $items = $model::whereIn('id', $bookmarks->where('kind', $kind)->pluck('content_id'))->select(['id', 'status', 'staff_withdrawn_at', 'published_revision_id'])
                ->with('publishedRevision:id,title')->get()->keyBy('id');
            foreach ($bookmarks->where('kind', $kind) as $bookmark) {
                $item = $items->get($bookmark->content_id);
                $available = $item && $item->status === 'Published' && ! $item->isWithdrawn() && $item->publishedRevision;
                $title = $available ? $item->publishedRevision->getAttribute('title') : ucfirst($kind).' unavailable';
                if (trim($filters['q'] ?? '') !== '' && (! $available || ! str_contains(mb_strtolower($title), mb_strtolower(trim($filters['q']))))) {
                    continue;
                }
                $bookmarkContent->push(['kind' => $kind, 'id' => $bookmark->content_id, 'title' => $title, 'available' => (bool) $available]);
            }
        }
        $reviewItems = DB::table('tower_review_items')->where('user_id', $participant->id)->orderByDesc('created_at')->limit(100)->get();
        $reviewLevels = $reviewItems->isEmpty() ? collect() : app(TowerProgression::class)->snapshot($participant->id)['steps']->keyBy(fn ($step) => $step['level']->id);
        if (trim($filters['q'] ?? '') !== '') {
            $reviewItems = $reviewItems->filter(function ($item) use ($filters, $reviewLevels) {
                $step = $reviewLevels->get($item->level_id);

                return $step && $step['unlocked'] && str_contains(mb_strtolower($step['level']->currentRevision->title), mb_strtolower(trim($filters['q'])));
            });
        }

        return response()->view('learning.my-courses', compact('enrollments', 'courseProgress', 'recentModules', 'moduleProgress', 'filters', 'bookmarkContent', 'reviewItems', 'reviewLevels'))->header('Cache-Control', 'no-store, private');
    }

    public function show(Request $request, LearningCourse $course, CourseLearning $learning, ContentFeedback $feedback): Response
    {
        $data = $learning->outline($request->user(), $request->session()->getId(), $course->id);
        $data['feedback'] = $feedback->read($request->user(), $request->session()->getId(), 'course', $course->id, true);

        return response()->view('learning.course', $data)->header('Cache-Control', 'no-store, private');
    }

    public function enroll(EnrollCourseRequest $request, LearningCourse $course, CourseLearning $learning): RedirectResponse
    {
        $learning->enroll($request->user(), $request->session()->getId(), $course->id, (int) $request->validated('revision_id'), $request->boolean('confirmed'));

        return redirect()->route('course-learning.show', $course)->with('status', 'You have joined this course. Choose a module to begin.');
    }

    public function lesson(Request $request, LearningCourse $course, int $slot, CourseLearning $learning, ContentFeedback $feedback): Response
    {
        $data = $learning->lesson($request->user(), $request->session()->getId(), $course->id, $slot);
        $data['feedback'] = $feedback->read($request->user(), $request->session()->getId(), 'module', $data['slot']->module_id);

        return response()->view('learning.course-lesson', $data)->header('Cache-Control', 'no-store, private');
    }

    public function game(CompleteGameRequest $request, LearningCourse $course, int $slot, CourseLearning $learning): JsonResponse
    {
        return response()->json($learning->complete($request->user(), $request->session()->getId(), $course->id, $slot, 'game', $request->validated()))->header('Cache-Control', 'no-store');
    }

    public function markRead(MarkLessonReadRequest $request, LearningCourse $course, int $slot, CourseLearning $learning): RedirectResponse
    {
        $learning->markRead($request->user(), $request->session()->getId(), $course->id, $slot);

        return redirect()->route('course-learning.show', $course)->with('status', 'Module marked as read. Your course progress is saved.');
    }

    public function quiz(CompleteQuizRequest $request, LearningCourse $course, int $slot, CourseLearning $learning): JsonResponse
    {
        return response()->json($learning->complete($request->user(), $request->session()->getId(), $course->id, $slot, 'quiz', $request->validated()))->header('Cache-Control', 'no-store');
    }
}
