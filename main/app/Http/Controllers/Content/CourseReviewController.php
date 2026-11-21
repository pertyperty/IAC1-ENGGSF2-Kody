<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ReviewPublicationRequest;
use App\Models\CourseRevision;
use App\Models\LearningCourse;
use App\Services\Content\CoursePublishing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CourseReviewController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', LearningCourse::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80', 'not_regex:/\x00/']]);
        $revisions = CourseRevision::where('review_status', 'Pending')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('title', 'ilike', '%'.addcslashes($search, '%_\\').'%'))
            ->whereNotExists(fn ($newer) => $newer->selectRaw('1')->from('course_revisions as newer')->whereColumn('newer.course_id', 'course_revisions.course_id')->whereColumn('newer.id', '>', 'course_revisions.id'))->whereHas('course', fn ($query) => $query->whereIn('status', ['Draft', 'Published'])->where('created_by', '!=', $request->user()->id))->with('course')->orderBy('id')->paginate(15)->withQueryString();

        return response()->view('content.course-reviews', compact('revisions', 'filters'))->header('Cache-Control', 'no-store, private');
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
