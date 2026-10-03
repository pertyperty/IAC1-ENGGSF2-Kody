<?php

namespace App\Mail\Account;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RecoveryLink extends Mailable
{
    public function __construct(public readonly string $recoveryUrl, public readonly string $recoveryToken) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recover your Kody account');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.recover-account');
    }
}
