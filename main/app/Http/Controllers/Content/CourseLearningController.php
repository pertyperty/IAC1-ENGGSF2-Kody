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
use App\Services\Content\CourseLearning;
use App\Services\Engagement\ContentFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CourseLearningController extends Controller
{
    public function catalog(Request $request): Response
    {
        $query = $request->validate(['q' => ['nullable', 'string', 'max:80']])['q'] ?? '';
        $courses = CourseRevision::select(['id', 'course_id', 'title', 'description', 'category', 'difficulty', 'estimated_duration'])
            ->where('review_status', 'Approved')->whereHas('course', fn ($builder) => $builder->where('status', 'Published')->whereNull('staff_withdrawn_at')->whereColumn('published_revision_id', 'course_revisions.id'))
            ->when(trim($query) !== '', fn ($builder) => $builder->where(fn ($search) => $search->where('title', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')->orWhere('description', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')->orWhere('category', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')))
            ->orderByDesc('id')->paginate(12)->withQueryString();

        return response()->view('learning.courses', compact('courses', 'query'));
    }

    public function mine(Request $request): Response
    {
        Gate::authorize('viewLearning', LearningCourse::class);
        $enrollments = CourseEnrollment::where('user_id', $request->user()->id)->with('course', 'revision')->orderByDesc('enrolled_at')->orderByDesc('id')->paginate(15);

        return response()->view('learning.my-courses', compact('enrollments'))->header('Cache-Control', 'no-store, private');
    }

    public function show(Request $request, LearningCourse $course, CourseLearning $learning, ContentFeedback $feedback): Response
    {
        $data = $learning->outline($request->user(), $request->session()->getId(), $course->id);
        $data['feedback'] = $feedback->read($request->user(), $request->session()->getId(), 'course', $course->id, true);

        return response()->view('learning.course', $data)->header('Cache-Control', 'no-store, private');
    }

    public function enroll(EnrollCourseRequest $request, LearningCourse $course, CourseLearning $learning): RedirectResponse
    {
        $learning->enroll($request->user(), $request->session()->getId(), $course->id, (int) $request->validated('revision_id'));

        return redirect()->route('course-learning.show', $course)->with('status', 'You have joined this journey. Pick an adventure to begin!');
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

        return redirect()->route('course-learning.show', $course)->with('status', 'Lesson marked as read. Your course progress is saved.');
    }

    public function quiz(CompleteQuizRequest $request, LearningCourse $course, int $slot, CourseLearning $learning): JsonResponse
    {
        return response()->json($learning->complete($request->user(), $request->session()->getId(), $course->id, $slot, 'quiz', $request->validated()))->header('Cache-Control', 'no-store');
    }
}
