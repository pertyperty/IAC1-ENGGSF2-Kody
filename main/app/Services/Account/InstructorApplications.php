<?php

namespace App\Services\Account;

use App\Enums\Role;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use Throwable;

class InstructorApplications
{
    public function snapshot(InstructorApplication $application, bool $newSubmission = false): void
    {
        DB::table('instructor_application_versions')->insertOrIgnore([
            'instructor_application_id' => $application->id, 'application_version' => $application->record_version ?? 1,
            ...$application->only(['institution_name', 'specialization', 'credential_disk', 'credential_path',
                'verification_status', 'verification_notes', 'reviewed_by', 'verified_at']), 'created_at' => $newSubmission ? now() : ($application->created_at ?? now()),
        ]);
    }

    public function submit(User $actor, string $sessionId, array $data, UploadedFile $credential): void
    {
        $disk = config('account.credentials.disk');
        $path = null;
        try {
            if ($disk === 'public' || config('filesystems.disks.'.$disk.'.visibility') === 'public') {
                throw new LogicException('Instructor credentials require private storage.');
            }
            $path = Storage::disk($disk)->putFile('instructor-credentials', $credential, ['visibility' => 'private']);
            if (! is_string($path)) {
                throw new RuntimeException('Credentials could not be stored.');
            }
            DB::transaction(function () use ($actor, $sessionId, $data, $disk, $path): void {
                $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                if (! in_array($user->account_role, [Role::Learner, Role::Contributor], true)) {
                    throw new AuthorizationException('Learners and Contributors may apply for Instructor access.');
                }
                if (DB::table('contributor_applications')->where('user_id', $user->id)->where('approval_status', 'Pending')->exists()) {
                    throw ValidationException::withMessages(['record_version' => 'Your Contributor application is awaiting review. Wait for its decision before applying for Instructor access.']);
                }
                $application = InstructorApplication::where('user_id', $user->id)->lockForUpdate()->first();
                if (($application?->record_version ?? 0) !== (int) $data['record_version']
                    || ($application !== null && $application->verification_status !== 'Rejected')) {
                    throw ValidationException::withMessages(['record_version' => 'Your application changed or is already awaiting review. Reload its status.']);
                }
                if ($application !== null && ! DB::table('instructor_application_versions')->where('instructor_application_id', $application->id)->exists()) {
                    $this->snapshot($application);
                }
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                $attributes = ['institution_name' => $data['institution_name'], 'specialization' => $data['specialization'],
                    'credential_disk' => $disk, 'credential_path' => $path, 'verification_status' => 'Pending',
                    'verification_notes' => null, 'reviewed_by' => null, 'verified_at' => null,
                    'record_version' => ($application?->record_version ?? 0) + 1];
                if ($application === null) {
                    $application = InstructorApplication::create(['user_id' => $user->id, ...$attributes]);
                } else {
                    $application->update($attributes);
                }
                $this->snapshot($application, true);
                app(AuditRecorder::class)->record($user->id, $user->id, 'instructor_application.submitted', 'instructor_application', (string) $application->id,
                    ['version' => $application->record_version]);
            });
        } catch (Throwable $exception) {
            if (is_string($path)) {
                try {
                    if (! Storage::disk($disk)->delete($path)) {
                        Log::warning('Instructor credential cleanup failed.');
                    }
                } catch (Throwable) {
                    Log::warning('Instructor credential cleanup failed.');
                }
            }
            if ($exception instanceof QueryException) {
                Log::error('Instructor application write failed.', ['sqlstate' => $exception->getCode()]);
                throw new RuntimeException('Your application could not be saved. Please try again.');
            }
            throw $exception;
        }
    }
}
