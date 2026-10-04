<?php

namespace App\Services\Engagement;

use App\Models\CodingChallenge;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Content\CourseLearning;
use App\Services\Transactions\ContentAccess;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ContentFeedback
{
    public function model(string $kind): string
    {
        return match ($kind) {
            'module' => LearningModule::class, 'course' => LearningCourse::class, 'challenge' => CodingChallenge::class,
            default => abort(404),
        };
    }

    public function read(User $actor, string $sessionId, string $kind, int $id, bool $opened = false): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $kind, $id, $opened): array {
            $user = $this->account($actor, $sessionId);
            [, $entitled] = $this->target($user, $sessionId, $kind, $id);
            $participant = Gate::forUser($user)->allows('viewLearning', LearningCourse::class);
            if ($opened && $participant && $entitled) {
                // Only content-delivery controllers invoke this; request data cannot attest a visit.
                $query = $this->query('content_accesses', $kind, $id)->where('user_id', $user->id);
                if (! $query->exists()) {
                    DB::table('content_accesses')->insert(['user_id' => $user->id, 'kind' => $kind, $kind.'_id' => $id, 'first_opened_at' => now()]);
                }
            }

            return $this->state($user, $kind, $id, $participant && $entitled && $this->proof($user, $kind, $id));
        });
    }

    public function change(User $actor, string $sessionId, string $kind, int $id, int $version, ?string $reaction): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $kind, $id, $version, $reaction): array {
            $user = $this->account($actor, $sessionId);
            Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
            [, $entitled] = $this->target($user, $sessionId, $kind, $id);
            abort_unless($entitled && $this->proof($user, $kind, $id), 403, 'Try this content before leaving a reaction.');
            if ($version < 0 || ($reaction !== null && ! in_array($reaction, ['Like', 'Helpful', 'Favorite'], true))) {
                throw ValidationException::withMessages(['reaction' => 'Choose a supported reaction.']);
            }
            $row = $this->query('content_reactions', $kind, $id)->where('user_id', $user->id)->first();
            $currentVersion = $row?->record_version ?? 0;
            if ($row?->reaction === $reaction && in_array($version, [$currentVersion, $currentVersion - 1], true)) {
                return $this->state($user, $kind, $id, true);
            }
            if ($version !== $currentVersion) {
                throw ValidationException::withMessages(['record_version' => 'Your reaction changed. Reload before choosing again.']);
            }
            if ($row === null) {
                DB::table('content_reactions')->insert(['user_id' => $user->id, 'kind' => $kind, $kind.'_id' => $id,
                    'reaction' => $reaction, 'record_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
            } else {
                // Keep a versioned empty row after removal so stale forms cannot recreate old feedback.
                DB::table('content_reactions')->where('id', $row->id)->update(['reaction' => $reaction, 'record_version' => $currentVersion + 1, 'updated_at' => now()]);
            }

            return $this->state($user, $kind, $id, true);
        });
    }

    private function account(User $actor, string $sessionId): User
    {
        $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
        app(CurrentAccountSession::class)->assert($user, $sessionId);

        return $user;
    }

    private function target(User $user, string $sessionId, string $kind, int $id): array
    {
        $class = $this->model($kind);
        $target = $class::whereKey($id)->lockForUpdate()->firstOrFail();
        abort_if($target->isWithdrawn(), 404);
        if ($kind === 'course') {
            $outline = app(CourseLearning::class)->outline($user, $sessionId, $id);

            return [$target, $outline['enrollment'] !== null && $outline['accessible']];
        }
        abort_unless($target->status === 'Published' && $target->publishedRevision?->review_status === 'Approved', 404);

        $access = app(ContentAccess::class);
        $entitled = $access->has($user->id, $kind, $id)
            || ($access->price($target->publishedRevision, $target->created_by) === 0 && $access->requirements($user, $target->publishedRevision)['eligible']);
        if (! $entitled && $kind === 'module') {
            $entitled = DB::table('course_module_progress as progress')->join('course_enrollments as enrollment', 'enrollment.id', '=', 'progress.enrollment_id')
                ->join('course_revision_modules as assignment', 'assignment.id', '=', 'progress.assignment_id')
                ->join('content_entitlements as access', fn ($join) => $join->on('access.user_id', '=', 'enrollment.user_id')->on('access.content_id', '=', 'enrollment.course_id')->where('access.content_type', 'course')->whereNull('access.revoked_at'))
                ->where('enrollment.user_id', $user->id)->where('assignment.module_id', $id)->whereNotNull('progress.first_accessed_at')->exists();
        }

        return [$target, $entitled];
    }

    private function proof(User $user, string $kind, int $id): bool
    {
        if ($this->query('content_accesses', $kind, $id)->where('user_id', $user->id)->exists()) {
            return true;
        }
        if ($kind === 'challenge') {
            return DB::table('challenge_submissions')->join('challenge_participations', 'challenge_participations.id', '=', 'challenge_submissions.participation_id')
                ->where('challenge_participations.user_id', $user->id)->where('challenge_participations.challenge_id', $id)
                ->where('challenge_submissions.status', 'Passed')->whereNotNull('challenge_submissions.completed_at')->exists();
        }
        if ($kind === 'module' && DB::table('learning_activity_days')->where('user_id', $user->id)->where('level', 'module-'.$id)->exists()) {
            return true;
        }
        $visits = DB::table('course_module_progress')->join('course_enrollments', 'course_enrollments.id', '=', 'course_module_progress.enrollment_id')
            ->join('course_revision_modules', 'course_revision_modules.id', '=', 'course_module_progress.assignment_id')
            ->where('course_enrollments.user_id', $user->id)->whereNotNull('course_module_progress.first_accessed_at');

        return $kind === 'course' ? $visits->where('course_enrollments.course_id', $id)->exists() : $visits->where('course_revision_modules.module_id', $id)->exists();
    }

    private function state(User $user, string $kind, int $id, bool $eligible): array
    {
        $row = $this->query('content_reactions', $kind, $id)->where('user_id', $user->id)->first();
        $counts = array_fill_keys(['Like', 'Helpful', 'Favorite'], 0);
        foreach ($this->query('content_reactions', $kind, $id)->whereNotNull('reaction')->selectRaw('reaction, count(*) AS total')->groupBy('reaction')->get() as $count) {
            $counts[$count->reaction] = (int) $count->total;
        }

        return ['kind' => $kind, 'content_id' => $id, 'eligible' => $eligible, 'reaction' => $row?->reaction, 'record_version' => $row?->record_version ?? 0, 'counts' => $counts];
    }

    private function query(string $table, string $kind, int $id): Builder
    {
        $this->model($kind);

        return DB::table($table)->where('kind', $kind)->where($kind.'_id', $id);
    }
}
