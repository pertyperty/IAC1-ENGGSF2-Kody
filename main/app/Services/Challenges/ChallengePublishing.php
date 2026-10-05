<?php

namespace App\Services\Challenges;

use App\Models\ChallengeTestCase;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use App\Services\Notifications\InAppNotifications;
use App\Services\Publishing\AccessSettings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChallengePublishing
{
    public function save(User $actor, string $sessionId, array $data, ?CodingChallenge $challenge = null): CodingChallenge
    {
        return $this->transaction(function () use ($actor, $sessionId, $data, $challenge): CodingChallenge {
            $user = $this->account($actor, $sessionId);
            if ($challenge === null) {
                Gate::forUser($user)->authorize('create', CodingChallenge::class);
                $current = CodingChallenge::create(['created_by' => $user->id]);
                $number = 1;
            } else {
                $current = CodingChallenge::whereKey($challenge->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($user)->authorize('update', $current);
                $this->version($current, (int) $data['record_version']);
                $latest = $current->latestRevision;
                if ($latest->review_status === 'Pending') {
                    throw ValidationException::withMessages(['challenge' => 'This revision is awaiting review. Save changes after the review finishes.']);
                }
                $number = $latest->number + 1;
                $current->increment('record_version');
            }
            if (count($data['test_cases']) < 1 || count($data['test_cases']) > config('challenges.authoring.max_test_cases')) {
                throw ValidationException::withMessages(['test_cases' => 'Supply at least one test case within the authoring limit.']);
            }
            $revision = CodingChallengeRevision::create(['challenge_id' => $current->id, 'number' => $number,
                'title' => $data['title'], 'description' => $data['description'], 'language' => $data['language'],
                'difficulty' => $data['difficulty'], 'rules' => $data['rules'], 'input_format' => $data['input_format'],
                'output_format' => $data['output_format'], 'cpu_time_ms' => $data['cpu_time_ms'], 'memory_kib' => $data['memory_kib']] + app(AccessSettings::class)->attributes($user, 'challenge', $data, $current->id) + app(ChallengeMetadata::class)->attributes($data));
            foreach ($data['test_cases'] as $index => $case) {
                ChallengeTestCase::create(['revision_id' => $revision->id, 'position' => $index + 1,
                    'input' => $case['input'] ?? '', 'expected_output' => $case['expected_output'] ?? '', 'hidden' => (bool) $case['hidden']]);
            }
            $this->audit($user, $current, 'challenge.saved', ['revision' => $number]);

            return $current;
        });
    }

    public function submit(User $actor, string $sessionId, CodingChallenge $challenge, int $version): void
    {
        $this->transaction(function () use ($actor, $sessionId, $challenge, $version): void {
            $user = $this->account($actor, $sessionId);
            $current = CodingChallenge::whereKey($challenge->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('update', $current);
            $this->version($current, $version);
            $revision = $current->latestRevision;
            if ($revision->review_status !== 'Draft' || ! $revision->testCases()->exists()) {
                throw ValidationException::withMessages(['challenge' => 'Save a complete draft before submitting for review.']);
            }
            $revision->update(['review_status' => 'Pending']);
            $current->increment('record_version');
            $this->audit($user, $current, 'challenge.submitted', ['revision' => $revision->number]);
        });
    }

    public function review(User $actor, string $sessionId, CodingChallenge $challenge, int $version, string $decision, ?string $notes): void
    {
        $this->transaction(function () use ($actor, $sessionId, $challenge, $version, $decision, $notes): void {
            $users = User::whereIn('id', [$actor->id, $challenge->created_by])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $user = $users->get($actor->id);
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $current = CodingChallenge::whereKey($challenge->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('review', $current);
            $this->version($current, $version);
            $revision = $current->latestRevision;
            if ($revision->review_status !== 'Pending' || ! in_array($current->status, ['Draft', 'Published'], true)) {
                throw ValidationException::withMessages(['challenge' => 'Only the current pending revision can be reviewed.']);
            }
            if (! in_array($decision, ['Approved', 'Rejected'], true) || ($decision === 'Rejected' && trim($notes ?? '') === '')) {
                throw ValidationException::withMessages(['decision' => 'Choose a valid decision and give a reason for rejection.']);
            }
            if ($decision === 'Approved') {
                Gate::forUser($users->get($current->created_by))->authorize('create', CodingChallenge::class);
                app(AccessSettings::class)->attributes($users->get($current->created_by), 'challenge',
                    $revision->only(['price_kb', 'minimum_xp', 'prerequisite_modules']), $current->id);
                if (! $revision->testCases()->exists()) {
                    throw ValidationException::withMessages(['challenge' => 'This challenge has no test cases.']);
                }
                $current->update(['published_revision_id' => $revision->id, 'status' => 'Published']);
            }
            $revision->update(['review_status' => $decision, 'reviewed_by' => $user->id, 'reviewed_at' => now(), 'review_notes' => $notes]);
            $current->increment('record_version');
            $this->audit($user, $current, 'challenge.reviewed', ['revision' => $revision->number, 'decision' => $decision]);
            app(InAppNotifications::class)->challengeReviewed($users->get($current->created_by), $revision);
        });
    }

    public function archive(User $actor, string $sessionId, CodingChallenge $challenge, int $version): void
    {
        $this->transaction(function () use ($actor, $sessionId, $challenge, $version): void {
            $user = $this->account($actor, $sessionId);
            $current = CodingChallenge::whereKey($challenge->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('archive', $current);
            $this->version($current, $version);
            $current->update(['status' => 'Archived', 'record_version' => $current->record_version + 1]);
            $this->audit($user, $current, 'challenge.archived', ['revision' => $current->publishedRevision->number]);
        });
    }

    private function transaction(callable $operation): mixed
    {
        try {
            return DB::transaction($operation);
        } catch (QueryException $exception) {
            // QueryException embeds SQL bindings; never report hidden test payloads.
            Log::error('Challenge storage write failed.', ['sqlstate' => $exception->getCode()]);
            throw new \RuntimeException('Challenge storage is temporarily unavailable.');
        }
    }

    private function account(User $actor, string $sessionId): User
    {
        $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
        app(CurrentAccountSession::class)->assert($user, $sessionId);

        return $user;
    }

    private function version(CodingChallenge $challenge, int $version): void
    {
        if ($challenge->record_version !== $version) {
            throw ValidationException::withMessages(['record_version' => 'This challenge changed. Reload before saving or reviewing.']);
        }
    }

    private function audit(User $actor, CodingChallenge $challenge, string $event, array $context): void
    {
        app(AuditRecorder::class)->record($actor->id, $challenge->created_by, $event, 'coding_challenge', (string) $challenge->id,
            $context + ['version' => $challenge->record_version]);
    }
}
