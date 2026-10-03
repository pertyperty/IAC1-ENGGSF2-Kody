<?php

namespace App\Http\Controllers;

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

    public function show(string $module): Response
    {
        $lesson = config('learning.modules')[$module] ?? null;
        abort_if($lesson === null, 404);
        $game = config('learning.instances')[$module];
        $quiz = config('learning.quizzes')[$module];

        return response()->view('learning.module', compact('lesson', 'game', 'quiz'))->header('Cache-Control', 'no-store, private');
    }
}
