<?php

namespace App\Services\Account;

use App\Enums\Role;
use App\Models\CodingChallenge;
use App\Models\LearningModule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class RoleChangeNotices
{
    public const TYPE = 'account.role-changed';

    public function isLocalDemo(User $user): bool
    {
        return app()->environment(['local', 'testing']) && DB::table('audit_events')->where('event', 'local.role-demo-prepared')->where('subject_id', (string) $user->id)->exists();
    }

    /** The approved application and locked account must share this transaction. */
    public function record(User $user, Role $previous, string $applicationKind, int $applicationId): void
    {
        if (DB::transactionLevel() === 0 || ! in_array($user->account_role, [Role::Contributor, Role::Instructor], true)) {
            throw new LogicException('Role notices require an approved application transaction.');
        }
        DB::table('notifications')->insert(['id' => (string) Str::uuid(), 'type' => self::TYPE,
            'notifiable_type' => User::class, 'notifiable_id' => $user->id,
            'data' => json_encode(['title' => $user->account_role->name.' access approved', 'decision' => 'Approved',
                'previous_role' => $previous->value, 'role' => $user->account_role->value,
                'application_kind' => $applicationKind, 'application_id' => $applicationId], JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now()]);
    }

    public function snapshot(User $actor, string $sessionId): array
    {
        $user = User::findOrFail($actor->id);
        app(CurrentAccountSession::class)->assert($user, $sessionId);
        $notice = $user->unreadNotifications()->where('type', self::TYPE)->orderByDesc('created_at')->orderByDesc('id')->limit(10)->get()
            ->first(fn ($notice) => ($notice->data['role'] ?? null) === $user->account_role->value);
        $update = $notice === null ? null : $this->presentation($user, $notice->id, $notice->data);
        $pending = in_array($user->account_role, [Role::Learner, Role::Contributor], true)
            && (DB::table('instructor_applications')->where('user_id', $user->id)->where('verification_status', 'Pending')->exists()
                || DB::table('contributor_applications')->where('user_id', $user->id)->where('approval_status', 'Pending')->exists());

        return ['update' => $update, 'watch' => $pending || $update !== null];
    }

    public function acknowledge(User $actor, string $sessionId, string $notification): string
    {
        return DB::transaction(function () use ($actor, $sessionId, $notification): string {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            $notice = $user->notifications()->whereKey($notification)->where('type', self::TYPE)->lockForUpdate()->firstOrFail();
            $presentation = $this->presentation($user, $notice->id, $notice->data);
            if ($presentation === null) {
                throw ValidationException::withMessages(['role' => 'Your account changed again. Reload to see your current workspace.']);
            }
            $notice->markAsRead();

            return $presentation['workspace'];
        });
    }

    private function presentation(User $user, string $id, array $data): ?array
    {
        if (($data['role'] ?? null) !== $user->account_role->value) {
            return null;
        }
        $role = $user->account_role;
        $model = match ($role) {
            Role::Instructor => LearningModule::class, Role::Contributor => CodingChallenge::class, default => null
        };
        if ($model === null || ! Gate::forUser($user)->allows('create', $model)) {
            return null;
        }

        return ['id' => $id, 'role' => $role->name, 'previous' => Role::tryFrom($data['previous_role'] ?? '')?->name ?? 'Learner',
            'title' => $role === Role::Instructor ? 'You’re now an Instructor' : 'You’re now a Contributor',
            'message' => $role === Role::Instructor ? 'Your teaching journey starts here. Turn what you know into lessons, games and learning paths.' : 'Your next idea can become someone’s coding quest. Create challenges and help learners practice.',
            'capabilities' => $role === Role::Instructor ? ['Create learning modules', 'Build courses and assessments', 'Keep playing the Tower'] : ['Author coding quests', 'Preview and submit for review', 'Keep your saved learning history'],
            'workspace' => route($role === Role::Instructor ? 'studio.index' : 'challenges.index'),
            'action' => $role === Role::Instructor ? 'Explore your module studio' : 'Explore your quest studio',
            'acknowledge' => route('account.role-update.acknowledge', $id)];
    }
}
