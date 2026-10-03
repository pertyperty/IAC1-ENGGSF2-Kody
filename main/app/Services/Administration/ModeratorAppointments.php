<?php

namespace App\Services\Administration;

use App\Enums\Role;
use App\Jobs\Account\SendModeratorNotice;
use App\Models\User;
use App\Services\Account\AccountSecurity;
use App\Services\Account\CurrentAccountSession;
use App\Support\AccountPasswords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class ModeratorAppointments
{
    public function change(User $actor, string $sessionId, User $target, #[\SensitiveParameter] array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $target, $data): void {
            $accounts = User::whereIn('id', [$actor->id, $target->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $admin = $accounts->get($actor->id);
            $current = $accounts->get($target->id);
            app(CurrentAccountSession::class)->assert($admin, $sessionId);
            Gate::forUser($admin)->authorize('changeModeratorRole', $current);
            $action = $data['action'];
            $participantRoles = [Role::Learner, Role::Contributor, Role::Instructor];
            $appoint = $action === 'Appointed' && in_array($current->account_role, $participantRoles, true) && $current->moderator_prior_role === null;
            $remove = $action === 'Removed' && $current->account_role === Role::Moderator && in_array($current->moderator_prior_role, $participantRoles, true);
            if ((! $appoint && ! $remove) || $current->profile_version !== (int) $data['profile_version']
                || ! in_array($data['confirmed'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)) {
                throw ValidationException::withMessages(['account' => 'This account changed or the role action was already applied. Reload and confirm a valid action.']);
            }
            if (! AccountPasswords::matches($data['current_password'] ?? '', $admin->password)) {
                throw ValidationException::withMessages(['current_password' => 'Confirm your current Administrator password.']);
            }
            $previousRole = $current->account_role;
            $resultingRole = $appoint ? Role::Moderator : $current->moderator_prior_role;
            app(AccountSecurity::class)->revoke($current);
            $current->forceFill(['account_role' => $resultingRole, 'moderator_prior_role' => $appoint ? $previousRole : null,
                'profile_version' => $current->profile_version + 1])->save();
            $id = (string) Str::uuid();
            DB::table('account_role_changes')->insert(['id' => $id, 'user_id' => $current->id, 'actor_id' => $admin->id, 'action' => $action,
                'previous_role' => $previousRole->value, 'resulting_role' => $resultingRole->value,
                'profile_version' => $current->profile_version, 'created_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record($admin->id, $current->id, 'account.moderator-'.strtolower($action), 'account_role_change', $id,
                ['previous_role' => $previousRole->value, 'resulting_role' => $resultingRole->value, 'version' => $current->profile_version]);
            if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
                throw new LogicException('Moderator appointments require the application database queue.');
            }
            Queue::connection('database')->push((new SendModeratorNotice($id))->beforeCommit());
        });
    }
}
