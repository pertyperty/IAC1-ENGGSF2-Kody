<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\SaveCourseRequest;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Services\Content\CoursePublishing;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CourseStudioController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('create', LearningCourse::class);
        $courses = LearningCourse::where('created_by', $request->user()->id)->with('latestRevision')->orderByDesc('id')->paginate(15);

        return response()->view('content.courses', compact('courses'))->header('Cache-Control', 'no-store, private');
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', LearningCourse::class);

        return response()->view('content.course-editor', ['course' => null, 'revision' => null, 'modules' => $this->availableModules([], $request)])->header('Cache-Control', 'no-store, private');
    }

    public function store(SaveCourseRequest $request, CoursePublishing $publishing): RedirectResponse
    {
        Gate::authorize('create', LearningCourse::class);
        $course = $publishing->save($request->user(), $request->session()->getId(), $request->validated());

        return redirect()->route('courses.edit', $course)->with('status', 'Course draft saved.');
    }

    public function edit(Request $request, LearningCourse $course): Response
    {
        Gate::authorize('update', $course);

        $revision = $course->latestRevision->load('modules.revision');

        return response()->view('content.course-editor', ['course' => $course, 'revision' => $revision, 'modules' => $this->availableModules($revision->modules->pluck('module_id')->all(), $request)])->header('Cache-Control', 'no-store, private');
    }

    public function update(SaveCourseRequest $request, LearningCourse $course, CoursePublishing $publishing): RedirectResponse
    {
        Gate::authorize('update', $course);
        $publishing->save($request->user(), $request->session()->getId(), $request->validated(), $course);

        return redirect()->route('courses.edit', $course)->with('status', 'New course draft saved.');
    }

    public function submit(Request $request, LearningCourse $course, CoursePublishing $publishing): RedirectResponse
    {
        Gate::authorize('update', $course);
        $data = $request->validate(['record_version' => ['required', 'integer', 'min:1']]);
        $publishing->submit($request->user(), $request->session()->getId(), $course, (int) $data['record_version']);

        return redirect()->route('courses.edit', $course)->with('status', 'Course sent for review.');
    }

    public function preview(LearningCourse $course, int $slot): Response
    {
        abort_unless(Gate::allows('update', $course) || Gate::allows('review', $course), 403);
        $assignment = $course->latestRevision->modules()->with('revision')->findOrFail($slot);

        return response()->view('content.course-preview', ['course' => $course, 'revision' => $assignment->revision])->header('Cache-Control', 'no-store, private');
    }

    private function availableModules(array $selectedIds, Request $request): Collection
    {
        $query = $request->validate(['q' => ['nullable', 'string', 'max:80']])['q'] ?? '';
        $recentIds = LearningModule::where('created_by', $request->user()->id)->where('status', 'Published')
            ->when(trim($query) !== '', fn ($builder) => $builder->whereHas('publishedRevision', fn ($revision) => $revision->where('title', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')))
            ->orderByDesc('id')->limit(200)->pluck('id');

        return LearningModule::where('created_by', $request->user()->id)->whereIn('id', $recentIds->merge($selectedIds)->unique())->with('publishedRevision')->orderByDesc('id')->get();
    }
}
