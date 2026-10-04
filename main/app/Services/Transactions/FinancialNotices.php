<?php

namespace App\Services\Transactions;

use App\Jobs\Transactions\SendFinancialNotice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use LogicException;

class FinancialNotices
{
    public function queue(int $userId, string $reference, string $title, array $details): void
    {
        if (DB::transactionLevel() === 0 || (config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
            throw new LogicException('Financial notices require the application database transaction and queue.');
        }
        if (DB::table('financial_deliveries')->where('reference', $reference)->exists()) {
            return;
        }
        $id = (string) Str::uuid();
        DB::table('financial_deliveries')->insert(['id' => $id, 'user_id' => $userId, 'reference' => $reference,
            'title' => $title, 'details' => json_encode($details, JSON_THROW_ON_ERROR), 'last_queued_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('notifications')->insert(['id' => $id, 'type' => 'financial.notice', 'notifiable_type' => User::class,
            'notifiable_id' => $userId, 'data' => json_encode(['title' => $title, 'decision' => 'Open your wallet or earnings history for the verified details.'], JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now()]);
        Queue::connection('database')->pushOn('notifications', (new SendFinancialNotice($id))->beforeCommit());
    }
}
