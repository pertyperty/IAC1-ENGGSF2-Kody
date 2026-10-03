<?php

namespace App\Http\Controllers\Challenges;

use App\Http\Controllers\Controller;
use App\Http\Requests\Challenges\ReviewChallengeRequest;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Services\Challenges\ChallengePublishing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ChallengeReviewController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', CodingChallenge::class);
        $revisions = CodingChallengeRevision::where('review_status', 'Pending')->whereHas('challenge', fn ($query) => $query->whereIn('status', ['Draft', 'Published']))
            ->with('challenge')->orderBy('id')->paginate(15);

        return response()->view('challenges.reviews', compact('revisions'))->header('Cache-Control', 'no-store, private');
    }

    public function show(CodingChallenge $challenge): Response
    {
        Gate::authorize('review', $challenge);

        return response()->view('challenges.review', ['challenge' => $challenge, 'revision' => $challenge->latestRevision->load('testCases')])->header('Cache-Control', 'no-store, private');
    }

    public function review(ReviewChallengeRequest $request, CodingChallenge $challenge, ChallengePublishing $publishing): RedirectResponse
    {
        $publishing->review($request->user(), $request->session()->getId(), $challenge, (int) $request->validated('record_version'),
            $request->validated('decision'), $request->validated('review_notes'));

        return redirect()->route('challenge-reviews.show', $challenge)->with('status', 'Challenge review saved.');
    }
}
