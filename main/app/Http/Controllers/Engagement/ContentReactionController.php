<?php

namespace App\Http\Controllers\Engagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Engagement\ReactToContentRequest;
use App\Services\Engagement\ContentFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ContentReactionController extends Controller
{
    public function store(ReactToContentRequest $request, string $kind, int $content, ContentFeedback $feedback): JsonResponse|RedirectResponse
    {
        $state = $feedback->change($request->user(), $request->session()->getId(), $kind, $content,
            (int) $request->validated('record_version'), $request->validated('reaction'));
        if ($request->expectsJson()) {
            return response()->json($state)->header('Cache-Control', 'no-store, private');
        }

        return redirect()->route(match ($kind) {
            'module' => 'modules.show', 'course' => 'course-learning.show', 'challenge' => 'challenges.show'
        }, $content)
            ->with('reaction_status', 'Your reaction is saved.');
    }
}
