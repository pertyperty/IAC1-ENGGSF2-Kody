<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ReviewModuleRequest;
use App\Models\LearningModule;
use App\Models\ModuleRevision;
use App\Services\Content\ModulePublishing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ModuleReviewController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', LearningModule::class);
        $revisions = ModuleRevision::where('review_status', 'Pending')->whereHas('module', fn ($query) => $query->whereIn('status', ['Draft', 'Published']))->with('module')->orderBy('id')->paginate(15);

        return response()->view('content.reviews', compact('revisions'))->header('Cache-Control', 'no-store, private');
    }

    public function show(LearningModule $module): Response
    {
        Gate::authorize('review', $module);

        return response()->view('content.review', ['module' => $module, 'revision' => $module->latestRevision])->header('Cache-Control', 'no-store, private');
    }

    public function review(ReviewModuleRequest $request, LearningModule $module, ModulePublishing $publishing): RedirectResponse
    {
        Gate::authorize('review', $module);
        $publishing->review($request->user(), $request->session()->getId(), $module, (int) $request->validated('record_version'),
            $request->validated('decision'), $request->validated('review_notes'));

        return redirect()->route('module-reviews.show', $module)->with('status', 'Review saved. Only approved revisions appear in Learning.');
    }
}
