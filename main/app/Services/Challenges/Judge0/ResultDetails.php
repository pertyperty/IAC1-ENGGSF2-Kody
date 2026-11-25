<?php

namespace App\Services\Challenges\Judge0;

final class ResultDetails
{
    public static function fromResponse(array $data, int $status, ?string $publicLanguage, int $lineCount): array
    {
        $details = [];
        if ($status === 3) {
            $time = $data['time'] ?? null;
            if ((is_string($time) || is_float($time) || is_int($time)) && is_numeric($time) && (float) $time >= 0 && (float) $time <= 60) {
                $details['cpu_ms'] = (int) round((float) $time * 1000);
            }
            $memory = $data['memory'] ?? null;
            if (is_int($memory) && $memory > 0 && $memory <= 1024 * 1024) {
                $details['memory_kib'] = $memory;
            }
        }
        if ($publicLanguage === null) {
            return $details;
        }
        if ($status === 4) {
            $output = self::decode($data['stdout'] ?? null, 8192);
            if ($output !== null) {
                $details['output'] = mb_strcut(preg_replace('/[^\P{C}\r\n\t]/u', "\u{FFFD}", $output) ?? '', 0, 1000, 'UTF-8');
                $details['output_truncated'] = strlen($output) > strlen($details['output']) || strlen($details['output']) === 1000;
            }
        }
        if ($status === 6 || ($status >= 7 && $status <= 12)) {
            $text = self::decode($data[$status === 6 ? 'compile_output' : 'stderr'] ?? null, 16384) ?? '';
            // Only retain locations in the submitted file. Never retain provider error text.
            $pattern = match ($publicLanguage) {
                'java' => $status === 6 ? '/^Main\.java:(\d{1,6}): error:/m' : '/\bat Main\.\w+\(Main\.java:(\d{1,6})\)/',
                'cpp' => '/^(?:\.\/)?main\.(?:cpp|cc):(\d{1,6}):\d{1,6}: (?:fatal )?error:/m',
                'python' => '/^\s*File "(?:\.\/)?(?:script|main)\.py", line (\d{1,6})\b/m',
                default => null,
            };
            if ($pattern !== null && preg_match_all($pattern, $text, $matches)) {
                $lines = array_map('intval', $matches[1]);
                $details['lines'] = array_slice(array_values(array_unique(array_filter($lines, fn (int $line) => $line > 0 && $line <= $lineCount))), 0, 5);
            }
        }

        return $details;
    }

    private static function decode(mixed $value, int $limit): ?string
    {
        if (! is_string($value) || strlen($value) > 4 * (int) ceil($limit / 3)) {
            return null;
        }
        $text = base64_decode($value, true);

        return $text !== false && strlen($text) <= $limit && mb_check_encoding($text, 'UTF-8') ? $text : null;
    }
}
