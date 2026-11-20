<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\InactivateGamePresetRequest;
use App\Http\Requests\Administration\SaveGamePresetRequest;
use App\Models\GamePreset;
use App\Services\Games\GamePresets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GamePresetController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', GamePreset::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100', 'not_regex:/\\x00/'], 'status' => ['nullable', 'in:Active,Inactive']]);
        $presets = GamePreset::with('currentRevision')->withCount('lessonRevisions')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('name', 'ilike', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')->orderBy('id')->paginate(20)->withQueryString();

        return response()->view('games.preset-index', compact('presets', 'filters'))->header('Cache-Control', 'no-store, private');
    }

    public function edit(GamePreset $preset): Response
    {
        Gate::authorize('viewAny', GamePreset::class);
        $instance = $preset->currentRevision->instance;
        $history = $preset->revisions()->orderByDesc('number')->paginate(10);
        $uses = DB::table('module_revisions')->whereIn('game_preset_revision_id', $preset->revisions()->select('id'))->count();

        $usage = DB::table('module_revisions as revision')
            ->join('game_preset_revisions as preset_revision', 'preset_revision.id', '=', 'revision.game_preset_revision_id')
            ->where('preset_revision.preset_id', $preset->id)
            ->select(['revision.module_id', 'revision.title', 'revision.number', 'revision.review_status', 'preset_revision.number as preset_number'])
            ->orderByDesc('revision.id')->paginate(10, ['*'], 'usage_page')->withQueryString();

        return response()->view('games.preset-editor', compact('preset', 'instance', 'history', 'uses', 'usage'))->header('Cache-Control', 'no-store, private');
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
