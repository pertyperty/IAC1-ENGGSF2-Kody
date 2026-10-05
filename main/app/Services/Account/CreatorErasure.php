<?php

namespace App\Services\Account;

use App\Enums\Role;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Transactions\FinancialAccounts;
use App\Services\Transactions\FinancialNotices;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatorErasure
{
    public function inventory(User $user): array
    {
        $digest = hash_init('sha256');
        $counts = [];
        foreach ([['learning_courses', 'course_revisions', 'course_id'], ['learning_modules', 'module_revisions', 'module_id'], ['coding_challenges', 'coding_challenge_revisions', 'challenge_id']] as [$table, $revisions, $foreign]) {
            $ids = DB::table($table)->where('created_by', $user->id)->orderBy('id')->lockForUpdate()->pluck('id');
            $counts[$table] = $ids->count();
            DB::table($table)->whereIn('id', $ids)->orderBy('id')->chunkById(100, function ($rows) use ($digest, $table): void {
                foreach ($rows as $row) {
                    hash_update($digest, $table.json_encode($row, JSON_THROW_ON_ERROR));
                }
            });
            DB::table($revisions)->whereIn($foreign, $ids)->orderBy('id')->chunkById(100, function ($rows) use ($digest, $revisions): void {
                foreach ($rows as $row) {
                    hash_update($digest, $revisions.json_encode($row, JSON_THROW_ON_ERROR));
                    if ($revisions === 'coding_challenge_revisions') {
                        foreach (DB::table('challenge_test_cases')->where('revision_id', $row->id)->orderBy('position')->get() as $test) {
                            hash_update($digest, json_encode($test, JSON_THROW_ON_ERROR));
                        }
                    } elseif ($revisions === 'course_revisions') {
                        foreach (DB::table('course_revision_modules')->where('course_revision_id', $row->id)->orderBy('position')->get() as $slot) {
                            hash_update($digest, json_encode($slot, JSON_THROW_ON_ERROR));
                        }
                    }
                }
            });
        }

        return ['fingerprint' => hash_final($digest), 'counts' => $counts];
    }

    public function request(User $actor, string $sessionId, #[\SensitiveParameter] array $data): string
    {
        return DB::transaction(function () use ($actor, $sessionId, $data): string {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $sessionId);
            Gate::forUser($user)->authorize('delete', $user);
            app(FinancialAccounts::class)->confirm($user, $data);
            if (! in_array($data['retention_consent'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)) {
                throw ValidationException::withMessages(['retention_consent' => 'Consent to retaining your learning material and making it free under Deleted creator attribution.']);
            }
            abort_unless(app(AccountDeletion::class)->hasAuthoredContent($user), 409);
            $inventory = $this->inventory($user);
            $existing = DB::table('creator_erasure_reviews')->where('user_id', $user->id)->where('state', 'Pending')->first();
            if ($existing !== null) {
                return $existing->id;
            }
            $id = (string) Str::uuid();
            DB::table('creator_erasure_reviews')->insert(['id' => $id, 'user_id' => $user->id, 'fingerprint' => $inventory['fingerprint'], 'created_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record($user->id, $user->id, 'creator.erasure_requested', 'creator_erasure_review', $id, ['fingerprint' => $inventory['fingerprint'], 'retention_consent' => true, 'policy_version' => config('economy.policy_version')]);

            return $id;
        }, 3);
    }

    public function review(User $actor, string $sessionId, string $id, #[\SensitiveParameter] array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $id, $data): void {
            $staff = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($staff, $sessionId);
            abort_unless(in_array($staff->account_role, [Role::Moderator, Role::Administrator], true), 403);
            app(FinancialAccounts::class)->confirm($staff, $data);
            $candidate = DB::table('creator_erasure_reviews')->find($id) ?? abort(404);
            abort_if($candidate->user_id === $staff->id, 403);
            $user = User::whereKey($candidate->user_id)->lockForUpdate()->firstOrFail();
            $row = DB::table('creator_erasure_reviews')->where('id', $id)->lockForUpdate()->firstOrFail();
            abort_unless($row->state === 'Pending', 409);
            $inventory = $this->inventory($user);
            if (($data['decision'] ?? '') === 'Approved' && ! hash_equals($row->fingerprint, $inventory['fingerprint'])) {
                throw ValidationException::withMessages(['review' => 'Content changed after this request. Reject it and request a fresh inventory.']);
            }
            $approved = ($data['decision'] ?? '') === 'Approved';
            if (! in_array($data['decision'] ?? '', ['Approved', 'Rejected'], true) || empty(trim($data['review_notes'] ?? ''))
                || ($approved && ! in_array($data['privacy_reviewed'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true))) {
                throw ValidationException::withMessages(['review' => 'Record review notes and confirm a manual privacy inspection of every retained revision before approval.']);
            }
            DB::table('creator_erasure_reviews')->where('id', $id)->update(['state' => $approved ? 'Approved' : 'Rejected', 'reviewed_by' => $staff->id,
                'review_notes' => $data['review_notes'], 'reviewed_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record($staff->id, $user->id, 'creator.erasure_reviewed', 'creator_erasure_review', $id, ['decision' => $data['decision'], 'fingerprint' => $inventory['fingerprint']]);
            app(FinancialNotices::class)->queue($user->id, 'creator-erasure:'.$id, 'Creator privacy review '.strtolower($data['decision']), []);
        }, 3);
    }

    public function consume(User $user, bool $consent): void
    {
        $inventory = $this->inventory($user);
        $review = DB::table('creator_erasure_reviews')->where('user_id', $user->id)->where('state', 'Approved')->where('fingerprint', $inventory['fingerprint'])->lockForUpdate()->first();
        if (! $consent || $review === null) {
            throw ValidationException::withMessages(['account' => 'Creator deletion requires retention consent and an approved, unchanged privacy inventory.']);
        }
        DB::table('creator_erasure_reviews')->where('id', $review->id)->update(['state' => 'Consumed', 'updated_at' => now()]);
    }
}
