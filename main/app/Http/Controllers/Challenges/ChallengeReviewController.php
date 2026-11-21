<?php

namespace App\Http\Controllers\Challenges;

use App\Http\Controllers\Controller;
use App\Http\Requests\Challenges\ReviewChallengeRequest;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Services\Challenges\ChallengePublishing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ChallengeReviewController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CodingChallenge::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80', 'not_regex:/\x00/']]);
        $revisions = CodingChallengeRevision::where('review_status', 'Pending')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('title', 'ilike', '%'.addcslashes($search, '%_\\').'%'))
            ->whereNotExists(fn ($newer) => $newer->selectRaw('1')->from('coding_challenge_revisions as newer')->whereColumn('newer.challenge_id', 'coding_challenge_revisions.challenge_id')->whereColumn('newer.id', '>', 'coding_challenge_revisions.id'))->whereHas('challenge', fn ($query) => $query->whereIn('status', ['Draft', 'Published'])->where('created_by', '!=', $request->user()->id))
            ->with('challenge')->orderBy('id')->paginate(15)->withQueryString();

        return response()->view('challenges.reviews', compact('revisions', 'filters'))->header('Cache-Control', 'no-store, private');
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
