<?php

namespace App\Services\Challenges\Judge0;

final readonly class EvaluationResult
{
    public function __construct(public int $status) {}

    public function pending(): bool
    {
        return $this->status <= 2;
    }

    public function outcome(): string
    {
        return match ($this->status) {
            3 => 'Passed',
            4, 5, 6, 7, 8, 9, 10, 11, 12, 14 => 'Failed',
            default => 'Unavailable',
        };
    }
}
