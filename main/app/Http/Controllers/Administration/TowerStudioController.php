<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\SaveTowerRequest;
use App\Models\TowerLevel;
use App\Services\Games\TowerPublishing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TowerStudioController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage', TowerLevel::class);

        return response()->view('tower.studio', ['levels' => TowerLevel::with('currentRevision')->orderBy('position')->paginate(25)])->header('Cache-Control', 'no-store, private');
    }

    public function edit(TowerLevel $level): Response
    {
        Gate::authorize('manage', TowerLevel::class);

        return response()->view('tower.editor', ['level' => $level, 'revision' => $level->currentRevision])->header('Cache-Control', 'no-store, private');
    }

    public function update(SaveTowerRequest $request, TowerLevel $level, TowerPublishing $publishing): RedirectResponse
    {
        $publishing->save($request->user(), $request->session()->getId(), $level, $request->validated());

        return redirect()->route('tower-studio.edit', $level)->with('status', 'Tower revision published. Existing clearances are preserved.');
    }
}
