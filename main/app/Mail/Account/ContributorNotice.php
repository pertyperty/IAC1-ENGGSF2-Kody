<?php

namespace App\Mail\Account;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContributorNotice extends Mailable
{
    public function __construct(public readonly string $kind) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kody Contributor application update');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contributor-notice');
    }
}
