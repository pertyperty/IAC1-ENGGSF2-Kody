<?php

namespace App\Http\Controllers;

use App\Services\Gamification\LearningProgression;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LearningController extends Controller
{
    public function home(): Response
    {
        return response()->view('welcome', ['game' => config('learning.instances.sequences')]);
    }

    public function catalog(Request $request): Response
    {
        $query = $request->validate(['q' => ['nullable', 'string', 'max:80']])['q'] ?? '';
        $modules = collect(config('learning.modules'))->filter(fn (array $module) => str_contains(mb_strtolower(implode(' ', $module)), mb_strtolower(trim($query))))->all();

        return response()->view('learning.catalog', compact('modules', 'query'));
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
