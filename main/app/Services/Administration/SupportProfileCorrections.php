<?php

namespace App\Services\Administration;

use App\Jobs\Account\SendSupportCorrectionNotice;
use App\Models\User;
use App\Services\Account\AccountSecurity;
use App\Services\Account\CurrentAccountSession;
use App\Support\AccountPasswords;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;

class SupportProfileCorrections
{
    public function correct(User $actor, string $sessionId, User $target, #[\SensitiveParameter] array $data): bool
    {
        try {
            return DB::transaction(function () use ($actor, $sessionId, $target, $data): bool {
                $accounts = User::whereIn('id', [$actor->id, $target->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $admin = $accounts->get($actor->id);
                $current = $accounts->get($target->id);
                app(CurrentAccountSession::class)->assert($admin, $sessionId);
                Gate::forUser($admin)->authorize('correctProfile', $current);
                if ($current->profile_version !== (int) $data['profile_version']) {
                    throw ValidationException::withMessages(['account' => 'This account changed. Reload before confirming a correction.']);
                }
                foreach (['confirmed', 'support_requested'] as $confirmation) {
                    if (! in_array($data[$confirmation] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)) {
                        throw ValidationException::withMessages([$confirmation => 'Confirm this correction and that the account holder requested help.']);
                    }
                }
                if (! AccountPasswords::matches($data['current_password'] ?? '', $admin->password)) {
                    throw ValidationException::withMessages(['current_password' => 'Confirm your current Administrator password.']);
                }
                $fields = ['username', 'first_name', 'last_name'];
                $current->fill(array_intersect_key($data, array_flip($fields)));
                $changed = array_values(array_intersect($fields, array_keys($current->getDirty())));
                if ($changed === []) {
                    return false;
                }
                $current->name = $current->first_name.' '.$current->last_name;
                app(AccountSecurity::class)->revoke($current);
                $current->forceFill(['profile_version' => $current->profile_version + 1])->save();
                $id = (string) Str::uuid();
                DB::table('account_support_corrections')->insert(['id' => $id, 'user_id' => $current->id, 'actor_id' => $admin->id,
                    'profile_version' => $current->profile_version, 'fields' => json_encode($changed, JSON_THROW_ON_ERROR),
                    'created_at' => now(), 'updated_at' => now()]);
                app(AuditRecorder::class)->record($admin->id, $current->id, 'account.support-corrected', 'account_support_correction', $id,
                    ['fields' => $changed, 'version' => $current->profile_version, 'support_requested' => true]);
                if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
                    throw new LogicException('Support corrections require the application database queue.');
                }
                Queue::connection('database')->push((new SendSupportCorrectionNotice($id))->beforeCommit());

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['username' => 'The username is already registered. Reload and try another username.']);
        } catch (QueryException $exception) {
            // SQL bindings may contain personal fields; never log the original exception.
            Log::error('Support correction storage write failed.', ['sqlstate' => $exception->getCode()]);
            throw new RuntimeException('The support correction could not be saved. Please try again.');
        }
    }
}
