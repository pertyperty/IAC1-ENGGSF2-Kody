<?php

namespace App\Mail\Account;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VerificationLink extends Mailable
{
    public function __construct(public readonly string $verificationUrl, public readonly string $verificationToken) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your Kody email');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.verify-email');
    }
}
