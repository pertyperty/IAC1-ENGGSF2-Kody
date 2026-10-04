<?php

namespace App\Jobs\Transactions;

use App\Services\Transactions\Payments;
use App\Services\Transactions\PublisherSettlements;
use App\Services\Transactions\Refunds;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateProviderOperation implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(public string $kind, public string $operationId) {}

    public function handle(): void
    {
        match ($this->kind) {
            'payment' => app(Payments::class)->create($this->operationId),
            'payout' => app(PublisherSettlements::class)->create($this->operationId),
            'refund' => app(Refunds::class)->create($this->operationId),
            default => throw new \LogicException('Unknown financial operation.'),
        };
    }
}
