<?php

namespace App\Services\Account;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatorProfiles
{
    public const SOCIAL_PLATFORMS = [
        'website' => 'Website',
        'github' => 'GitHub',
        'linkedin' => 'LinkedIn',
        'x' => 'X',
    ];

    public function visible(User $user): bool
    {
        return $user->account_status === AccountStatus::Active && $user->email_verified_at !== null
            && in_array($user->account_role, [Role::Contributor, Role::Instructor, Role::Moderator, Role::Administrator], true);
    }

    /** @return array<string, array{label: string, url: string}> */
    public function socials(?object $profile): array
    {
        $raw = $profile?->social_links ?? null;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw)) {
            return [];
        }
        $links = [];
        foreach (self::SOCIAL_PLATFORMS as $key => $label) {
            $url = is_string($raw[$key] ?? null) ? trim($raw[$key]) : '';
            if ($url !== '' && str_starts_with($url, 'https://')) {
                $links[$key] = ['label' => $label, 'url' => $url];
            }
        }

        return $links;
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
            $socials = [];
            foreach (array_keys(self::SOCIAL_PLATFORMS) as $key) {
                $url = trim((string) ($data['social_links'][$key] ?? ''));
                if ($url !== '') {
                    $socials[$key] = $url;
                }
            }
            DB::table('creator_profiles')->updateOrInsert(['user_id' => $user->id], [
                'headline' => trim($data['headline'] ?? ''),
                'bio' => trim($data['bio'] ?? ''),
                'accomplishments' => trim($data['accomplishments'] ?? ''),
                'qualifications' => trim($data['qualifications'] ?? ''),
                'social_links' => $socials === [] ? null : json_encode($socials, JSON_THROW_ON_ERROR),
                'record_version' => ($profile?->record_version ?? 0) + 1,
                'created_at' => $profile?->created_at ?? now(),
                'updated_at' => now(),
            ]);
        });
    }
}
