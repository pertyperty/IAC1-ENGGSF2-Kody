<?php

namespace App\Support;

use InvalidArgumentException;

class ExactMoney
{
    public static function minor(mixed $value): int
    {
        // Reject decoded floats. Provider decoding preserves decimal tokens as
        // strings, and every financial calculation uses integer centavos.
        $text = is_string($value) || is_int($value) ? (string) $value : '';
        if (! preg_match('/\A(0|[1-9][0-9]{0,7})(?:\.([0-9]{1,2}))?\z/', $text, $matches)) {
            throw new InvalidArgumentException('Invalid exact monetary amount.');
        }

        return (int) $matches[1] * 100 + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    public static function decimal(int $minor): string
    {
        if ($minor < 0 || $minor > 9999999999) {
            throw new InvalidArgumentException('Invalid monetary range.');
        }

        return intdiv($minor, 100).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function decode(string $json): array
    {
        // Skip JSON strings, including escapes; preserve numeric decimal tokens.
        $json = preg_replace_callback('/"(?:[^"\\\\]|\\\\.)*"(*SKIP)(*F)|-?(?:0|[1-9][0-9]*)\.[0-9]+(?:[eE][+-]?[0-9]+)?/',
            fn (array $match) => '"'.$match[0].'"', $json);
        $data = json_decode($json, true, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (! is_array($data)) {
            throw new InvalidArgumentException('Invalid provider JSON object.');
        }

        return $data;
    }

    public static function milli(int $units): string
    {
        if ($units < 0) {
            throw new InvalidArgumentException('Invalid token amount.');
        }

        return intdiv($units, 1000).'.'.str_pad((string) ($units % 1000), 3, '0', STR_PAD_LEFT);
    }
}
