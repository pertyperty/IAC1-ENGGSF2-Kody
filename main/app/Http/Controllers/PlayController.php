<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Learning\CompleteGameRequest;
use App\Http\Requests\Learning\CompleteQuizRequest;
use App\Models\LearningCourse;
use App\Models\WeeklyEvent;
use App\Services\Engagement\CreatorInsights;
use App\Services\Engagement\LearnerMissions;
use App\Services\Engagement\PlatformDashboard;
use App\Services\Gamification\Achievements;
use App\Services\Gamification\LearningProgression;
use App\Services\Gamification\TowerProgression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PlayController extends Controller
{
    public function progress(Request $request, LearningProgression $progression, Achievements $achievements): JsonResponse
    {
        Gate::authorize('viewLearning', LearningCourse::class);

        return response()->json(['progress' => $progression->snapshot($request->user()->id),
            'achievements' => $achievements->snapshot($request->user()->id),
            'tower' => app(TowerProgression::class)->counts($request->user()->id)])->header('Cache-Control', 'no-store, private');
    }

    public function hub(Request $request, LearningProgression $progression, PlatformDashboard $dashboard): Response
    {
        $days = (int) ($request->validate(['insight_days' => ['sometimes', Rule::in([7, 30, 90])]])['insight_days'] ?? 30);
        $participant = $request->user()->account_role === Role::Learner && Gate::allows('viewLearning', LearningCourse::class);
        $progress = $participant ? $progression->snapshot($request->user()->id) : null;
        $dashboard = $dashboard->snapshot($request->user());
        $missions = $participant ? app(LearnerMissions::class)->snapshot($progress, $dashboard['achievements']) : [];
        $insights = in_array($request->user()->account_role, [Role::Instructor, Role::Contributor], true)
            ? app(CreatorInsights::class)->snapshot($request->user(), $request->session()->getId(), $days) : null;

        return response()->view('learning.hub', ['progress' => $progress, 'dashboard' => $dashboard, 'missions' => $missions, 'insights' => $insights,
            'weekly' => $participant ? WeeklyEvent::open()->with('revision:id,title,language,difficulty')->first() : null])->header('Cache-Control', 'no-store, private');
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
