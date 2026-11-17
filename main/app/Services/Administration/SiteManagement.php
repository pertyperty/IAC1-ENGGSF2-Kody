<?php

namespace App\Services\Administration;

use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Operations\Heartbeats;
use App\Support\AccountPasswords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SiteManagement
{
    public function rules(): array
    {
        return ['title' => ['nullable', 'required_if:active,1', 'string', 'max:80'], 'message' => ['nullable', 'required_if:active,1', 'string', 'max:500'],
            'destination' => ['required', Rule::in(['none', 'help', 'contact', 'learn'])], 'active' => ['required', 'boolean'], 'record_version' => ['required', 'integer', 'min:1'],
            'current_password' => ['required', 'string', 'max:1024'], 'confirmed' => ['required', 'accepted'], 'updated_by' => ['prohibited']];
    }

    public function save(User $actor, string $sessionId, #[\SensitiveParameter] array $data): void
    {
        $data = Validator::make($data, $this->rules())->validate();
        DB::transaction(function () use ($actor, $sessionId, $data): void {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            Gate::forUser($user)->authorize('manageSite', User::class);
            if (! AccountPasswords::matches($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Confirm your current password.']);
            }
            $row = DB::table('site_announcements')->where('id', 1)->lockForUpdate()->firstOrFail();
            if ($row->record_version !== (int) $data['record_version']) {
                throw ValidationException::withMessages(['record_version' => 'This announcement changed. Reload before saving.']);
            }
            $fields = ['title' => trim($data['title'] ?? ''), 'message' => trim($data['message'] ?? ''), 'destination' => $data['destination'], 'active' => (bool) $data['active']];
            if ($fields['active'] && ($fields['title'] === '' || $fields['message'] === '')) {
                throw ValidationException::withMessages(['message' => 'A published announcement needs a title and message.']);
            }
            DB::table('site_announcements')->where('id', 1)->update($fields + ['updated_by' => $user->id, 'updated_at' => now(), 'record_version' => $row->record_version + 1]);
            app(AuditRecorder::class)->record($user->id, null, 'site.announcement_updated', 'site_announcement', '1',
                ['before' => array_intersect_key((array) $row, $fields), 'after' => $fields, 'version' => $row->record_version + 1]);
        });
    }

    public function snapshot(User $actor, string $sessionId): array
    {
        $user = User::findOrFail($actor->id);
        app(CurrentAccountSession::class)->assert($user, $sessionId);
        Gate::forUser($user)->authorize('manageSite', User::class);

        return ['announcement' => DB::table('site_announcements')->where('id', 1)->firstOrFail(),
            'heartbeats' => app(Heartbeats::class)->snapshot(),
            'queues' => DB::table('jobs')->select('queue')->selectRaw('count(*) as total, min(created_at) as oldest,
                count(*) FILTER (WHERE reserved_at IS NOT NULL) as reserved,
                count(*) FILTER (WHERE reserved_at IS NULL AND available_at <= ?) as ready,
                count(*) FILTER (WHERE reserved_at IS NULL AND available_at > ?) as delayed', [now()->timestamp, now()->timestamp])
                ->groupBy('queue')->orderBy('queue')->get(),
            'failed' => DB::table('failed_jobs')->where('failed_at', '>=', now()->subDays(7))->count(),
            'services' => ['Judge0 execution' => (bool) config('judge0.enabled'), 'Xendit payments' => (bool) config('xendit.enabled'), 'Google identity' => (bool) config('services.google.enabled'),
                'SendGrid verification transport' => config('account.verification.mailer') === 'sendgrid'],
            'changes' => DB::table('audit_events')->where('event', 'site.announcement_updated')->orderByDesc('created_at')->orderByDesc('id')->limit(5)->get(['actor_id', 'created_at'])];
    }
}
