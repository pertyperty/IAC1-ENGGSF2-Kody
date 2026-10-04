<?php

namespace App\Http\Controllers;

use App\Models\ModuleRevision;
use App\Services\Gamification\LearningProgression;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class LearningController extends Controller
{
    public function home(): Response
    {
        return response()->view('welcome', ['game' => config('learning.instances.sequences')]);
    }

    public function arcade(Request $request): Response
    {
        $selected = $request->validate(['game' => ['sometimes', 'string', Rule::in(array_keys(config('arcade')))]])['game'] ?? 'pixel-studio';

        return response()->view('learning.arcade', ['selected' => $selected, 'game' => config('arcade')[$selected]]);
    }

    public function catalog(Request $request): Response
    {
        $query = $request->validate(['q' => ['nullable', 'string', 'max:80']])['q'] ?? '';
        $modules = collect(config('learning.modules'))->filter(fn (array $module) => str_contains(mb_strtolower(implode(' ', $module)), mb_strtolower(trim($query))))->all();

        $published = ModuleRevision::where('review_status', 'Approved')->whereHas('module', fn ($builder) => $builder->where('status', 'Published')->whereNull('staff_withdrawn_at')->whereColumn('published_revision_id', 'module_revisions.id'))
            ->when(trim($query) !== '', fn ($builder) => $builder->where(fn ($search) => $search->where('title', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')->orWhere('description', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')))
            ->orderByDesc('id')->paginate(12)->withQueryString();

        return response()->view('learning.catalog', compact('modules', 'query', 'published'));
    }

    public function show(Request $request, string $module, LearningProgression $progression): Response
    {
        $lesson = config('learning.modules')[$module] ?? null;
        abort_if($lesson === null, 404);
        abort_unless($progression->snapshot($request->user()->id)['levels'][$module]['unlocked'], 403, 'Clear the previous level in Play first.');
        $game = config('learning.instances')[$module];
        $quiz = config('learning.quizzes')[$module];

        return response()->view('learning.module', compact('lesson', 'game', 'quiz', 'module'))->header('Cache-Control', 'no-store, private');
    }
}
