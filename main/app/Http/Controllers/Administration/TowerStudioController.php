<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\SaveTowerRequest;
use App\Models\TowerLevel;
use App\Services\Games\TowerEditorialUpgrade;
use App\Services\Games\TowerPublishing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TowerStudioController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage', TowerLevel::class);

        return response()->view('tower.studio', ['levels' => TowerLevel::with('currentRevision')->orderBy('position')->paginate(25)])->header('Cache-Control', 'no-store, private');
    }

    public function edit(Request $request, TowerLevel $level): Response
    {
        Gate::authorize('manage', TowerLevel::class);

        $revision = $level->currentRevision;
        abort_if($revision === null, 404);
        $suggested = app(TowerEditorialUpgrade::class)->suggestion($level);
        $usingSuggestion = $suggested !== null && $request->boolean('suggested');
        if ($usingSuggestion) {
            $revision = clone $revision;
            $revision->fill($suggested);
            $revision->source_notes = $revision->source_notes.' Editorial proposal 2026-10-18: scenario-specific teaching and corrected hints; original Kody material.';
        }

        return response()->view('tower.editor', ['level' => $level, 'revision' => $revision, 'suggested' => $suggested, 'usingSuggestion' => $usingSuggestion])->header('Cache-Control', 'no-store, private');
    }

    public function update(SaveTowerRequest $request, TowerLevel $level, TowerPublishing $publishing): RedirectResponse
    {
        $publishing->save($request->user(), $request->session()->getId(), $level, $request->validated());

        return redirect()->route('tower-studio.edit', $level)->with('status', 'Tower revision published. Existing clearances are preserved.');
    }
}
