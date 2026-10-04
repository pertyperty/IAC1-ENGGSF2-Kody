<?php

namespace App\Services\Challenges;

use App\Models\ChallengeCaseEvaluation;
use App\Models\ChallengeSubmission;
use App\Models\ChallengeTestCase;
use App\Models\CodingChallengeRevision;
use App\Services\Challenges\Judge0\Judge0Client;
use App\Services\Challenges\Judge0\ProviderUnavailable;
use App\Services\Gamification\Achievements;
use App\Services\Gamification\WeeklyResults;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmissionEvaluation
{
    // One bounded provider operation per job invocation; returns a polling delay.
    public function advance(string $id): ?int
    {
        $claim = app(SubmissionStorage::class)->transaction(function () use ($id): ?array {
            $candidate = ChallengeSubmission::find($id);
            if ($candidate === null) {
                return null;
            }
            DB::table('challenge_participations')->where('id', $candidate->participation_id)->lockForUpdate()->firstOrFail();
            $submission = ChallengeSubmission::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($submission->completed_at !== null) {
                return null;
            }
            if ($submission->lease_expires_at?->isFuture()) {
                return ['busy' => true];
            }
            $case = ChallengeCaseEvaluation::where('submission_id', $id)->whereNotIn('status', ['Passed', 'Failed', 'Unavailable'])->orderBy('test_case_id')->first();
            $profile = DB::table('judge0_profiles')->find($submission->provider_profile_id);
            if ($submission->submitted_at->addSeconds(config('judge0.evaluation_seconds'))->isPast()
                || $profile === null || ! hash_equals($profile->fingerprint, app(Judge0Client::class)->fingerprint())
                || $case?->status === 'Creating') {
                // A POST may have succeeded before a crash. Never create it again.
                $this->finish($submission, 'Unavailable');

                return null;
            }
            if ($case === null) {
                $this->finish($submission);

                return null;
            }
            $lease = (string) Str::uuid();
            $submission->update(['status' => 'Evaluating', 'lease_id' => $lease, 'lease_expires_at' => now()->addSeconds(60)]);
            $create = $case->status === 'Queued';
            if ($create) {
                $case->update(['status' => 'Creating']);
            }

            return ['submission' => $submission, 'case' => $case, 'lease' => $lease, 'create' => $create,
                'language_id' => json_decode($profile->languages, true)[$submission->language]['id']];
        });
        if ($claim === null) {
            return null;
        }
        if (isset($claim['busy'])) {
            return 10;
        }
        $token = null;
        $result = null;
        $unavailable = false;
        try {
            $client = app(Judge0Client::class);
            if ($claim['create']) {
                $test = ChallengeTestCase::findOrFail($claim['case']->test_case_id);
                $revision = CodingChallengeRevision::findOrFail($claim['submission']->revision_id);
                $token = $client->create($claim['submission']->source_code, $claim['language_id'], $test->input, $test->expected_output, $revision->cpu_time_ms, $revision->memory_kib);
            } else {
                $result = $client->result($claim['case']->provider_token);
            }
        } catch (ProviderUnavailable) {
            // GET is safe to retry; an uncertain POST must never be retried.
            $unavailable = $claim['create'];
        }

        return app(SubmissionStorage::class)->transaction(function () use ($id, $claim, $token, $result, $unavailable): ?int {
            DB::table('challenge_participations')->where('id', $claim['submission']->participation_id)->lockForUpdate()->firstOrFail();
            $submission = ChallengeSubmission::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($submission->completed_at !== null || $submission->lease_id !== $claim['lease']) {
                return $submission->completed_at !== null ? null : 10;
            }
            $case = ChallengeCaseEvaluation::findOrFail($claim['case']->id);
            if ($unavailable || ($result !== null && $result->outcome() === 'Unavailable' && ! $result->pending())) {
                $this->finish($submission, 'Unavailable');

                return null;
            }
            if ($token !== null) {
                $case->update(['provider_token' => $token, 'status' => 'Submitted']);
            } elseif ($result !== null) {
                $case->update(['provider_status' => $result->status, 'status' => $result->pending() ? 'Submitted' : $result->outcome()]);
            }
            $submission->update(['lease_id' => null, 'lease_expires_at' => null]);
            if (! ChallengeCaseEvaluation::where('submission_id', $id)->whereNotIn('status', ['Passed', 'Failed'])->exists()) {
                $this->finish($submission);

                return null;
            }

            return $token !== null || ($result !== null && ! $result->pending()) ? 0 : ($result !== null ? 1 : 5);
        });
    }

    private function finish(ChallengeSubmission $submission, ?string $status = null): void
    {
        $passed = ChallengeCaseEvaluation::where('submission_id', $submission->id)->where('status', 'Passed')->count();
        $status ??= $passed === $submission->total_cases ? 'Passed' : 'Failed';
        if ($status === 'Unavailable') {
            ChallengeCaseEvaluation::where('submission_id', $submission->id)->whereNotIn('status', ['Passed', 'Failed'])->update(['status' => 'Unavailable']);
        }
        $submission->update(['status' => $status, 'passed_cases' => $passed, 'completed_at' => now(), 'lease_id' => null, 'lease_expires_at' => null,
            'feedback' => match ($status) {
                'Passed' => 'All test cases passed. Well done!',
                'Failed' => 'Some test cases did not pass. Check the formats, edge cases and resource limits.',
                default => 'Evaluation could not be completed. This is a service issue, not a failed solution.'
            }]);
        DB::table('challenge_participations')->where('id', $submission->participation_id)->where('active_submission_id', $submission->id)
            ->update(['active_submission_id' => null, 'updated_at' => now()]);
        app(Achievements::class)->challenge($submission);
        app(WeeklyResults::class)->capture($submission);
    }
}
