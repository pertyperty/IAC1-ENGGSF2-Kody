<?php

namespace App\Services\Administration;

use App\Enums\AccountStatus;
use App\Jobs\Account\SendEnforcementNotice;
use App\Models\User;
use App\Services\Account\AccountSecurity;
use App\Services\Account\CurrentAccountSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class AccountEnforcement
{
    public function change(User $actor, string $sessionId, User $target, array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $target, $data): void {
            $accounts = User::whereIn('id', [$actor->id, $target->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $reviewer = $accounts->get($actor->id);
            $current = $accounts->get($target->id);
            app(CurrentAccountSession::class)->assert($reviewer, $sessionId);
            Gate::forUser($reviewer)->authorize('manage', $current);
            $action = $data['action'];
            $expected = match ($action) {
                'Suspended' => AccountStatus::Active,
                'Reinstated' => AccountStatus::Suspended,
                default => null,
            };
            if ($expected === null || $current->account_status !== $expected || $current->profile_version !== (int) $data['profile_version']
                || ! in_array($data['confirmed'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)) {
                throw ValidationException::withMessages(['account' => 'The account changed or this action was already applied. Reload and confirm a valid action.']);
            }
            // Reinstatement restores status, never old sessions or consumed proofs.
            app(AccountSecurity::class)->revoke($current);
            $current->forceFill(['account_status' => $action === 'Suspended' ? AccountStatus::Suspended : AccountStatus::Active,
                'profile_version' => $current->profile_version + 1])->save();
            $id = (string) Str::uuid();
            DB::table('account_enforcements')->insert(['id' => $id, 'user_id' => $current->id, 'actor_id' => $reviewer->id,
                'action' => $action, 'profile_version' => $current->profile_version, 'created_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record($reviewer->id, $current->id, 'account.'.strtolower($action), 'account_enforcement', $id,
                ['previous_status' => $expected->value, 'resulting_status' => $current->account_status->value, 'version' => $current->profile_version]);
            if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
                throw new LogicException('Account enforcement requires the application database queue.');
            }
            Queue::connection('database')->push((new SendEnforcementNotice($id))->beforeCommit());
        });
    }
}
