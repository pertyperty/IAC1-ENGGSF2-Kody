<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\LearningCourse;
use App\Models\TowerLevel;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Gamification\TowerProgression;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReviewQueueController extends Controller
{
    public function index(Request $request, TowerProgression $tower): Response
    {
        Gate::authorize('viewLearning', LearningCourse::class);
        $levels = $tower->snapshot($request->user()->id)['steps']->keyBy(fn ($step) => $step['level']->id);
        $items = DB::table('tower_review_items')->where('user_id', $request->user()->id)->orderByDesc('created_at')->limit(100)->get();

        return response()->view('learning.review-queue', compact('items', 'levels'))->header('Cache-Control', 'no-store, private');
    }

    public function update(Request $request, TowerLevel $level, TowerProgression $tower): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', 'in:add,remove']]);
        DB::transaction(function () use ($request, $level, $tower, $data): void {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $request->session()->getId());
            Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
            $query = DB::table('tower_review_items')->where('user_id', $user->id)->where('level_id', $level->id);
            if ($data['action'] === 'remove') {
                $query->delete();

                return;
            }
            $current = TowerLevel::whereKey($level->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->current_revision_id !== null, 404);
            $tower->assertUnlocked($user, $current);
            abort_if(! $query->exists() && DB::table('tower_review_items')->where('user_id', $user->id)->count() >= 100, 422, 'Your review list is full. Remove an item first.');
            DB::table('tower_review_items')->updateOrInsert(['user_id' => $user->id, 'level_id' => $current->id],
                ['revision_id' => $current->current_revision_id, 'created_at' => now()]);
        });

        return back()->with('status', $data['action'] === 'add' ? 'Added to your review list.' : 'Removed from your review list.');
    }
}
