<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ReviewPublicationRequest;
use App\Models\CourseRevision;
use App\Models\LearningCourse;
use App\Services\Content\CoursePublishing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CourseReviewController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', LearningCourse::class);
        $revisions = CourseRevision::where('review_status', 'Pending')->whereHas('course', fn ($query) => $query->whereIn('status', ['Draft', 'Published']))->with('course')->orderBy('id')->paginate(15);

        return response()->view('content.course-reviews', compact('revisions'))->header('Cache-Control', 'no-store, private');
    }

    public function show(LearningCourse $course): Response
    {
        Gate::authorize('review', $course);

        return response()->view('content.course-review', ['course' => $course, 'revision' => $course->latestRevision->load('modules.revision')])->header('Cache-Control', 'no-store, private');
    }

    public function review(ReviewPublicationRequest $request, LearningCourse $course, CoursePublishing $publishing): RedirectResponse
    {
        Gate::authorize('review', $course);
        $publishing->review($request->user(), $request->session()->getId(), $course, (int) $request->validated('record_version'),
            $request->validated('decision'), $request->validated('review_notes'));

        return redirect()->route('course-reviews.show', $course)->with('status', 'Course review saved.');
    }
}
