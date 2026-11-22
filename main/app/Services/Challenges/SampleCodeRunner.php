<?php

namespace App\Services\Challenges;

interface SampleCodeRunner
{
    /**
     * @param  list<array{position: int, input: string, expected_output: string}>  $samples
     * @return list<bool> Verdicts in the supplied order; infrastructure failures are not failed answers.
     */
    public function run(string $source, string $language, int $cpuTimeMs, int $memoryKib, array $samples): array;
}
