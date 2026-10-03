<?php

namespace App\Jobs\Account;

use App\Models\AccountFileErasure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class EraseAccountFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly string $erasureId) {}

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        $failed = DB::transaction(function (): bool {
            $erasure = AccountFileErasure::whereKey($this->erasureId)->lockForUpdate()->first();
            if ($erasure === null || $erasure->completed_at !== null) {
                return false;
            }
            try {
                $path = $erasure->path;
                if (! is_string($path) || ! str_starts_with($path, 'instructor-credentials/') || str_contains($path, '..') || str_contains($path, '\\')
                    || $erasure->disk === 'public' || ! is_array(config('filesystems.disks.'.$erasure->disk))
                    || config('filesystems.disks.'.$erasure->disk.'.visibility') === 'public') {
                    throw new RuntimeException('Invalid private erasure target.');
                }
                if (! Storage::disk($erasure->disk)->delete($path)) {
                    throw new RuntimeException('Private object erasure failed.');
                }
                $erasure->update(['path' => null, 'completed_at' => now(), 'failed_at' => null, 'attempts' => $erasure->attempts + 1]);

                return false;
            } catch (Throwable) {
                $erasure->update(['failed_at' => now(), 'attempts' => $erasure->attempts + 1]);
                Log::warning('Private account file erasure failed.', ['erasure_id' => $erasure->id]);

                return true;
            }
        });
        if ($failed) {
            throw new RuntimeException('Private account file erasure could not be completed.');
        }
    }
}
