<?php

namespace App\Mail\Account;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CreatorDecision extends Mailable
{
    public function __construct(public readonly string $decision, public readonly ?string $notes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Kody instructor application');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.creator-decision');
    }
}
