<?php

namespace App\Mail\Account;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ModeratorNotice extends Mailable
{
    public function __construct(public readonly string $action, public readonly string $resultingRole, public readonly string $occurredAt) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kody Moderator appointment update');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.moderator-notice');
    }
}
