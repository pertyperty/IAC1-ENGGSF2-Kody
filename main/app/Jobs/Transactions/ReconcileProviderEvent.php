<?php

namespace App\Jobs\Transactions;

use App\Services\Transactions\ProviderWebhooks;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReconcileProviderEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 45;

    public function __construct(public string $eventId) {}

    public function backoff(): array
    {
        return [15, 60];
    }

    public function handle(ProviderWebhooks $webhooks): void
    {
        $webhooks->reconcile($this->eventId);
    }
}
