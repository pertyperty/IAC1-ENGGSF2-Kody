<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\SaveModuleRequest;
use App\Models\GamePreset;
use App\Models\LearningModule;
use App\Services\Content\ModulePublishing;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ModuleStudioController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('create', LearningModule::class);
        $modules = LearningModule::where('created_by', $request->user()->id)->with('latestRevision')->orderByDesc('id')->paginate(15);

        return response()->view('content.studio', compact('modules'))->header('Cache-Control', 'no-store, private');
    }

    public function create(): Response
    {
        Gate::authorize('create', LearningModule::class);

        return response()->view('content.editor', ['module' => null, 'revision' => null, 'presets' => $this->presets()])->header('Cache-Control', 'no-store, private');
    }

    public function store(SaveModuleRequest $request, ModulePublishing $publishing): RedirectResponse
    {
        Gate::authorize('create', LearningModule::class);
        $module = $publishing->save($request->user(), $request->session()->getId(), $request->validated());

        return redirect()->route('studio.edit', $module)->with('status', 'Draft saved. Bring your idea to life, then submit it for review.');
    }

    public function edit(LearningModule $module): Response
    {
        Gate::authorize('viewOwned', $module);

        return response()->view('content.editor', ['module' => $module, 'revision' => $module->latestRevision, 'presets' => $this->presets()])->header('Cache-Control', 'no-store, private');
    }

    public function update(SaveModuleRequest $request, LearningModule $module, ModulePublishing $publishing): RedirectResponse
    {
        Gate::authorize('update', $module);
        $publishing->save($request->user(), $request->session()->getId(), $request->validated(), $module);

        return redirect()->route('studio.edit', $module)->with('status', 'New draft saved. Your approved version stays live until this revision is approved.');
    }

    public function archiveConfirmation(LearningModule $module): Response
    {
        Gate::authorize('archive', $module);

        return response()->view('content.archive', compact('module'))->header('Cache-Control', 'no-store, private');
    }

    public function archive(Request $request, LearningModule $module, ModulePublishing $publishing): RedirectResponse
    {
        Gate::authorize('archive', $module);
        $data = $request->validate(['record_version' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']]);
        $publishing->archive($request->user(), $request->session()->getId(), $module, (int) $data['record_version']);

        return redirect()->route('studio.edit', $module)->with('status', 'Adventure archived. Your history is preserved.');
    }

    public function submit(Request $request, LearningModule $module, ModulePublishing $publishing): RedirectResponse
    {
        Gate::authorize('update', $module);
        $data = $request->validate(['record_version' => ['required', 'integer', 'min:1']]);
        $publishing->submit($request->user(), $request->session()->getId(), $module, (int) $data['record_version']);

        return redirect()->route('studio.edit', $module)->with('status', 'Sent for review. You can continue editing after the decision.');
    }

    private function presets(): Collection
    {
        return GamePreset::where('status', 'Active')->whereNotNull('current_revision_id')->with('currentRevision')->orderBy('name')->limit(100)->get();
    }
}
