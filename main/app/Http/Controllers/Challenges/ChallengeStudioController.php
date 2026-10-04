<?php

namespace App\Http\Controllers\Challenges;

use App\Http\Controllers\Controller;
use App\Http\Requests\Challenges\SaveChallengeRequest;
use App\Models\CodingChallenge;
use App\Services\Challenges\ChallengePublishing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChallengeStudioController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('create', CodingChallenge::class);
        $challenges = CodingChallenge::where('created_by', $request->user()->id)->with('latestRevision')->orderByDesc('id')->paginate(15);

        return response()->view('challenges.studio', compact('challenges'))->header('Cache-Control', 'no-store, private');
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', CodingChallenge::class);

        $data = $request->validate(['example' => ['nullable', 'string', Rule::in(array_keys(config('challenge-examples')))],
            'language' => ['nullable', 'string', Rule::in(array_keys(config('challenges.languages')))]]);
        $starter = empty($data['example']) ? [] : config('challenge-examples.'.$data['example'])
            + ['language' => $data['language'] ?? 'python', 'difficulty' => 'Easy', 'cpu_time_ms' => 1000, 'memory_kib' => 262144];

        return response()->view('challenges.editor', ['challenge' => null, 'revision' => null, 'starter' => $starter])->header('Cache-Control', 'no-store, private');
    }

    public function store(SaveChallengeRequest $request, ChallengePublishing $publishing): RedirectResponse
    {
        $challenge = $publishing->save($request->user(), $request->session()->getId(), $request->validated());

        return redirect()->route('challenges.edit', $challenge)->with('status', 'Challenge draft saved.');
    }

    public function edit(CodingChallenge $challenge): Response
    {
        Gate::authorize('viewOwned', $challenge);

        return response()->view('challenges.editor', ['challenge' => $challenge, 'revision' => $challenge->latestRevision->load('testCases')])->header('Cache-Control', 'no-store, private');
    }

    public function update(SaveChallengeRequest $request, CodingChallenge $challenge, ChallengePublishing $publishing): RedirectResponse
    {
        $publishing->save($request->user(), $request->session()->getId(), $request->validated(), $challenge);

        return redirect()->route('challenges.edit', $challenge)->with('status', 'New draft saved. Your approved version stays published until review.');
    }

    public function submit(Request $request, CodingChallenge $challenge, ChallengePublishing $publishing): RedirectResponse
    {
        Gate::authorize('update', $challenge);
        $data = $request->validate(['record_version' => ['required', 'integer', 'min:1']]);
        $publishing->submit($request->user(), $request->session()->getId(), $challenge, (int) $data['record_version']);

        return redirect()->route('challenges.edit', $challenge)->with('status', 'Challenge sent for review.');
    }

    public function archiveConfirmation(CodingChallenge $challenge): Response
    {
        Gate::authorize('archive', $challenge);

        return response()->view('challenges.archive', compact('challenge'))->header('Cache-Control', 'no-store, private');
    }

    public function archive(Request $request, CodingChallenge $challenge, ChallengePublishing $publishing): RedirectResponse
    {
        Gate::authorize('archive', $challenge);
        $data = $request->validate(['record_version' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']]);
        $publishing->archive($request->user(), $request->session()->getId(), $challenge, (int) $data['record_version']);

        return redirect()->route('challenges.edit', $challenge)->with('status', 'Challenge archived. Its history is preserved.');
    }
}
