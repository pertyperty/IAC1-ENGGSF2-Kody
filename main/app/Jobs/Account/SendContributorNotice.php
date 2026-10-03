<?php

namespace App\Jobs\Account;

use App\Enums\AccountStatus;
use App\Mail\Account\ContributorNotice;
use App\Models\ContributorApplication;
use App\Models\User;
use App\Services\Account\SecureAccountMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Throwable;

class SendContributorNotice implements ShouldQueue
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
        $initial = DB::table('contributor_notice_deliveries')->where('id', $this->deliveryId)->first();
        $application = $initial === null ? null : ContributorApplication::find($initial->application_id);
        if ($application === null) {
            return;
        }
        $failed = DB::transaction(function () use ($initial, $application): bool {
            $users = User::whereIn('id', [$initial->recipient_id, $application->user_id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $recipient = $users->get($initial->recipient_id);
            $applicant = $users->get($application->user_id);
            $delivery = DB::table('contributor_notice_deliveries')->where('id', $this->deliveryId)->lockForUpdate()->first();
            if ($delivery === null || $delivery->sent_at !== null || $delivery->cancelled_at !== null) {
                return false;
            }
            $current = $application->fresh();
            if ($current === null || $recipient === null || $recipient->account_status !== AccountStatus::Active || $recipient->email_verified_at === null
                || $applicant === null || $applicant->account_status === AccountStatus::Deleted
                || ($delivery->kind === 'Submitted' && ! Gate::forUser($recipient)->allows('view', $current))) {
                DB::table('contributor_notice_deliveries')->where('id', $this->deliveryId)->update(['cancelled_at' => now(), 'updated_at' => now()]);

                return false;
            }
            if ($delivery->notified_at === null) {
                DB::table('notifications')->insert(['id' => $this->deliveryId, 'type' => 'contributor.application',
                    'notifiable_type' => User::class, 'notifiable_id' => $recipient->id,
                    'data' => json_encode(['application_id' => $current->id, 'title' => 'Contributor application', 'decision' => $delivery->kind,
                        'reviewer' => $delivery->kind === 'Submitted'], JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
                DB::table('contributor_notice_deliveries')->where('id', $this->deliveryId)->update(['notified_at' => now()]);
            }
            try {
                app(SecureAccountMailer::class)->send('notifications', $recipient->email, new ContributorNotice($delivery->kind));
                DB::table('contributor_notice_deliveries')->where('id', $this->deliveryId)->update(['sent_at' => now(), 'failed_at' => null, 'updated_at' => now()]);

                return false;
            } catch (Throwable) {
                DB::table('contributor_notice_deliveries')->where('id', $this->deliveryId)->update(['failed_at' => now(), 'updated_at' => now()]);

                return true;
            }
        });
        if ($failed) {
            throw new RuntimeException('Contributor application notice could not be delivered.');
        }
    }
}
