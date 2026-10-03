<?php

namespace App\Services\Account;

use App\Enums\AccountStatus;
use App\Models\AccountRecovery;
use App\Models\EmailVerification;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Administration\AuditRecorder;
use App\Support\AccountPasswords;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;

class ProfileEditing
{
    public function update(User $actor, string $sessionId, #[\SensitiveParameter] array $data): string
    {
        try {
            return DB::transaction(function () use ($actor, $sessionId, $data): string {
                $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                Gate::forUser($user)->authorize('update', $user);
                if ($user->profile_version !== (int) $data['profile_version']) {
                    throw ValidationException::withMessages(['profile_version' => 'Your profile changed. Reload before saving.']);
                }
                $emailChanged = $user->email !== $data['email'];
                $passwordChanged = isset($data['password']);
                $sensitive = $emailChanged || $passwordChanged;
                if ($sensitive && ! AccountPasswords::matches($data['current_password'] ?? '', $user->password)) {
                    throw ValidationException::withMessages(['current_password' => 'Confirm your current password for email or password changes.']);
                }
                $user->fill(array_intersect_key($data, array_flip(['username', 'first_name', 'last_name', 'email'])));
                $user->name = $user->first_name.' '.$user->last_name;
                $changed = array_keys($user->getDirty());
                if ($passwordChanged) {
                    $user->password = $data['password'];
                    $changed[] = 'password';
                }
                if ($changed === []) {
                    return 'unchanged';
                }
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                if ($sensitive) {
                    $this->revokeSecurityProofs($user);
                }
                if ($emailChanged) {
                    $user->forceFill(['account_status' => AccountStatus::Unverified, 'email_verified_at' => null]);
                }
                $user->forceFill(['profile_version' => $user->profile_version + 1])->save();
                if ($emailChanged) {
                    EmailVerification::updateOrCreate(['user_id' => $user->id], ['email' => $user->email,
                        'token_hash' => null, 'expires_at' => null, 'request_count' => 0, 'last_requested_at' => null]);
                    if (! app(EmailVerificationService::class)->request($user)) {
                        throw new RuntimeException('Verification delivery could not be queued.');
                    }
                }
                app(AuditRecorder::class)->record($user->id, $user->id, 'account.profile-updated', 'user', (string) $user->id,
                    ['fields' => array_values(array_unique($changed)), 'version' => $user->profile_version, 'reverification' => $emailChanged]);

                return $emailChanged ? 'verify' : ($passwordChanged ? 'sign-in' : 'saved');
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'The email or username is already registered.']);
        } catch (QueryException $exception) {
            // Never attach the exception: SQL bindings can contain hashes or private email.
            Log::error('Profile storage write failed.', ['sqlstate' => $exception->getCode()]);
            throw new RuntimeException('Your profile could not be saved. Please try again.');
        }
    }

    private function revokeSecurityProofs(User $user): void
    {
        if (config('session.driver') === 'database'
            && (config('session.connection') ?? config('database.default')) !== config('database.default')) {
            throw new LogicException('Profile changes require database sessions on the application database.');
        }
        $user->forceFill(['remember_token' => Str::random(60), 'active_session_hash' => null, 'active_session_expires_at' => null]);
        AccountRecovery::where('user_id', $user->id)->update(['token_hash' => null, 'expires_at' => null]);
        EmailVerification::where('user_id', $user->id)->update(['token_hash' => null, 'expires_at' => null]);
        VerificationDelivery::where('user_id', $user->id)->whereNull('sent_at')->whereNull('cancelled_at')
            ->update(['token' => null, 'cancelled_at' => now()]);
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $user->id)->delete();
        }
    }
}
