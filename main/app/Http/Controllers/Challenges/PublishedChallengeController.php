<?php

namespace App\Http\Controllers\Challenges;

use App\Http\Controllers\Controller;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PublishedChallengeController extends Controller
{
    public function catalog(Request $request): Response
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80'],
            'language' => ['nullable', Rule::in(array_keys(config('challenges.languages')))],
            'difficulty' => ['nullable', Rule::in(['Easy', 'Medium', 'Hard'])]]);
        $query = $filters['q'] ?? '';
        $challenges = CodingChallengeRevision::select(['id', 'challenge_id', 'title', 'language', 'difficulty'])
            ->where('review_status', 'Approved')->whereHas('challenge', fn ($builder) => $builder->where('status', 'Published')->whereColumn('published_revision_id', 'coding_challenge_revisions.id'))
            ->when(trim($query) !== '', fn ($builder) => $builder->where('title', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%'))
            ->when($filters['language'] ?? null, fn ($builder, $language) => $builder->where('language', $language))
            ->when($filters['difficulty'] ?? null, fn ($builder, $difficulty) => $builder->where('difficulty', $difficulty))
            ->orderByDesc('id')->paginate(12)->withQueryString();

        return response()->view('challenges.catalog', compact('challenges', 'query', 'filters'));
    }

    public function show(CodingChallenge $challenge): Response
    {
        abort_unless($challenge->status === 'Published', 404);
        $revision = CodingChallengeRevision::whereKey($challenge->published_revision_id)->where('challenge_id', $challenge->id)->where('review_status', 'Approved')
            ->firstOrFail(['id', 'title', 'description', 'language', 'difficulty', 'rules', 'input_format', 'output_format', 'cpu_time_ms', 'memory_kib']);
        // Hidden tests are excluded by SQL and never loaded into the learner view.
        $samples = $revision->testCases()->where('hidden', false)->get(['position', 'input', 'expected_output']);

        return response()->view('challenges.published', compact('revision', 'samples'))->header('Cache-Control', 'no-store, private');
    }
}
