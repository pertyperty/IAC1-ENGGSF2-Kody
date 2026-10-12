<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\CompleteGameRequest;
use App\Http\Requests\Learning\CompleteQuizRequest;
use App\Models\LearningModule;
use App\Services\Content\ModuleReading;
use App\Services\Engagement\ContentFeedback;
use App\Services\Gamification\LearningProgression;
use App\Services\Transactions\ContentAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PublishedModuleController extends Controller
{
    public function show(Request $request, LearningModule $module, ContentFeedback $feedback): Response
    {
        $access = app(ContentAccess::class)->open($request->user(), $request->session()->getId(), 'module', $module->id);
        if (! $access['accessible']) {
            return response()->view('transactions.access-preview', $access + ['kind' => 'module'])->header('Cache-Control', 'no-store, private');
        }

        return response()->view('content.published', ['module' => $access['participant'] ? $access['target'] : null, 'revision' => $access['revision'], 'participant' => $access['participant'],
            'articleRead' => DB::table('module_read_completions')->where('user_id', $request->user()->id)->where('module_id', $module->id)->exists(),
            'feedback' => $feedback->read($request->user(), $request->session()->getId(), 'module', $module->id, true)])->header('Cache-Control', 'no-store, private');
    }

    public function game(CompleteGameRequest $request, LearningModule $module, int $revision, LearningProgression $progression): JsonResponse
    {
        $input = ['program' => $request->validated('program'), 'repeat' => $request->boolean('repeat'), 'conditional' => $request->boolean('conditional')];

        return response()->json($progression->recordModule($request->user(), $request->session()->getId(), $module->id, $revision, 'game', $input))->header('Cache-Control', 'no-store');
    }

    public function quiz(CompleteQuizRequest $request, LearningModule $module, int $revision, LearningProgression $progression): JsonResponse
    {
        return response()->json($progression->recordModule($request->user(), $request->session()->getId(), $module->id, $revision, 'quiz', $request->validated()))->header('Cache-Control', 'no-store');
    }

    public function read(Request $request, LearningModule $module, int $revision, ModuleReading $reading): RedirectResponse
    {
        $request->validate(['confirmed' => ['required', 'accepted']]);
        $reading->standalone($request->user(), $request->session()->getId(), $module->id, $revision);

        return back()->with('status', 'Article completed. First module completion earns 40 XP; reading does not advance streaks or assessment eligibility.');
    }
}
