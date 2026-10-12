<?php

namespace App\Mail\Transactions;

use App\Services\Documents\PdfDocuments;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
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

    public function attachments(): array
    {
        if (! isset($this->details['purchase_reference'], $this->details['confirmed_at'], $this->details['kodebits'])) {
            return [];
        }
        $receipt = ['id' => $this->receiptId, 'title' => $this->receiptTitle, 'details' => $this->details,
            'issued_at' => $this->details['confirmed_at'], 'purchase_reference' => $this->details['purchase_reference']];

        return [Attachment::fromData(fn () => app(PdfDocuments::class)->render('transactions.receipt-pdf', compact('receipt')), 'kody-payment-receipt.pdf')->withMime('application/pdf')];
    }
}
