<?php

namespace App\Services\Challenges;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubmissionStorage
{
    public function transaction(callable $operation): mixed
    {
        try {
            return DB::transaction($operation);
        } catch (QueryException $exception) {
            Log::error('Submission storage write failed.', ['sqlstate' => $exception->getCode()]);
            throw new RuntimeException('Submission storage is temporarily unavailable.');
        }
    }
}
