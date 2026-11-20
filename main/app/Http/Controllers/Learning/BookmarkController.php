<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BookmarkController extends Controller
{
    public function update(Request $request, string $kind, int $content): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', 'in:add,remove']]);
        abort_unless(in_array($kind, ['module', 'course'], true), 404);
        DB::transaction(function () use ($request, $data, $kind, $content): void {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $request->session()->getId());
            Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
            $saved = DB::table('learning_bookmarks')->where('user_id', $user->id)->where('kind', $kind)->where('content_id', $content);
            if ($data['action'] === 'remove') {
                $saved->delete();

                return;
            }
            $model = $kind === 'module' ? LearningModule::class : LearningCourse::class;
            $model::whereKey($content)->where('status', 'Published')->whereNull('staff_withdrawn_at')
                ->whereHas('publishedRevision', fn ($revision) => $revision->where('review_status', 'Approved'))->firstOrFail();
            abort_if(! $saved->exists() && DB::table('learning_bookmarks')->where('user_id', $user->id)->count() >= 100, 422, 'Your bookmarks are full. Remove an item first.');
            DB::table('learning_bookmarks')->insertOrIgnore(['user_id' => $user->id, 'kind' => $kind, 'content_id' => $content, 'created_at' => now()]);
        });

        return back()->with('status', $data['action'] === 'add' ? 'Bookmarked. Find it in your Library.' : 'Bookmark removed.');
    }
}
