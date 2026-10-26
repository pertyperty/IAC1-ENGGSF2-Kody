<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrowseModulesRequest;
use App\Models\ModuleRevision;
use App\Services\Content\LearningHistory;
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
        $cabinets = config('arcade') + config('practice-quizzes');
        $selected = $request->validate(['game' => ['sometimes', 'string', Rule::in(array_keys($cabinets))]])['game'] ?? 'pixel-studio';

        return response()->view('learning.arcade', ['selected' => $selected, 'game' => $cabinets[$selected], 'cabinets' => $cabinets]);
    }

    public function catalog(BrowseModulesRequest $request): Response
    {
        $query = $request->validated('q') ?? '';
        $template = $request->validated('template') ?? '';
        $templates = $request->templates();
        $modules = collect(config('learning.modules'))
            ->filter(fn (array $module) => str_contains(mb_strtolower(implode(' ', $module)), mb_strtolower(trim($query))))
            ->filter(fn (array $module, string $slug) => $template === '' || config('learning.instances.'.$slug.'.template') === $template || config('learning.quizzes.'.$slug.'.template') === $template)->all();

        $published = ModuleRevision::with(['module.creator:id,username,account_role,account_status,email_verified_at', 'module.creator.creatorVerification'])->where('review_status', 'Approved')->whereHas('module', fn ($builder) => $builder->where('status', 'Published')->whereNull('staff_withdrawn_at')->whereColumn('published_revision_id', 'module_revisions.id'))
            ->when(trim($query) !== '', fn ($builder) => $builder->where(fn ($search) => $search->where('title', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')->orWhere('description', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%')))
            ->when($template !== '', fn ($builder) => $builder->where('assessment->template', $template))
            ->orderByDesc('id')->paginate(12)->withQueryString();

        $history = app(LearningHistory::class);
        $participant = $history->participant($request);
        $moduleProgress = $participant === null ? collect() : $history->modules($participant, $published->getCollection());
        $starterProgress = $participant === null ? null : app(LearningProgression::class)->snapshot($participant->id);

        return response()->view('learning.catalog', compact('modules', 'query', 'published', 'template', 'templates', 'moduleProgress', 'starterProgress'))->header('Cache-Control', 'no-store, private');
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
