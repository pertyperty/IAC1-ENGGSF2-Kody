<?php

namespace App\Services\Account;

use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Support\AccountPasswords;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GoogleAuthentication
{
    public function state(User $user): array
    {
        $identity = DB::table('google_identities')->where('user_id', $user->id)->first();

        return ['linked' => $identity?->subject_hash !== null, 'identity_version' => $identity?->record_version ?? 0];
    }

    public function start(Request $request, ?array $data = null): RedirectResponse
    {
        return DB::transaction(function () use ($request, $data): RedirectResponse {
            $user = $data === null ? null : $this->confirmedAccount($request, $data);
            $response = app(GoogleOAuth::class)->redirect($request);
            $id = (string) Str::uuid();
            DB::table('google_auth_attempts')->upsert([[
                'id' => $id, 'session_hash' => hash('sha256', $request->session()->getId()),
                'state_hash' => hash('sha256', $request->session()->get('state')), 'intent' => $user === null ? 'login' : 'link',
                'user_id' => $user?->id, 'profile_version' => $user?->profile_version,
                'identity_version' => $user === null ? null : $this->state($user)['identity_version'],
                'expires_at' => now()->addMinutes(5), 'consumed_at' => null, 'created_at' => now(),
            ]], ['session_hash']);
            $request->session()->put('google_attempt_id', $id);

            return $response;
        });
    }

    public function claim(Request $request): object
    {
        $id = $request->session()->pull('google_attempt_id');
        $state = $request->query('state');
        if (! is_string($id) || ! Str::isUuid($id) || ! is_string($state) || strlen($state) > 256) {
            throw new RuntimeException('Start Google sign-in again.');
        }

        return DB::transaction(function () use ($request, $id, $state): object {
            $attempt = DB::table('google_auth_attempts')->where('id', $id)->lockForUpdate()->first();
            if ($attempt === null || $attempt->consumed_at !== null || now()->gte($attempt->expires_at)
                || ! hash_equals($attempt->session_hash, hash('sha256', $request->session()->getId()))
                || ! hash_equals($attempt->state_hash, hash('sha256', $state))) {
                throw new RuntimeException('Start Google sign-in again.');
            }
            DB::table('google_auth_attempts')->where('id', $id)->update(['consumed_at' => now()]);

            return $attempt;
        });
    }

    public function link(Request $request, object $attempt, string $subjectHash): void
    {
        try {
            DB::transaction(function () use ($request, $attempt, $subjectHash): void {
                $user = User::whereKey($attempt->user_id)->lockForUpdate()->firstOrFail();
                app(CurrentAccountSession::class)->assert($user, $request->session()->getId());
                Gate::forUser($user)->authorize('update', $user);
                if ($request->user()?->id !== $user->id || $user->profile_version !== $attempt->profile_version
                    || $this->state($user)['identity_version'] !== $attempt->identity_version) {
                    throw new RuntimeException('Your account changed. Start linking again.');
                }
                $current = $this->state($user);
                if ($current['linked']) {
                    throw new RuntimeException('Unlink your current Google account before linking another.');
                }
                DB::table('google_identities')->updateOrInsert(['user_id' => $user->id], [
                    'subject_hash' => $subjectHash, 'record_version' => $current['identity_version'] + 1,
                    'linked_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->changed($user, 'account.google-linked');
            });
        } catch (UniqueConstraintViolationException) {
            throw new RuntimeException('This Google account cannot be linked.');
        }
    }

    public function unlink(Request $request, array $data): void
    {
        DB::transaction(function () use ($request, $data): void {
            $user = $this->confirmedAccount($request, $data);
            $state = $this->state($user);
            if (! $state['linked']) {
                throw ValidationException::withMessages(['identity_version' => 'No Google account is linked.']);
            }
            DB::table('google_identities')->where('user_id', $user->id)->update([
                'subject_hash' => null, 'linked_at' => null, 'record_version' => $state['identity_version'] + 1, 'updated_at' => now(),
            ]);
            $this->changed($user, 'account.google-unlinked');
        });
    }

    private function confirmedAccount(Request $request, #[\SensitiveParameter] array $data): User
    {
        $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
        app(CurrentAccountSession::class)->assert($user, $request->session()->getId());
        Gate::forUser($user)->authorize('update', $user);
        if ($user->profile_version !== (int) $data['profile_version'] || $this->state($user)['identity_version'] !== (int) $data['identity_version']) {
            throw ValidationException::withMessages(['profile_version' => 'Your account changed. Reload this page.']);
        }
        if (! AccountPasswords::matches($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Confirm your current Kody password.']);
        }

        return $user;
    }

    private function changed(User $user, string $event): void
    {
        app(AccountSecurity::class)->revoke($user);
        $user->forceFill(['profile_version' => $user->profile_version + 1])->save();
        app(AuditRecorder::class)->record($user->id, $user->id, $event, 'user', (string) $user->id,
            ['version' => $user->profile_version]);
    }
}
