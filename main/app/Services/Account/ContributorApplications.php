<?php

namespace App\Services\Account;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\SendContributorNotice;
use App\Models\ContributorApplication;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Throwable;

class ContributorApplications
{
    public function submit(User $actor, string $sessionId, array $data, UploadedFile $credential): void
    {
        $disk = config('account.credentials.disk');
        $path = null;
        try {
            if ($disk === 'public' || config('filesystems.disks.'.$disk.'.visibility') === 'public') {
                throw new LogicException('Contributor credentials require private storage.');
            }
            $path = Storage::disk($disk)->putFile('contributor-credentials', $credential, ['visibility' => 'private']);
            if (! is_string($path)) {
                throw new \RuntimeException('Credentials could not be stored.');
            }
            DB::transaction(function () use ($actor, $sessionId, $data, $disk, $path): void {
                $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                Gate::forUser($user)->authorize('create', ContributorApplication::class);
                $latest = ContributorApplication::where('user_id', $user->id)->latest('id')->first();
                if (($latest?->id ?? 0) !== (int) $data['previous_application_id'] || ($latest !== null && $latest->approval_status !== 'Rejected')
                    || DB::table('instructor_applications')->where('user_id', $user->id)->where('verification_status', 'Pending')->exists()) {
                    throw ValidationException::withMessages(['application' => 'Your role application changed or is awaiting review. Reload its status.']);
                }
                $eligibility = app(ContributorEligibility::class)->snapshot($user);
                if (! $eligibility['eligible']) {
                    throw ValidationException::withMessages(['application' => 'Complete 25 distinct modules and pass 50 distinct coding challenges with an account at least 30 days old.']);
                }
                unset($eligibility['eligible']);
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                $application = ContributorApplication::create(['user_id' => $user->id, ...$eligibility,
                    'request_message' => $data['request_message'], 'portfolio_link' => $data['portfolio_link'] ?? null,
                    'credential_disk' => $disk, 'credential_path' => $path]);
                app(AuditRecorder::class)->record($user->id, $user->id, 'contributor_application.submitted', 'contributor_application', (string) $application->id);
                User::where('account_status', AccountStatus::Active)->whereNotNull('email_verified_at')
                    ->whereIn('account_role', [Role::Moderator, Role::Administrator])->chunkById(100, function ($reviewers) use ($application): void {
                        foreach ($reviewers as $reviewer) {
                            $this->notice($application, $reviewer->id, 'Submitted');
                        }
                    });
            });
        } catch (Throwable $exception) {
            if (is_string($path)) {
                try {
                    if (! Storage::disk($disk)->delete($path)) {
                        Log::warning('Contributor credential cleanup failed.');
                    }
                } catch (Throwable) {
                    Log::warning('Contributor credential cleanup failed.');
                }
            }
            if ($exception instanceof QueryException) {
                Log::error('Contributor application write failed.', ['sqlstate' => $exception->getCode()]);
                throw new \RuntimeException('Your application could not be saved. Please try again.');
            }
            throw $exception;
        }
    }

    public function review(User $actor, string $sessionId, ContributorApplication $application, array $data): void
    {
        DB::transaction(function () use ($actor, $sessionId, $application, $data): void {
            $accounts = User::whereIn('id', [$actor->id, $application->user_id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $reviewer = $accounts->get($actor->id);
            $applicant = $accounts->get($application->user_id);
            app(CurrentAccountSession::class)->assert($reviewer, $sessionId);
            $current = ContributorApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($reviewer)->authorize('review', $current);
            if ($current->approval_status !== 'Pending' || $current->record_version !== (int) $data['record_version']) {
                throw ValidationException::withMessages(['decision' => 'This application was already decided or changed. Reload before reviewing.']);
            }
            $decision = $data['decision'];
            if (! in_array($decision, ['Approved', 'Rejected'], true) || ! in_array($data['confirmed'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)) {
                throw ValidationException::withMessages(['decision' => 'Confirm a valid decision.']);
            }
            if ($decision === 'Approved' && ! app(ContributorEligibility::class)->snapshot($applicant)['eligible']) {
                throw ValidationException::withMessages(['decision' => 'The applicant must still have eligible verified Active Learner access.']);
            }
            $previousRole = $applicant->account_role;
            $current->update(['approval_status' => $decision, 'moderator_feedback' => $data['moderator_feedback'] ?? null,
                'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'record_version' => $current->record_version + 1]);
            if ($decision === 'Approved') {
                $applicant->forceFill(['account_role' => Role::Contributor])->save();
            }
            app(AuditRecorder::class)->record($reviewer->id, $applicant->id, 'contributor_application.reviewed', 'contributor_application', (string) $current->id,
                ['decision' => $decision, 'version' => $current->record_version, 'previous_role' => $previousRole->value, 'resulting_role' => $applicant->account_role->value]);
            $this->notice($current, $applicant->id, $decision);
        });
    }

    private function notice(ContributorApplication $application, int $recipientId, string $kind): void
    {
        if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
            throw new LogicException('Contributor notifications require the application database queue.');
        }
        $id = (string) Str::uuid();
        DB::table('contributor_notice_deliveries')->insert(['id' => $id, 'application_id' => $application->id,
            'recipient_id' => $recipientId, 'kind' => $kind, 'created_at' => now(), 'updated_at' => now()]);
        Queue::connection('database')->push((new SendContributorNotice($id))->beforeCommit());
    }
}
