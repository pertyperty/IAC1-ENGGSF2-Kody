<?php

namespace App\Services\Games;

class ArcadePseudocode
{
    public function normalize(string $template, string $command): string
    {
        if (strlen($command) > 100) {
            return $command;
        }

        foreach (config('arcade-pseudocode.'.$template, []) as [$pattern, $canonical]) {
            $normalized = preg_replace('~^'.$pattern.'$~uD', $canonical, $command, 1, $count);
            if ($count === 1 && $normalized !== null) {
                return $normalized;
            }
        }

        // Canonical and unrecognized text still go through the existing command validators.
        return $command;
    }
}
