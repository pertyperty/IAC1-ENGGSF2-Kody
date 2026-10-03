<?php

namespace App\Actions\Account;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\SendCreatorDecision;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class ReviewInstructorApplication
{
    public function handle(User $actor, string $sessionId, InstructorApplication $application, int $version, string $decision, ?string $notes, bool $credibilityReviewed): void
    {
        DB::transaction(function () use ($actor, $sessionId, $application, $version, $decision, $notes, $credibilityReviewed): void {
            // Consistent user ordering prevents two reviews from locking users in reverse order.
            $accounts = User::whereIn('id', [$actor->id, $application->user_id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $reviewer = $accounts->get($actor->id);
            $applicant = $accounts->get($application->user_id);
            $current = InstructorApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            if ($reviewer === null || $reviewer->active_session_hash === null
                || ! hash_equals($reviewer->active_session_hash, hash('sha256', $sessionId)) || ! $reviewer->active_session_expires_at?->isFuture()) {
                throw new AuthorizationException('Use your current signed-in session.');
            }
            Gate::forUser($reviewer)->authorize('review', $current);
            if ($current->verification_status !== 'Pending' || $current->record_version !== $version) {
                throw ValidationException::withMessages(['decision' => 'This application changed or was already reviewed. Reload before reviewing.']);
            }
            if (! $credibilityReviewed || ! in_array($decision, ['Approved', 'Rejected'], true) || ($decision === 'Rejected' && trim($notes ?? '') === '')) {
                throw ValidationException::withMessages(['decision' => 'Choose a valid decision and provide a reason for rejection.']);
            }
            if ($decision === 'Approved' && ($applicant === null || $applicant->account_status !== AccountStatus::Active
                || $applicant->email_verified_at === null || ! in_array($applicant->account_role, [Role::Learner, Role::Contributor], true))) {
                throw ValidationException::withMessages(['decision' => 'The applicant must have verified Active Learner or Contributor access before approval.']);
            }
            $previousRole = $applicant->account_role->value;
            $current->update(['verification_status' => $decision, 'verification_notes' => $notes,
                'reviewed_by' => $reviewer->id, 'verified_at' => now(), 'record_version' => $version + 1]);
            if ($decision === 'Approved') {
                $applicant->forceFill(['account_role' => Role::Instructor])->save();
            }
            app(AuditRecorder::class)->record($reviewer->id, $applicant->id, 'instructor_application.reviewed', 'instructor_application', (string) $current->id,
                ['decision' => $decision, 'version' => $version + 1, 'previous_role' => $previousRole, 'resulting_role' => $applicant->account_role->value]);
            $deliveryId = (string) Str::uuid();
            DB::table('creator_decision_deliveries')->insert(['id' => $deliveryId, 'instructor_application_id' => $current->id,
                'review_version' => $version + 1, 'recipient_email' => $applicant->email, 'created_at' => now(), 'updated_at' => now()]);
            if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
                throw new LogicException('Creator notifications require the application database queue.');
            }
            Queue::connection('database')->push((new SendCreatorDecision($deliveryId))->beforeCommit());
        });
    }
}
