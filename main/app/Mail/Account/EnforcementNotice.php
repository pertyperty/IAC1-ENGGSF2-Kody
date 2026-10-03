<?php

namespace App\Mail\Account;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EnforcementNotice extends Mailable
{
    public function __construct(public readonly string $action, public readonly string $occurredAt) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kody account enforcement update');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.enforcement-notice');
    }
}
