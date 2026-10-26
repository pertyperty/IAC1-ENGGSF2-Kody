<?php

namespace App\Services\Administration;

use App\Enums\Role;
use App\Models\ContributorApplication;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Support\AccountPasswords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreatorBadges
{
    public function proof(User $user): InstructorApplication|ContributorApplication|null
    {
        return $user->account_role === Role::Instructor
            ? InstructorApplication::where('user_id', $user->id)->where('verification_status', 'Approved')->first()
            : ContributorApplication::where('user_id', $user->id)->where('approval_status', 'Approved')->latest('id')->first();
    }

    public function decide(User $actor, string $sessionId, User $target, #[\SensitiveParameter] array $data): void
    {
        if (! in_array($data['decision'] ?? null, ['grant', 'revoke'], true)
            || ! in_array($data['confirmed'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)
            || ! is_string($data['reason'] ?? null) || mb_strlen(trim($data['reason'])) < 5 || mb_strlen($data['reason']) > 500) {
            throw ValidationException::withMessages(['decision' => 'Confirm your decision and provide a review reason.']);
        }
        DB::transaction(function () use ($actor, $sessionId, $target, $data): void {
            $users = User::whereIn('id', [$actor->id, $target->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $reviewer = $users->get($actor->id);
            $creator = $users->get($target->id);
            app(CurrentAccountSession::class)->assert($reviewer, $sessionId);
            Gate::forUser($reviewer)->authorize('verifyCreator', $creator);
            if (! AccountPasswords::matches($data['current_password'], $reviewer->password)) {
                throw ValidationException::withMessages(['current_password' => 'Confirm your current password.']);
            }
            $existing = DB::table('creator_verifications')->where('user_id', $creator->id)->first();
            if (($existing?->record_version ?? 0) !== (int) $data['record_version']) {
                throw ValidationException::withMessages(['record_version' => 'The badge changed. Reload before reviewing.']);
            }
            $active = $data['decision'] === 'grant';
            $proof = $this->proof($creator);
            if ($active && ($proof === null || ! ($data['qualifications_reviewed'] ?? false)
                || (int) ($data['application_id'] ?? 0) !== $proof->id || (int) ($data['application_version'] ?? 0) !== $proof->record_version)) {
                throw ValidationException::withMessages(['qualifications_reviewed' => 'Review the current approved application and its supporting documents first.']);
            }
            if ($active) {
                $prefix = $creator->account_role === Role::Instructor ? 'instructor-credentials/' : 'contributor-credentials/';
                if ($proof->credential_disk === 'public' || config('filesystems.disks.'.$proof->credential_disk.'.visibility') === 'public'
                    || ! str_starts_with($proof->credential_path, $prefix) || str_contains($proof->credential_path, '..') || str_contains($proof->credential_path, '\\')
                    || ! Storage::disk($proof->credential_disk)->exists($proof->credential_path)) {
                    throw ValidationException::withMessages(['qualifications_reviewed' => 'The supporting document is unavailable. Resolve it before granting a badge.']);
                }
            }
            if (! $active && $existing === null) {
                throw ValidationException::withMessages(['decision' => 'There is no badge to revoke.']);
            }
            $fields = $active ? ['role' => $creator->account_role->value,
                'instructor_application_id' => $creator->account_role === Role::Instructor ? $proof->id : null,
                'contributor_application_id' => $creator->account_role === Role::Contributor ? $proof->id : null] : [
                    'role' => $existing->role, 'instructor_application_id' => $existing->instructor_application_id,
                    'contributor_application_id' => $existing->contributor_application_id,
                ];
            DB::table('creator_verifications')->updateOrInsert(['user_id' => $creator->id], $fields + [
                'active' => $active, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'record_version' => ($existing?->record_version ?? 0) + 1,
            ]);
            app(AuditRecorder::class)->record($reviewer->id, $creator->id, $active ? 'creator_badge.granted' : 'creator_badge.revoked',
                'user', (string) $creator->id, ['reason' => $data['reason'], 'role' => $creator->account_role->value,
                    'version' => ($existing?->record_version ?? 0) + 1, 'application_id' => $active ? $proof->id : null]);
        });
    }
}
