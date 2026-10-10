<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Learning\CompleteTowerRequest;
use App\Models\TowerLevel;
use App\Services\Gamification\TowerProgression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TowerController extends Controller
{
    public function index(Request $request, TowerProgression $tower): Response|RedirectResponse
    {
        if ($request->user() && $request->user()->account_role !== Role::Learner) {
            return redirect()->route('dashboard');
        }

        return response()->view('tower.map', ['tower' => $tower->snapshot($request->user()?->id)])->header('Cache-Control', 'no-store, private');
    }

    public function show(Request $request, TowerLevel $level, TowerProgression $tower): Response|RedirectResponse
    {
        abort_if($level->current_revision_id === null, 404);
        if ($request->user() === null && $level->position > 3) {
            return redirect()->route('welcome');
        }
        if ($request->user()) {
            $tower->assertUnlocked($request->user(), $level);
        }
        $done = $request->user() ? DB::table('tower_stage_completions')->where('user_id', $request->user()->id)->where('revision_id', $level->current_revision_id)->pluck('stage')->all() : [];

        return response()->view('tower.level', ['level' => $level, 'revision' => $level->currentRevision, 'done' => $done])->header('Cache-Control', 'no-store, private');
    }

    public function complete(CompleteTowerRequest $request, TowerLevel $level, int $revision, int $stage, TowerProgression $tower): JsonResponse
    {
        return response()->json($tower->complete($request->user(), $request->session()->getId(), $level, $revision, $stage, $request->validated()))->header('Cache-Control', 'no-store, private');
    }
}
