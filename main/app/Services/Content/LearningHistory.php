<?php

namespace App\Services\Content;

use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LearningHistory
{
    public function participant(Request $request): ?User
    {
        $user = $request->user();
        if ($user === null || ! Gate::forUser($user)->allows('viewLearning', LearningCourse::class)) {
            return null;
        }
        app(CurrentAccountSession::class)->assert($user, $request->session()->getId());

        return $user;
    }

    /** Combine standalone wins with course completion, retaining the exact completed edition. */
    public function modules(User $user, Collection $revisions): Collection
    {
        if ($revisions->isEmpty()) {
            return collect();
        }
        $ids = $revisions->pluck('module_id')->all();
        $course = DB::table('course_module_progress as p')->join('course_enrollments as e', 'e.id', '=', 'p.enrollment_id')
            ->join('course_revision_modules as a', 'a.id', '=', 'p.assignment_id')->where('e.user_id', $user->id)->whereIn('a.module_id', $ids)
            ->selectRaw("a.module_id, a.module_revision_id AS revision_id, MIN(p.completed_at) AS completed_at, MAX(p.last_accessed_at) AS last_activity, BOOL_OR(p.completed_at IS NOT NULL AND p.validated_input->>'kind' = 'reading') AS reading")
            ->groupBy('a.module_id', 'a.module_revision_id')->get();
        $wins = DB::table('learning_activity_days')->where('user_id', $user->id)->whereIn('level', array_map(fn ($id) => 'module-'.$id, $ids))
            ->selectRaw("SUBSTRING(level FROM 8)::bigint AS module_id, validated_input->>'revision_id' AS revision_id, MIN(completed_at) AS completed_at, MAX(completed_at) AS last_activity, false AS reading")
            ->groupBy('level')->groupByRaw("validated_input->>'revision_id'")->get();
        $opened = DB::table('content_accesses')->where('user_id', $user->id)->where('kind', 'module')->whereIn('module_id', $ids)->get()->keyBy('module_id');
        $read = DB::table('module_read_completions')->where('user_id', $user->id)->whereIn('module_id', $ids)
            ->selectRaw('module_id, revision_id, completed_at, completed_at AS last_activity, true AS reading, true AS standalone')->get();
        $records = $course->concat($wins)->concat($read)->groupBy('module_id');

        return $revisions->mapWithKeys(function ($revision) use ($records, $opened): array {
            $history = $records->get($revision->module_id, collect());
            $completed = $history->filter(fn ($row) => $row->completed_at !== null);
            $current = $completed->first(fn ($row) => (int) $row->revision_id === $revision->id);
            $state = $completed->isNotEmpty() ? 'complete' : ($history->isNotEmpty() || $opened->has($revision->module_id) ? 'started' : 'new');
            $label = $state === 'complete' ? ($current === null ? 'Completed · earlier edition' : ($current->reading ? (($current->standalone ?? false) ? 'Completed · article read' : 'Completed · read in course') : 'Completed · assessment cleared')) : ($state === 'started' ? 'In progress' : 'Not started');

            return [$revision->module_id => ['state' => $state, 'label' => $label, 'completed_at' => $completed->min('completed_at'),
                'last_activity' => $history->max('last_activity') ?? $opened->get($revision->module_id)?->first_opened_at]];
        });
    }

    public function recentModules(User $user): Collection
    {
        $visits = DB::table('course_module_progress as p')->join('course_enrollments as e', 'e.id', '=', 'p.enrollment_id')
            ->join('course_revision_modules as a', 'a.id', '=', 'p.assignment_id')->where('e.user_id', $user->id)
            ->selectRaw('a.module_id, MAX(p.last_accessed_at) AS activity')->groupBy('a.module_id')->orderByDesc('activity')->limit(12)->get();
        $opened = DB::table('content_accesses')->where('user_id', $user->id)->where('kind', 'module')->orderByDesc('first_opened_at')->limit(12)
            ->get(['module_id', 'first_opened_at as activity']);
        $ids = $visits->concat($opened)->sortByDesc('activity')->unique('module_id')->take(12)->pluck('module_id');
        $modules = LearningModule::whereIn('id', $ids)->with('publishedRevision')->get()->keyBy('id');

        return $ids->map(fn ($id) => $modules->get($id))->filter()->values();
    }
}
