<?php

namespace App\Services\Account;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class SecureAccountMailer
{
    public function send(string $purpose, string $email, Mailable $message): void
    {
        $mailer = config('account.'.$purpose.'.mailer');
        $transport = config('mail.mailers.'.$mailer.'.transport');
        if ($transport !== 'smtp' && ! (app()->environment('testing') && $transport === 'array')) {
            throw new RuntimeException('Account security mail requires a non-logging transport.');
        }
        Mail::mailer($mailer)->to($email)->send($message);
    }
}
