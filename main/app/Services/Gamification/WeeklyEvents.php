<?php

namespace App\Services\Gamification;

use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Models\User;
use App\Models\WeeklyEvent;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use App\Services\Challenges\SubmissionStorage;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WeeklyEvents
{
    public function weekStart(): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Manila')->startOfWeek(CarbonInterface::SUNDAY);
    }

    public function lockCalendar(): void
    {
        DB::table('weekly_calendar_locks')->where('id', 1)->lockForUpdate()->firstOrFail();
        $this->closeExpired();
    }

    public function configure(User $actor, string $sessionId, array $data): WeeklyEvent
    {
        return app(SubmissionStorage::class)->transaction(function () use ($actor, $sessionId, $data): WeeklyEvent {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            Gate::forUser($user)->authorize('manage', WeeklyEvent::class);
            $start = CarbonImmutable::createFromFormat('!Y-m-d', $data['week'], 'Asia/Manila');
            if ($start->dayOfWeek !== CarbonInterface::SUNDAY || $start->addWeek()->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['week' => 'Choose this Sunday or a future Sunday.']);
            }
            $this->lockCalendar();
            $challenge = CodingChallenge::whereKey($data['challenge_id'])->lockForUpdate()->firstOrFail();
            if ($challenge->status !== 'Published' || $challenge->published_revision_id !== (int) $data['revision_id']) {
                throw ValidationException::withMessages(['challenge_id' => 'Choose the current approved Published challenge revision.']);
            }
            $revision = CodingChallengeRevision::whereKey($challenge->published_revision_id)->where('review_status', 'Approved')->firstOrFail();
            $event = WeeklyEvent::where('starts_at', $start->utc())->lockForUpdate()->first();
            if (($event?->record_version ?? 0) !== (int) $data['record_version']
                || ($event !== null && ($event->status !== 'Scheduled' || ! $event->starts_at->isFuture()))) {
                throw ValidationException::withMessages(['record_version' => 'This week changed or has already started. Reload its configuration.']);
            }
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $attributes = ['challenge_id' => $challenge->id, 'revision_id' => $revision->id, 'configured_by' => $user->id,
                'rules' => $data['rules'], 'starts_at' => $start->utc(), 'ends_at' => $start->addWeek()->utc(),
                'status' => $start->isFuture() ? 'Scheduled' : 'Active', 'reward_mode' => 'Deferred',
                'record_version' => ($event?->record_version ?? 0) + 1];
            if ($event === null) {
                $event = WeeklyEvent::create($attributes);
            } else {
                $event->update($attributes);
            }
            $this->audit($event, 'weekly.configured', $user->id);

            return $event;
        });
    }

    public function synchronize(): void
    {
        app(SubmissionStorage::class)->transaction(function (): void {
            $this->lockCalendar();
            $start = $this->weekStart()->utc();
            $existing = WeeklyEvent::where('starts_at', $start)->first();
            if ($existing !== null) {
                if ($existing->status === 'Unavailable') {
                    return;
                }
                $challenge = CodingChallenge::whereKey($existing->challenge_id)->lockForUpdate()->firstOrFail();
                if ($challenge->status !== 'Published' || $existing->revision->review_status !== 'Approved') {
                    $existing->update(['status' => 'Unavailable', 'record_version' => $existing->record_version + 1]);
                    $this->audit($existing, 'weekly.unavailable');

                    return;
                }
                $this->admit($existing->id, $challenge);

                return;
            }
            $candidate = CodingChallenge::where('status', 'Published')->whereHas('publishedRevision', fn ($query) => $query->where('review_status', 'Approved'))
                ->inRandomOrder()->first(['id']);
            if ($candidate === null) {
                return;
            }
            $challenge = CodingChallenge::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            if ($challenge->status !== 'Published') {
                return;
            }
            $revision = $challenge->publishedRevision;
            if ($revision->review_status !== 'Approved') {
                return;
            }
            $event = WeeklyEvent::create(['challenge_id' => $challenge->id, 'revision_id' => $revision->id,
                'starts_at' => $start, 'ends_at' => $start->addWeek(), 'rules' => $revision->rules, 'status' => 'Active']);
            $this->audit($event, 'weekly.auto-selected');
        });
    }

    // Call after calendar + challenge locks; the pinned revision never follows publication edits.
    public function admit(int $id, CodingChallenge $challenge): WeeklyEvent
    {
        $event = WeeklyEvent::whereKey($id)->lockForUpdate()->firstOrFail();
        if ($event->challenge_id !== $challenge->id || ! in_array($event->status, ['Scheduled', 'Active'], true)
            || $event->starts_at->isFuture() || $event->ends_at->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages(['weekly_event' => 'This weekly event is not open for submissions.']);
        }
        if ($challenge->status !== 'Published' || $event->revision->review_status !== 'Approved') {
            throw ValidationException::withMessages(['weekly_event' => 'This weekly quest is unavailable.']);
        }
        if ($event->status === 'Scheduled') {
            $event->update(['status' => 'Active', 'record_version' => $event->record_version + 1]);
            $this->audit($event, 'weekly.activated');
        }

        return $event;
    }

    private function closeExpired(): void
    {
        WeeklyEvent::whereIn('status', ['Scheduled', 'Active', 'Unavailable'])->where('ends_at', '<=', now())
            ->lockForUpdate()->chunkById(100, function ($events): void {
                foreach ($events as $event) {
                    $event->update(['status' => 'Ended', 'record_version' => $event->record_version + 1]);
                    $this->audit($event, 'weekly.ended');
                }
            });
    }

    private function audit(WeeklyEvent $event, string $name, ?int $actorId = null): void
    {
        app(AuditRecorder::class)->record($actorId, $actorId, $name, 'weekly_event', (string) $event->id,
            ['challenge_id' => $event->challenge_id, 'revision_id' => $event->revision_id, 'version' => $event->record_version,
                'starts_at' => $event->starts_at->toIso8601String(), 'ends_at' => $event->ends_at->toIso8601String(), 'reward_mode' => 'Deferred']);
    }
}
