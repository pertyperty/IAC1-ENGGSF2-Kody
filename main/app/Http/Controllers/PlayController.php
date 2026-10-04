<?php

namespace App\Http\Controllers;

use App\Http\Requests\Learning\CompleteGameRequest;
use App\Http\Requests\Learning\CompleteQuizRequest;
use App\Models\WeeklyEvent;
use App\Services\Engagement\PlatformDashboard;
use App\Services\Gamification\LearningProgression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlayController extends Controller
{
    public function hub(Request $request, LearningProgression $progression, PlatformDashboard $dashboard): Response
    {
        return response()->view('learning.hub', ['progress' => $progression->snapshot($request->user()->id),
            'dashboard' => $dashboard->snapshot($request->user()),
            'weekly' => WeeklyEvent::open()->with('revision:id,title,language,difficulty')->first()])->header('Cache-Control', 'no-store, private');
    }

    public function game(CompleteGameRequest $request, string $level, LearningProgression $progression): JsonResponse
    {
        abort_unless(array_key_exists($level, config('learning.instances')), 404);
        $input = ['program' => $request->validated('program'), 'repeat' => $request->boolean('repeat'), 'conditional' => $request->boolean('conditional')];

        return response()->json($progression->record($request->user(), $request->session()->getId(), $level, 'game', $input))->header('Cache-Control', 'no-store');
    }

    public function quiz(CompleteQuizRequest $request, string $level, LearningProgression $progression): JsonResponse
    {
        abort_unless(array_key_exists($level, config('learning.quizzes')), 404);

        return response()->json($progression->record($request->user(), $request->session()->getId(), $level, 'quiz', $request->validated()))->header('Cache-Control', 'no-store');
    }
}
