<?php

namespace App\Support;

class AccountIdentity
{
    /** Private optional name, with a nonempty username fallback for legacy consumers. */
    public static function name(?string $first, ?string $last, string $username): string
    {
        return implode(' ', array_filter([$first, $last], fn ($part) => is_string($part) && $part !== '')) ?: $username;
    }
}
