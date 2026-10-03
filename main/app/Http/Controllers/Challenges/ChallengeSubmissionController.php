<?php

namespace App\Http\Controllers\Challenges;

use App\Http\Controllers\Controller;
use App\Http\Requests\Challenges\SubmitCodeRequest;
use App\Models\ChallengeSubmission;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Services\Challenges\ChallengeSubmissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ChallengeSubmissionController extends Controller
{
    public function store(SubmitCodeRequest $request, CodingChallenge $challenge, ChallengeSubmissions $submissions): RedirectResponse
    {
        $submission = $submissions->submit($request->user(), $request->session()->getId(), $challenge, $request->validated());

        return redirect()->route('challenge-attempts.show', $submission->id);
    }

    public function show(ChallengeSubmission $submission): Response
    {
        Gate::authorize('view', $submission);
        $revision = CodingChallengeRevision::findOrFail($submission->revision_id, ['id', 'title', 'number', 'language']);

        return response()->view('challenges.attempt', compact('submission', 'revision'))->header('Cache-Control', 'no-store, private');
    }

    public function status(Request $request, ChallengeSubmission $submission): JsonResponse
    {
        Gate::authorize('view', $submission);

        return response()->json(['status' => $submission->status, 'passed_cases' => $submission->passed_cases,
            'total_cases' => $submission->total_cases, 'feedback' => $submission->feedback,
            'completed' => $submission->completed_at !== null])->header('Cache-Control', 'no-store, private');
    }
}
