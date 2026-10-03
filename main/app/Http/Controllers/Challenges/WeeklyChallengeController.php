<?php

namespace App\Http\Controllers\Challenges;

use App\Http\Controllers\Controller;
use App\Http\Requests\Challenges\SubmitCodeRequest;
use App\Models\ChallengeSubmission;
use App\Models\CodingChallenge;
use App\Models\WeeklyEvent;
use App\Services\Challenges\ChallengeSubmissions;
use App\Services\Challenges\Judge0\ProviderReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class WeeklyChallengeController extends Controller
{
    public function index(Request $request): Response
    {
        $current = WeeklyEvent::open()->with('revision:id,title,language,difficulty')->first();
        $attempts = DB::table('challenge_submissions as submissions')
            ->join('challenge_participations as participation', 'participation.id', '=', 'submissions.participation_id')
            ->join('weekly_events as events', 'events.id', '=', 'submissions.weekly_event_id')
            ->join('coding_challenge_revisions as revisions', 'revisions.id', '=', 'submissions.revision_id')
            ->where('participation.user_id', $request->user()->id)->orderByDesc('submissions.submitted_at')
            ->paginate(12, ['submissions.id', 'submissions.attempt', 'submissions.status', 'events.starts_at', 'revisions.title']);

        return response()->view('weekly.index', compact('current', 'attempts'))->header('Cache-Control', 'no-store, private');
    }

    public function show(Request $request, WeeklyEvent $weeklyEvent): Response
    {
        abort_unless(WeeklyEvent::open()->whereKey($weeklyEvent->id)->exists(), 404);
        $revision = $weeklyEvent->revision()->where('review_status', 'Approved')->firstOrFail(['id', 'title', 'description', 'language', 'difficulty', 'rules', 'input_format', 'output_format', 'cpu_time_ms', 'memory_kib']);
        $samples = $revision->testCases()->where('hidden', false)->get(['position', 'input', 'expected_output']);
        $participation = DB::table('challenge_participations')->where('user_id', $request->user()->id)->where('weekly_event_id', $weeklyEvent->id)->first();
        $attempts = $participation === null ? collect() : ChallengeSubmission::where('participation_id', $participation->id)->orderByDesc('attempt')->get(['id', 'attempt', 'status']);
        $ready = Gate::allows('create', ChallengeSubmission::class) && app(ProviderReadiness::class)->profile($revision) !== null;
        $confirmationId = (string) Str::uuid();
        $attemptAction = route('weekly-events.attempt', $weeklyEvent);
        $attemptScope = 'this weekly event. Your standard quest attempts are separate';

        return response()->view('weekly.play', compact('weeklyEvent', 'revision', 'samples', 'participation', 'attempts', 'ready', 'confirmationId', 'attemptAction', 'attemptScope'))->header('Cache-Control', 'no-store, private');
    }

    public function store(SubmitCodeRequest $request, WeeklyEvent $weeklyEvent, ChallengeSubmissions $submissions): RedirectResponse
    {
        $submission = $submissions->submit($request->user(), $request->session()->getId(), CodingChallenge::findOrFail($weeklyEvent->challenge_id), $request->validated(), $weeklyEvent);

        return redirect()->route('challenge-attempts.show', $submission->id);
    }
}
