<?php

namespace App\Services\Account;

use App\Jobs\Account\EraseAccountFile;
use App\Models\AccountFileErasure;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use LogicException;

class PrivateFileRemoval
{
    public function queue(int $ownerId, string $disk, string $path): void
    {
        if ((config('queue.connections.database.connection') ?? config('database.default')) !== config('database.default')) {
            throw new LogicException('Private file erasure requires the application database queue.');
        }
        $erasure = AccountFileErasure::firstOrCreate(['user_id' => $ownerId, 'disk' => $disk, 'path_digest' => hash('sha256', $path)],
            ['id' => (string) Str::uuid(), 'path' => $path, 'last_queued_at' => now()]);
        if ($erasure->wasRecentlyCreated) {
            $id = Queue::connection('database')->push((new EraseAccountFile($erasure->id))->beforeCommit());
            $erasure->update(['queued_job_id' => $id]);
        }
    }
}
