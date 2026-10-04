<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\CompleteGameRequest;
use App\Http\Requests\Learning\CompleteQuizRequest;
use App\Models\LearningModule;
use App\Services\Engagement\ContentFeedback;
use App\Services\Gamification\LearningProgression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublishedModuleController extends Controller
{
    public function show(Request $request, LearningModule $module, ContentFeedback $feedback): Response
    {
        abort_unless($module->status === 'Published' && ! $module->isWithdrawn() && $module->publishedRevision?->review_status === 'Approved', 404);

        return response()->view('content.published', ['module' => $module, 'revision' => $module->publishedRevision,
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
}
