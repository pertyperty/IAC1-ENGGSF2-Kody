<?php

namespace App\Http\Controllers\Challenges;

use App\Http\Controllers\Controller;
use App\Models\ChallengeSubmission;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Services\Challenges\Judge0\ProviderReadiness;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
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
            ->where('review_status', 'Approved')->whereHas('challenge', fn ($builder) => $builder->where('status', 'Published')->whereNull('staff_withdrawn_at')->whereColumn('published_revision_id', 'coding_challenge_revisions.id'))
            ->when(trim($query) !== '', fn ($builder) => $builder->where('title', 'ilike', '%'.addcslashes(trim($query), '%_\\').'%'))
            ->when($filters['language'] ?? null, fn ($builder, $language) => $builder->where('language', $language))
            ->when($filters['difficulty'] ?? null, fn ($builder, $difficulty) => $builder->where('difficulty', $difficulty))
            ->orderByDesc('id')->paginate(12)->withQueryString();

        return response()->view('challenges.catalog', compact('challenges', 'query', 'filters'));
    }

    public function show(Request $request, CodingChallenge $challenge): Response
    {
        abort_unless($challenge->status === 'Published' && ! $challenge->isWithdrawn(), 404);
        $revision = CodingChallengeRevision::whereKey($challenge->published_revision_id)->where('challenge_id', $challenge->id)->where('review_status', 'Approved')
            ->firstOrFail(['id', 'title', 'description', 'language', 'difficulty', 'rules', 'input_format', 'output_format', 'cpu_time_ms', 'memory_kib']);
        // Hidden tests are excluded by SQL and never loaded into the learner view.
        $samples = $revision->testCases()->where('hidden', false)->get(['position', 'input', 'expected_output']);
        $participation = DB::table('challenge_participations')->where('user_id', $request->user()->id)->where('challenge_id', $challenge->id)->whereNull('weekly_event_id')->first();
        $attempts = $participation === null ? collect() : ChallengeSubmission::where('participation_id', $participation->id)->orderByDesc('attempt')->get(['id', 'attempt', 'status']);
        $ready = Gate::allows('create', ChallengeSubmission::class) && app(ProviderReadiness::class)->profile($revision) !== null;
        $confirmationId = (string) Str::uuid();

        return response()->view('challenges.published', compact('revision', 'samples', 'challenge', 'participation', 'attempts', 'ready', 'confirmationId'))->header('Cache-Control', 'no-store, private');
    }
}
