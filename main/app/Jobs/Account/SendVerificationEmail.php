<?php

namespace App\Jobs\Account;

use App\Enums\AccountStatus;
use App\Mail\Account\VerificationLink;
use App\Models\EmailVerification;
use App\Models\User;
use App\Models\VerificationDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendVerificationEmail implements ShouldQueue
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
        $accountId = VerificationDelivery::whereKey($this->deliveryId)->value('user_id');
        if ($accountId === null) {
            return;
        }

        $failed = DB::transaction(function () use ($accountId): bool {
            $user = User::whereKey($accountId)->lockForUpdate()->first();
            $delivery = VerificationDelivery::whereKey($this->deliveryId)->lockForUpdate()->first();
            $verification = EmailVerification::where('user_id', $accountId)->first();

            if ($delivery === null || $delivery->sent_at !== null || $delivery->cancelled_at !== null) {
                return false;
            }

            if ($user === null || $user->account_status !== AccountStatus::Unverified || $verification === null
                || $verification->email !== $user->email || $verification->token_hash !== $delivery->token_hash
                || $verification->expires_at === null || $verification->expires_at->lessThanOrEqualTo(now())) {
                $delivery->update(['token' => null, 'cancelled_at' => now()]);

                return false;
            }

            try {
                $mailer = config('account.verification.mailer');
                $transport = config('mail.mailers.'.$mailer.'.transport');
                if ($transport !== 'smtp' && ! (app()->environment('testing') && $transport === 'array')) {
                    throw new RuntimeException('Verification mail requires a transport that does not log tokens.');
                }

                // Use the trusted application URL, never a request's Host header.
                $url = rtrim(config('app.url'), '/').route('verification.notice', absolute: false).'#token='.$delivery->token;
                Mail::mailer($mailer)->to($user->email)->send(new VerificationLink($url, $delivery->token));
                $delivery->update(['token' => null, 'sent_at' => now(), 'failed_at' => null]);

                return false;
            } catch (Throwable) {
                $delivery->update(['failed_at' => now()]);

                return true;
            }
        });

        if ($failed) {
            // Never put provider exception text, email bodies or tokens in failed_jobs/logs.
            throw new RuntimeException('Verification email could not be delivered.');
        }
    }
}
