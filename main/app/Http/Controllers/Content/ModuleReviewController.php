<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ReviewPublicationRequest;
use App\Models\LearningModule;
use App\Models\ModuleRevision;
use App\Services\Content\ModulePublishing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ModuleReviewController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', LearningModule::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80', 'not_regex:/\x00/']]);
        $revisions = ModuleRevision::where('review_status', 'Pending')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('title', 'ilike', '%'.addcslashes($search, '%_\\').'%'))
            ->whereNotExists(fn ($newer) => $newer->selectRaw('1')->from('module_revisions as newer')->whereColumn('newer.module_id', 'module_revisions.module_id')->whereColumn('newer.id', '>', 'module_revisions.id'))->whereHas('module', fn ($query) => $query->whereIn('status', ['Draft', 'Published'])->where('created_by', '!=', $request->user()->id))->with('module')->orderBy('id')->paginate(15)->withQueryString();

        return response()->view('content.reviews', compact('revisions', 'filters'))->header('Cache-Control', 'no-store, private');
    }

    public function show(LearningModule $module): Response
    {
        Gate::authorize('review', $module);

        return response()->view('content.review', ['module' => $module, 'revision' => $module->latestRevision])->header('Cache-Control', 'no-store, private');
    }

    public function review(ReviewPublicationRequest $request, LearningModule $module, ModulePublishing $publishing): RedirectResponse
    {
        Gate::authorize('review', $module);
        $publishing->review($request->user(), $request->session()->getId(), $module, (int) $request->validated('record_version'),
            $request->validated('decision'), $request->validated('review_notes'));

        return redirect()->route('module-reviews.show', $module)->with('status', 'Review saved. Only approved revisions appear in Learning.');
    }
}
