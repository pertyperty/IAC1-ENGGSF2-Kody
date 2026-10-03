<?php

namespace App\Mail\Account;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SupportCorrectionNotice extends Mailable
{
    public function __construct(public readonly array $fields, public readonly string $occurredAt) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kody account support correction');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.support-correction-notice');
    }
}
