<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Learning\CompleteTowerRequest;
use App\Models\TowerLevel;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Gamification\TowerProgression;
use App\Services\Gamification\TowerTracks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TowerController extends Controller
{
    public function index(Request $request, TowerProgression $tower): Response|RedirectResponse
    {
        if ($request->user() && ! in_array($request->user()->account_role, [Role::Learner, Role::Instructor], true)) {
            return redirect()->route('dashboard');
        }

        $choice = $request->query('track');
        $track = is_string($choice) && array_key_exists($choice, app(TowerTracks::class)->all()) ? $choice : $request->session()->get('tower.track', $request->user()?->coding_proficiency ?? 'beginner');
        if ($request->user()) {
            $request->session()->put('tower.track', $track);
        } else {
            $track = 'beginner';
        }

        $view = $request->query('view', $request->session()->get('tower.view', 'map'));
        $view = in_array($view, ['map', 'list'], true) ? $view : 'map';
        if ($request->user()) {
            $request->session()->put('tower.view', $view);
        }

        return response()->view('tower.'.$view, ['tower' => $tower->snapshot($request->user()?->id, $track)])->header('Cache-Control', 'no-store, private');
    }

    public function proficiency(Request $request, TowerProgression $tower): RedirectResponse
    {
        $data = $request->validate(['proficiency' => ['required', 'in:beginner,intermediate,expert']]);
        DB::transaction(function () use ($request, $data): void {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $request->session()->getId());
            abort_unless($user->account_role === Role::Learner, 403);
            $user->forceFill(['coding_proficiency' => $data['proficiency']])->save();
        });
        $request->session()->put('tower.track', $data['proficiency']);
        $next = $tower->continuation($request->user()->id, $data['proficiency'])['next'];

        return $next ? redirect($next['url']) : redirect()->route('home', ['track' => $data['proficiency']]);
    }

    public function show(Request $request, TowerLevel $level, TowerProgression $tower): Response|RedirectResponse
    {
        abort_if($level->current_revision_id === null, 404);
        if ($request->user() === null && $level->position > 3) {
            return redirect()->route('welcome');
        }
        if ($request->user()) {
            $tower->assertUnlocked($request->user(), $level);
            $request->session()->put('tower.track', app(TowerTracks::class)->key($level));
        }
        $done = $request->user() ? DB::table('tower_stage_completions')->where('user_id', $request->user()->id)->where('revision_id', $level->current_revision_id)->pluck('stage')->all() : [];

        $bookmarked = $request->user() && DB::table('tower_review_items')->where('user_id', $request->user()->id)->where('level_id', $level->id)->exists();

        return response()->view('tower.level', ['level' => $level, 'revision' => $level->currentRevision, 'done' => $done, 'bookmarked' => $bookmarked])->header('Cache-Control', 'no-store, private');
    }

    public function complete(CompleteTowerRequest $request, TowerLevel $level, int $revision, int $stage, TowerProgression $tower): JsonResponse
    {
        return response()->json($tower->complete($request->user(), $request->session()->getId(), $level, $revision, $stage, $request->validated()))->header('Cache-Control', 'no-store, private');
    }
}
