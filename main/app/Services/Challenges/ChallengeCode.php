<?php

namespace App\Services\Challenges;

class ChallengeCode
{
    public static function valid(mixed $code): bool
    {
        return is_string($code) && strlen($code) <= config('judge0.source_bytes')
            && mb_check_encoding($code, 'UTF-8') && ! str_contains($code, "\0");
    }
}
