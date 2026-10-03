<?php

namespace App\Services\Account;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\EraseAccountFile;
use App\Models\AccountFileErasure;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Support\AccountPasswords;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;

class AccountDeletion
{
    public function hasAuthoredContent(User $user): bool
    {
        foreach (['learning_modules', 'learning_courses', 'coding_challenges'] as $table) {
            if (DB::table($table)->where('created_by', $user->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function delete(User $actor, string $sessionId, #[\SensitiveParameter] array $data): void
    {
        try {
            DB::transaction(function () use ($actor, $sessionId, $data): void {
                $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                Gate::forUser($user)->authorize('delete', $user);
                if ($user->profile_version !== (int) $data['profile_version']) {
                    throw ValidationException::withMessages(['profile_version' => 'Your account changed. Reload before deleting.']);
                }
                if (($data['confirmation_phrase'] ?? null) !== 'DELETE MY ACCOUNT'
                    || ! in_array($data['confirmed'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)
                    || ! AccountPasswords::matches($data['current_password'] ?? '', $user->password)) {
                    throw ValidationException::withMessages(['current_password' => 'Confirm deletion and enter your current password.']);
                }
                if ($this->hasAuthoredContent($user)) {
                    throw ValidationException::withMessages(['account' => 'Deletion for accounts with authored content is unavailable until retention rules are defined.']);
                }
                DB::table('challenge_participations')->where('user_id', $user->id)->lockForUpdate()->chunkById(200, function ($rows): void {
                    if (DB::table('challenge_submissions')->whereIn('participation_id', $rows->pluck('id'))->whereNull('completed_at')->exists()) {
                        throw ValidationException::withMessages(['account' => 'Wait for your active coding evaluations to finish before deleting.']);
                    }
                });
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                app(AccountSecurity::class)->revoke($user);
                $this->removePrivateData($user);
                $user->forceFill(['name' => 'Deleted account', 'username' => null, 'first_name' => null, 'last_name' => null,
                    'email' => Str::uuid().'@deleted.invalid', 'password' => Str::random(64), 'remember_token' => null,
                    'email_verified_at' => null, 'account_status' => AccountStatus::Deleted, 'account_role' => Role::Learner,
                    'last_login_at' => null, 'login_locked_until' => null, 'failed_login_attempts' => 0,
                    'anonymized_at' => now(), 'profile_version' => $user->profile_version + 1])->save();
                app(AuditRecorder::class)->record($user->id, $user->id, 'account.deleted', 'user', (string) $user->id,
                    ['version' => $user->profile_version, 'private_file_cleanup' => 'durable']);
            });
        } catch (QueryException $exception) {
            Log::error('Account deletion write failed.', ['sqlstate' => $exception->getCode()]);
            throw new RuntimeException('Your account could not be deleted. Please try again.');
        }
    }

    private function removePrivateData(User $user): void
    {
        DB::table('contributor_applications')->where('user_id', $user->id)->chunkById(100, function ($applications) use ($user): void {
            foreach ($applications as $application) {
                $this->queueFile($user, $application->credential_disk, $application->credential_path);
                DB::table('notifications')->whereIn('id', DB::table('contributor_notice_deliveries')->where('application_id', $application->id)->select('id'))->delete();
            }
        });
        DB::table('contributor_applications')->where('user_id', $user->id)->delete();
        $application = DB::table('instructor_applications')->where('user_id', $user->id)->first();
        if ($application !== null) {
            if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
                throw new LogicException('Account erasure requires the application database queue.');
            }
            $this->queueFile($user, $application->credential_disk, $application->credential_path);
            DB::table('instructor_application_versions')->where('instructor_application_id', $application->id)->chunkById(100, function ($files) use ($user): void {
                foreach ($files as $file) {
                    $this->queueFile($user, $file->credential_disk, $file->credential_path);
                }
            });
            DB::table('instructor_application_versions')->where('instructor_application_id', $application->id)->delete();
            DB::table('instructor_applications')->where('id', $application->id)->delete();
        }
        $participations = DB::table('challenge_participations')->where('user_id', $user->id)->select('id');
        $submissions = DB::table('challenge_submissions')->whereIn('participation_id', $participations)->select('id');
        DB::table('challenge_case_evaluations')->whereIn('submission_id', $submissions)->delete();
        DB::table('challenge_submissions')->whereIn('participation_id', $participations)->delete();
        DB::table('challenge_participations')->whereIn('id', $participations)->delete();
        $enrollments = DB::table('course_enrollments')->where('user_id', $user->id)->select('id');
        DB::table('course_module_progress')->whereIn('enrollment_id', $enrollments)->delete();
        DB::table('course_enrollments')->where('user_id', $user->id)->delete();
        foreach (['content_accesses', 'content_reactions', 'learning_activity_days', 'learning_level_completions', 'learning_progress', 'email_verifications', 'verification_deliveries', 'account_recoveries'] as $table) {
            DB::table($table)->where('user_id', $user->id)->delete();
        }
        DB::table('notifications')->where('notifiable_id', $user->id)->delete();
    }

    private function queueFile(User $user, string $disk, string $path): void
    {
        if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
            throw new LogicException('Account erasure requires the application database queue.');
        }
        $erasure = AccountFileErasure::firstOrCreate(['user_id' => $user->id, 'disk' => $disk, 'path_digest' => hash('sha256', $path)],
            ['id' => (string) Str::uuid(), 'path' => $path, 'last_queued_at' => now()]);
        if ($erasure->wasRecentlyCreated) {
            $jobId = Queue::connection('database')->push((new EraseAccountFile($erasure->id))->beforeCommit());
            $erasure->update(['queued_job_id' => $jobId]);
        }
    }
}
