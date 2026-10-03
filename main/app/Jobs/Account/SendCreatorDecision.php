<?php

namespace App\Jobs\Account;

use App\Enums\AccountStatus;
use App\Mail\Account\CreatorDecision;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\Account\SecureAccountMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SendCreatorDecision implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly string $deliveryId) {}

    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(): void
    {
        $applicationId = DB::table('creator_decision_deliveries')->where('id', $this->deliveryId)->value('instructor_application_id');
        $application = $applicationId === null ? null : InstructorApplication::find($applicationId);
        if ($application === null) {
            return;
        }
        $failed = DB::transaction(function () use ($application): bool {
            $user = User::whereKey($application->user_id)->lockForUpdate()->first();
            $delivery = DB::table('creator_decision_deliveries')->where('id', $this->deliveryId)->lockForUpdate()->first();
            if ($delivery === null || $delivery->sent_at !== null || $delivery->cancelled_at !== null) {
                return false;
            }
            $current = $application->fresh();
            if ($user === null || $user->account_status === AccountStatus::Deleted || $user->email !== $delivery->recipient_email
                || $current === null || $current->record_version !== $delivery->review_version) {
                DB::table('creator_decision_deliveries')->where('id', $this->deliveryId)->update(['cancelled_at' => now(), 'updated_at' => now()]);

                return false;
            }
            try {
                app(SecureAccountMailer::class)->send('notifications', $user->email, new CreatorDecision($current->verification_status, $current->verification_notes));
                DB::table('creator_decision_deliveries')->where('id', $this->deliveryId)->update(['sent_at' => now(), 'failed_at' => null, 'updated_at' => now()]);

                return false;
            } catch (Throwable) {
                DB::table('creator_decision_deliveries')->where('id', $this->deliveryId)->update(['failed_at' => now(), 'updated_at' => now()]);

                return true;
            }
        });
        if ($failed) {
            throw new RuntimeException('Creator decision email could not be delivered.');
        }
    }
}
