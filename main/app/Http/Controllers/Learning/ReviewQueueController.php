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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReviewQueueController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        Gate::authorize('viewLearning', LearningCourse::class);

        return redirect()->route('course-learning.mine', ['type' => 'bookmarks']);
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
            abort_if(! $query->exists() && DB::table('tower_review_items')->where('user_id', $user->id)->count() >= 100, 422, 'Your Tower bookmarks are full. Remove an item first.');
            DB::table('tower_review_items')->updateOrInsert(['user_id' => $user->id, 'level_id' => $current->id],
                ['revision_id' => $current->current_revision_id, 'created_at' => now()]);
        });

        return back()->with('status', $data['action'] === 'add' ? 'Bookmarked. Find it in your Library.' : 'Bookmark removed.');
    }
}
