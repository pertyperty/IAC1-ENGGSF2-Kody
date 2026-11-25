<?php

namespace App\Http\Controllers\Challenges;

use App\Http\Controllers\Controller;
use App\Http\Requests\Challenges\SubmitCodeRequest;
use App\Models\ChallengeSubmission;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Models\WeeklyEvent;
use App\Services\Challenges\AttemptResults;
use App\Services\Challenges\ChallengeSubmissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChallengeSubmissionController extends Controller
{
    public function store(SubmitCodeRequest $request, CodingChallenge $challenge, ChallengeSubmissions $submissions): RedirectResponse
    {
        $submission = $submissions->submit($request->user(), $request->session()->getId(), $challenge, $request->validated());

        return redirect()->route('challenge-attempts.show', $submission->id);
    }

    public function show(ChallengeSubmission $submission, AttemptResults $results): Response
    {
        Gate::authorize('view', $submission);
        $revision = CodingChallengeRevision::findOrFail($submission->revision_id, ['id', 'title', 'number', 'language']);
        $weeklyEvent = $submission->weekly_event_id === null ? null : WeeklyEvent::findOrFail($submission->weekly_event_id);

        $summary = $results->summarize($submission);
        $retryParameters = ['retry' => $submission->id];
        $retryUrl = ($weeklyEvent === null
            ? route('challenges.show', ['challenge' => $submission->challenge_id] + $retryParameters)
            : route('weekly-events.show', ['weeklyEvent' => $weeklyEvent] + $retryParameters)).'#solution';
        $participation = DB::table('challenge_participations')->where('id', $submission->participation_id)->first();
        $canRetry = $participation !== null && $participation->attempts < 3
            && ($participation->active_submission_id === null || $participation->active_submission_id === $submission->id)
            && Gate::allows('create', ChallengeSubmission::class)
            && ($weeklyEvent === null
                ? CodingChallenge::whereKey($submission->challenge_id)->where('published_revision_id', $revision->id)
                    ->where('status', 'Published')->whereNull('staff_withdrawn_at')->exists()
                : WeeklyEvent::open()->whereKey($weeklyEvent->id)->exists());

        return response()->view('challenges.attempt', compact('submission', 'revision', 'weeklyEvent', 'summary', 'canRetry', 'retryUrl'))->header('Cache-Control', 'no-store, private');
    }

    public function status(ChallengeSubmission $submission, AttemptResults $results): JsonResponse
    {
        Gate::authorize('view', $submission);

        return response()->json(['status' => $submission->status, 'passed_cases' => $submission->passed_cases,
            'total_cases' => $submission->total_cases, 'feedback' => $submission->feedback,
            'completed' => $submission->completed_at !== null] + $results->summarize($submission))->header('Cache-Control', 'no-store, private');
    }
}
