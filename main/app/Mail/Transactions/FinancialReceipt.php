<?php

namespace App\Mail\Transactions;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

class FinancialReceipt extends Mailable
{
    public function __construct(public string $receiptId, public string $receiptTitle, public array $details) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->receiptTitle.' — Kody');
    }

    public function headers(): Headers
    {
        return new Headers(messageId: $this->receiptId.'@receipts.kody.invalid');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.financial-receipt');
    }
}
