<?php

namespace App\Services\Account;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatorProfiles
{
    public function visible(User $user): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null
            && in_array($user->account_role, [Role::Contributor, Role::Instructor, Role::Moderator, Role::Administrator], true);
    }

    public function save(User $actor, string $sessionId, array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $data): void {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            abort_unless($this->visible($user), 403);
            $profile = DB::table('creator_profiles')->where('user_id', $user->id)->first();
            if (($profile?->record_version ?? 0) !== (int) $data['record_version']) {
                throw ValidationException::withMessages(['record_version' => 'Your profile changed. Reload before saving.']);
            }
            DB::table('creator_profiles')->updateOrInsert(['user_id' => $user->id], [
                'headline' => trim($data['headline'] ?? ''), 'bio' => trim($data['bio'] ?? ''),
                'accomplishments' => trim($data['accomplishments'] ?? ''), 'record_version' => ($profile?->record_version ?? 0) + 1,
                'created_at' => $profile?->created_at ?? now(), 'updated_at' => now(),
            ]);
        });
    }
}
