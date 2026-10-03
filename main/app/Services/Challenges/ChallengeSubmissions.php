<?php

namespace App\Services\Challenges;

use App\Jobs\Challenges\EvaluateSubmission;
use App\Models\ChallengeCaseEvaluation;
use App\Models\ChallengeSubmission;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Models\User;
use App\Models\WeeklyEvent;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use App\Services\Challenges\Judge0\ProviderReadiness;
use App\Services\Gamification\WeeklyEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChallengeSubmissions
{
    public function submit(User $actor, string $sessionId, CodingChallenge $challenge, array $data, ?WeeklyEvent $weeklyEvent = null): ChallengeSubmission
    {
        return app(SubmissionStorage::class)->transaction(function () use ($actor, $sessionId, $challenge, $data, $weeklyEvent): ChallengeSubmission {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            Gate::forUser($user)->authorize('create', ChallengeSubmission::class);
            if ($weeklyEvent !== null) {
                app(WeeklyEvents::class)->lockCalendar();
            }
            $current = CodingChallenge::whereKey($challenge->id)->lockForUpdate()->firstOrFail();
            $participation = DB::table('challenge_participations')->where('user_id', $user->id)->where('challenge_id', $current->id)
                ->where('context_key', $weeklyEvent?->id ?? 0)->lockForUpdate()->first();
            $digest = hash('sha256', json_encode([(int) $data['revision_id'], $data['language'], $data['source_code']], JSON_THROW_ON_ERROR));
            if ($participation !== null) {
                $existing = ChallengeSubmission::where('participation_id', $participation->id)->where('confirmation_id', $data['confirmation_id'])->first();
                if ($existing !== null) {
                    if (! hash_equals($existing->request_digest, $digest)) {
                        throw ValidationException::withMessages(['confirmation_id' => 'This confirmation already belongs to different code. Reload before submitting.']);
                    }

                    return $existing;
                }
            }
            $event = $weeklyEvent === null ? null : app(WeeklyEvents::class)->admit($weeklyEvent->id, $current);
            $revisionId = $event?->revision_id ?? $current->published_revision_id;
            if ($current->status !== 'Published' || $current->isWithdrawn() || $revisionId !== (int) $data['revision_id']) {
                throw ValidationException::withMessages(['revision_id' => 'This quest changed or is unavailable. Reload before submitting.']);
            }
            $revision = CodingChallengeRevision::whereKey($revisionId)->where('challenge_id', $current->id)->where('review_status', 'Approved')->firstOrFail();
            if ($data['language'] !== $revision->language || ! array_key_exists($data['language'], config('challenges.languages'))
                || strlen($data['source_code']) > config('judge0.source_bytes') || trim($data['source_code']) === '' || str_contains($data['source_code'], "\0")
                || ! Str::isUuid($data['confirmation_id']) || ! in_array($data['confirmed'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)
                || isset($data['weekly_event_id'])) {
                throw ValidationException::withMessages(['source_code' => 'Confirm valid code in the quest language within the source-size limit.']);
            }
            if ($participation !== null && ($participation->active_submission_id !== null || $participation->attempts >= 3)) {
                throw ValidationException::withMessages(['challenge' => $participation->active_submission_id !== null
                    ? 'Wait for your active attempt to finish before submitting again.' : ($event === null ? 'All three attempts for this quest have been used.' : 'All three attempts for this weekly event have been used.')]);
            }
            $profile = app(ProviderReadiness::class)->profile($revision);
            if ($profile === null) {
                throw ValidationException::withMessages(['challenge' => 'Code evaluation is not available yet. No attempt was used.']);
            }
            if (config('queue.connections.database.connection') !== null && config('queue.connections.database.connection') !== config('database.default')) {
                throw ValidationException::withMessages(['challenge' => 'Code evaluation is not available yet. No attempt was used.']);
            }
            $cases = $revision->testCases()->get(['id', 'revision_id']);
            if ($cases->isEmpty()) {
                throw ValidationException::withMessages(['challenge' => 'This quest is unavailable. No attempt was used.']);
            }
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $participationId = $participation?->id ?? DB::table('challenge_participations')->insertGetId([
                'user_id' => $user->id, 'challenge_id' => $current->id, 'weekly_event_id' => $event?->id,
                'attempts' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $submission = new ChallengeSubmission;
            $submission->id = (string) Str::uuid();
            $submission->fill(['participation_id' => $participationId, 'challenge_id' => $current->id, 'revision_id' => $revision->id,
                'provider_profile_id' => $profile->id, 'attempt' => ($participation?->attempts ?? 0) + 1,
                'confirmation_id' => $data['confirmation_id'], 'request_digest' => $digest, 'source_code' => $data['source_code'],
                'language' => $revision->language, 'weekly_event_id' => $event?->id, 'total_cases' => $cases->count(), 'submitted_at' => now()]);
            $submission->save();
            foreach ($cases as $case) {
                ChallengeCaseEvaluation::create(['submission_id' => $submission->id, 'revision_id' => $revision->id, 'test_case_id' => $case->id]);
            }
            DB::table('challenge_participations')->where('id', $participationId)->update([
                'attempts' => $submission->attempt, 'active_submission_id' => $submission->id, 'updated_at' => now()]);
            app(AuditRecorder::class)->record($user->id, $user->id, 'challenge.attempted', 'challenge_submission', $submission->id,
                ['challenge_id' => $current->id, 'revision_id' => $revision->id, 'weekly_event_id' => $event?->id, 'attempt' => $submission->attempt]);
            // Database queue and domain records commit together. Never use sync here.
            Queue::connection('database')->pushOn('code-execution', (new EvaluateSubmission($submission->id))->beforeCommit());

            return $submission;
        });
    }
}
