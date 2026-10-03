<?php

namespace App\Console\Commands;

use App\Jobs\Account\EraseAccountFile;
use App\Models\AccountFileErasure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use LogicException;

class RetryAccountErasures extends Command
{
    protected $signature = 'kody:account-erasures-retry';

    protected $description = 'Requeue unfinished private credential erasures';

    public function handle(): int
    {
        if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
            throw new LogicException('Account erasure requires the application database queue.');
        }
        DB::transaction(function (): void {
            AccountFileErasure::whereNull('completed_at')->where('last_queued_at', '<=', now()->subMinutes(5))
                ->where(fn ($query) => $query->whereNull('queued_job_id')->orWhereNotExists(fn ($jobs) => $jobs
                    ->selectRaw('1')->from(config('queue.connections.database.table'))->whereColumn('id', 'account_file_erasures.queued_job_id')))
                ->orderBy('id')->lockForUpdate()->limit(100)->get()
                ->each(function (AccountFileErasure $erasure): void {
                    $jobId = Queue::connection('database')->push((new EraseAccountFile($erasure->id))->beforeCommit());
                    $erasure->update(['last_queued_at' => now(), 'queued_job_id' => $jobId]);
                });
        });

        return self::SUCCESS;
    }
}
