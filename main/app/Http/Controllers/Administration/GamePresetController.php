<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\InactivateGamePresetRequest;
use App\Http\Requests\Administration\SaveGamePresetRequest;
use App\Models\GamePreset;
use App\Services\Games\GamePresets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GamePresetController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', GamePreset::class);
        $presets = GamePreset::with('currentRevision')->orderByDesc('id')->paginate(20);

        return response()->view('games.preset-index', compact('presets'))->header('Cache-Control', 'no-store, private');
    }

    public function create(): Response
    {
        Gate::authorize('create', GamePreset::class);

        return response()->view('games.preset-editor', ['preset' => null, 'instance' => null, 'history' => null, 'uses' => 0])->header('Cache-Control', 'no-store, private');
    }

    public function edit(GamePreset $preset): Response
    {
        Gate::authorize('viewAny', GamePreset::class);
        $instance = $preset->currentRevision->instance;
        $history = $preset->revisions()->orderByDesc('number')->paginate(10);
        $uses = DB::table('module_revisions')->whereIn('game_preset_revision_id', $preset->revisions()->select('id'))->count();

        return response()->view('games.preset-editor', compact('preset', 'instance', 'history', 'uses'))->header('Cache-Control', 'no-store, private');
    }

    public function store(SaveGamePresetRequest $request, GamePresets $presets): RedirectResponse
    {
        $preset = $presets->save($request->user(), $request->session()->getId(), $request->validated());

        return redirect()->route('game-presets.edit', $preset)->with('status', 'Preset created. Creators can now use its saved defaults.');
    }

    public function update(SaveGamePresetRequest $request, GamePreset $preset, GamePresets $presets): RedirectResponse
    {
        $presets->save($request->user(), $request->session()->getId(), $request->validated(), $preset);

        return redirect()->route('game-presets.edit', $preset)->with('status', 'New version saved. Existing module assessments keep their original version.');
    }

    public function inactivate(InactivateGamePresetRequest $request, GamePreset $preset, GamePresets $presets): RedirectResponse
    {
        $presets->inactivate($request->user(), $request->session()->getId(), $preset, (int) $request->validated('record_version'), true);

        return redirect()->route('game-presets.edit', $preset)->with('status', 'Preset inactive. Existing assessments and history are preserved.');
    }
}
