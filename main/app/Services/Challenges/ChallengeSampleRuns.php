<?php

namespace App\Services\Challenges;

use App\Models\ChallengeSubmission;
use App\Models\ChallengeTestCase;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Transactions\ContentAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ChallengeSampleRuns
{
    public function available(): bool
    {
        return config('judge0.enabled') === true && app()->bound(SampleCodeRunner::class);
    }

    /** @return list<array{position: int, passed: bool}> */
    public function run(User $actor, string $sessionId, CodingChallenge $challenge, int $revisionId, string $language, string $source): array
    {
        abort_unless($this->available(), 503, 'Sample runs are not available.');
        if (! ChallengeCode::valid($source) || trim($source) === '') {
            throw ValidationException::withMessages(['source_code' => 'Supply UTF-8 code without NUL bytes within the 64 KiB source limit.']);
        }
        [$revision, $samples] = DB::transaction(function () use ($actor, $sessionId, $challenge, $revisionId, $language): array {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            Gate::forUser($user)->authorize('create', ChallengeSubmission::class);
            $current = CodingChallenge::whereKey($challenge->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->status === 'Published' && ! $current->isWithdrawn(), 404);
            abort_unless(app(ContentAccess::class)->has($user->id, 'challenge', $current->id), 403, 'Open or unlock this challenge first.');
            abort_unless($current->published_revision_id === $revisionId, 409, 'This coding challenge changed. Reload before running samples.');

            $revision = CodingChallengeRevision::whereKey($revisionId)->where('challenge_id', $current->id)
                ->where('review_status', 'Approved')->firstOrFail();
            if ($revision->language !== $language || ! array_key_exists($language, config('challenges.languages'))) {
                throw ValidationException::withMessages(['language' => 'Use the published challenge language.']);
            }
            $samples = $revision->testCases()->where('hidden', false)->limit(3)->get(['position', 'input', 'expected_output'])
                ->map(fn (ChallengeTestCase $case) => ['position' => $case->position, 'input' => $case->input, 'expected_output' => $case->expected_output])->values()->all();
            if ($samples === []) {
                throw ValidationException::withMessages(['challenge' => 'This coding challenge has no public samples.']);
            }

            return [$revision, $samples];
        });

        // No provider call takes place inside the authorization transaction.
        $verdicts = app(SampleCodeRunner::class)->run($source, $language, $revision->cpu_time_ms, $revision->memory_kib, $samples);
        abort_unless($this->validVerdicts($verdicts, count($samples)), 503, 'Sample results are not available.');

        return array_map(fn (array $sample, bool $passed) => ['position' => $sample['position'], 'passed' => $passed], $samples, $verdicts);
    }

    private function validVerdicts(array $verdicts, int $sampleCount): bool
    {
        return array_is_list($verdicts) && count($verdicts) === $sampleCount
            && array_all($verdicts, fn (mixed $verdict) => is_bool($verdict));
    }
}
