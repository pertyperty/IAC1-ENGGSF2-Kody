<?php

namespace App\Services\Transactions;

use App\Enums\AccountStatus;
use App\Models\CodingChallenge;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Challenges\Judge0\ProviderReadiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ContentAccess
{
    public function model(string $type): string
    {
        return match ($type) {
            'module' => LearningModule::class, 'course' => LearningCourse::class, 'challenge' => CodingChallenge::class,
            default => abort(404),
        };
    }

    public function has(int $userId, string $type, int $id): bool
    {
        $row = DB::table('content_entitlements')->where('user_id', $userId)->where('content_type', $type)->where('content_id', $id)->first();

        return $row !== null && $row->revoked_at === null;
    }

    public function price(Model $revision, int $creatorId): int
    {
        // Explicit deletion consent makes retained material free for new users;
        // immutable historical revision prices and old sale records stay intact.
        return User::whereKey($creatorId)->where('account_status', AccountStatus::Deleted->value)->exists() ? 0 : $revision->price_kb;
    }

    public function requirements(User $user, Model $revision): array
    {
        $xp = (int) DB::table('xp_totals')->where('user_id', $user->id)->value('xp');
        $missing = [];
        foreach ($revision->prerequisite_modules ?? [] as $moduleId) {
            $standalone = DB::table('learning_activity_days')->where('user_id', $user->id)->where('level', 'module-'.$moduleId)->exists();
            $course = DB::table('course_module_progress as progress')->join('course_enrollments as enrollment', 'enrollment.id', '=', 'progress.enrollment_id')
                ->join('course_revision_modules as assignment', 'assignment.id', '=', 'progress.assignment_id')->where('enrollment.user_id', $user->id)
                ->where('assignment.module_id', $moduleId)->whereNotNull('progress.completed_at')->whereRaw("progress.validated_input->>'kind' IS DISTINCT FROM 'reading'")->exists();
            if (! $standalone && ! $course) {
                $missing[] = $moduleId;
            }
        }

        $titles = LearningModule::whereIn('id', $missing)->with('publishedRevision:id,title')->get()
            ->mapWithKeys(fn ($module) => [$module->id => $module->publishedRevision?->title ?? 'Adventure #'.$module->id])->all();

        return ['eligible' => $xp >= $revision->minimum_xp && $missing === [], 'minimum_xp' => $revision->minimum_xp, 'missing_modules' => $missing, 'missing_titles' => $titles];
    }

    /** Caller must hold the user/content locks and select the approved revision. */
    public function admit(User $user, string $type, Model $target, Model $revision): void
    {
        if ($this->has($user->id, $type, $target->id)) {
            return;
        }
        $requirements = $this->requirements($user, $revision);
        if (! $requirements['eligible']) {
            throw ValidationException::withMessages(['access' => 'Meet this content’s rank and module prerequisites before unlocking it.']);
        }
        $price = $this->price($revision, $target->created_by);
        $purchase = app(WalletLedger::class)->buy($user->id, $target->created_by, $type, $target->id, $revision->id, $price, $revision->creator_settlement);
        $purchase ??= DB::table('content_purchases')->where('user_id', $user->id)->where('content_type', $type)->where('content_id', $target->id)->where('status', 'Active')->first();
        DB::table('content_entitlements')->updateOrInsert(['user_id' => $user->id, 'content_type' => $type, 'content_id' => $target->id],
            ['revision_id' => $revision->id, 'source' => $purchase === null ? 'Free' : 'Paid', 'purchase_id' => $purchase?->id,
                'revoked_at' => null, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function unlock(User $actor, string $sessionId, string $type, int $id, int $revisionId): void
    {
        abort_unless(in_array($type, ['module', 'challenge'], true), 404);
        DB::transaction(function () use ($actor, $sessionId, $type, $id, $revisionId): void {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            Gate::forUser($user)->authorize('viewLearning', LearningCourse::class);
            $class = $this->model($type);
            $target = $class::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($target->status === 'Published' && ! $target->isWithdrawn(), 404);
            $revision = $target->publishedRevision;
            abort_unless($revision?->review_status === 'Approved' && $revision->id === $revisionId, 409, 'This content changed. Reload before unlocking.');
            if ($type === 'challenge' && app(ProviderReadiness::class)->profile($revision) === null) {
                throw ValidationException::withMessages(['access' => 'Code evaluation must be available before unlocking. No KodeBits were used.']);
            }
            $this->admit($user, $type, $target, $revision);
        }, 3);
    }

    public function assertAccessible(User $user, string $type, Model $target, Model $revision): void
    {
        if ($this->has($user->id, $type, $target->id)) {
            return;
        }
        if ($this->price($revision, $target->created_by) > 0) {
            abort(403, 'Unlock this content before learning or submitting.');
        }
        $this->admit($user, $type, $target, $revision);
    }

    public function open(User $actor, string $sessionId, string $type, int $id): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $type, $id): array {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $class = $this->model($type);
            $target = $class::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($target->status === 'Published' && ! $target->isWithdrawn(), 404);
            $revision = $target->publishedRevision;
            abort_unless($revision?->review_status === 'Approved', 404);
            $requirements = $this->requirements($user, $revision);
            $price = $this->price($revision, $target->created_by);
            $participant = Gate::forUser($user)->allows('viewLearning', LearningCourse::class);
            $accessible = $this->has($user->id, $type, $id);
            if (! $accessible && $price === 0 && $requirements['eligible']) {
                if ($participant) {
                    $this->admit($user, $type, $target, $revision);
                }
                $accessible = true;
            }

            return compact('target', 'revision', 'requirements', 'price', 'accessible', 'participant');
        });
    }
}
