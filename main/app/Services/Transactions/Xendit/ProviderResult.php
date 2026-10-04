<?php

namespace App\Services\Transactions\Xendit;

readonly class ProviderResult
{
    public function __construct(
        public string $id,
        public string $reference,
        public string $status,
        public int $amountMinor,
        public string $currency,
        public ?string $businessId = null,
        public ?string $checkout = null,
        public ?string $paymentId = null,
        public array $captures = [],
        public array $recipient = [],
        public ?string $paymentRequestId = null,
        public ?int $destinationMinor = null,
    ) {}
}
