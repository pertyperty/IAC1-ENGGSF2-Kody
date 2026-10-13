<?php

namespace App\Services\Documents;

use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExportFiles
{
    public function within(Closure $export): string
    {
        // Each request owns a private directory, including the writer's internal files.
        // No dependence on TEMP/TMP and no tempnam fallback to a shared OS directory.
        $directory = storage_path('app/private/report-exports/'.Str::uuid());
        File::makeDirectory($directory, 0700, true);
        try {
            abort_unless(is_writable($directory), 503, 'Report storage is unavailable.');

            return $export($directory);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
