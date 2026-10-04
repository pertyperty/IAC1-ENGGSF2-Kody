<?php

namespace App\Services\Games;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;

class ArcadeGames
{
    public function instance(string $template, ?string $json = null): array
    {
        $instance = config('arcade')[$template] ?? null;
        if ($instance === null) {
            $this->invalid();
        }
        $scenario = $instance['scenario'];
        if ($json !== null && $json !== '') {
            if (strlen($json) > 4000) {
                $this->invalid();
            }
            try {
                $scenario = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $this->invalid();
            }
        }
        $rules = match ($template) {
            'pixel-studio' => ['scenario' => ['required', 'array:pixels'], 'scenario.pixels' => ['required', 'array', 'list', 'min:1', 'max:9'],
                'scenario.pixels.*' => ['required', 'string', 'regex:/^[1-3] [1-3] (mint|peach|lavender)$/D']],
            'number-machine' => ['scenario' => ['required', 'array:start,target'], 'scenario.start' => ['required', 'integer', 'between:-100,100'],
                'scenario.target' => ['required', 'integer', 'between:-100,100']],
            'sort-lab' => ['scenario' => ['required', 'array:items'], 'scenario.items' => ['required', 'array', 'list', 'min:2', 'max:6'],
                'scenario.items.*' => ['required', 'integer', 'between:0,99']],
            'terminal-quest' => ['scenario' => ['required', 'array:files,destination,content'], 'scenario.files' => ['required', 'array', 'list', 'min:1', 'max:8'],
                'scenario.files.*' => ['required', 'array:name,content'], 'scenario.files.*.name' => ['required', 'string', 'distinct', 'regex:/^[a-z][a-z0-9_-]{0,19}\.[a-z]{1,5}$/D'],
                'scenario.files.*.content' => ['required', 'string', 'max:300', 'not_regex:/[\r\n]/'], 'scenario.destination' => ['required', 'string', 'regex:/^[a-z][a-z0-9_-]{0,19}\.[a-z]{1,5}$/D'],
                'scenario.content' => ['required', 'string', 'max:300', 'not_regex:/[\r\n]/']],
        };
        Validator::make(['scenario' => $scenario], $rules)->validate();
        // Reject JSON numeric strings/floats: interpreter state uses integer arithmetic.
        $numbers = match ($template) {
            'number-machine' => [$scenario['start'], $scenario['target']], 'sort-lab' => $scenario['items'], default => [],
        };
        foreach ($numbers as $number) {
            if (! is_int($number)) {
                $this->invalid();
            }
        }
        if ($template === 'pixel-studio') {
            $coordinates = array_map(fn ($pixel) => substr($pixel, 0, 3), $scenario['pixels']);
            if (count(array_unique($coordinates)) !== count($coordinates)) {
                $this->invalid();
            }
        }
        if ($template === 'terminal-quest' && (! in_array($scenario['content'], array_column($scenario['files'], 'content'), true)
            || in_array($scenario['destination'], array_column($scenario['files'], 'name'), true))) {
            $this->invalid();
        }

        return array_replace($instance, ['scenario' => $scenario]);
    }

    public function supports(array $instance): bool
    {
        return ($instance['version'] ?? null) === 1 && isset(config('arcade')[$instance['template'] ?? '']);
    }

    public function succeeds(array $instance, array $program): bool
    {
        if (! $this->supports($instance) || ! array_is_list($program) || $program === [] || count($program) > 12) {
            return false;
        }
        $scenario = $instance['scenario'];
        $number = $scenario['start'] ?? 0;
        $items = $scenario['items'] ?? [];
        $pixels = [];
        $files = array_column($scenario['files'] ?? [], 'content', 'name');
        $readDestination = false;
        foreach ($program as $command) {
            if (! is_string($command) || strlen($command) > 100 || trim($command) !== $command) {
                return false;
            }
            switch ($instance['template']) {
                case 'pixel-studio':
                    if (! preg_match('/^paint ([1-3]) ([1-3]) (mint|peach|lavender)$/D', $command, $match)) {
                        return false;
                    }
                    $pixels[$match[1].' '.$match[2]] = $match[3];
                    break;
                case 'number-machine':
                    if (! preg_match('/^(add|subtract|multiply) (-?\d{1,3})$/D', $command, $match) || abs((int) $match[2]) > 100) {
                        return false;
                    }
                    $operand = (int) $match[2];
                    $number = match ($match[1]) {
                        'add' => $number + $operand, 'subtract' => $number - $operand, 'multiply' => $number * $operand
                    };
                    if (abs($number) > 10000) {
                        return false;
                    }
                    break;
                case 'sort-lab':
                    if (! preg_match('/^swap ([1-6]) ([1-6])$/D', $command, $match) || max((int) $match[1], (int) $match[2]) > count($items)) {
                        return false;
                    }
                    [$items[$match[1] - 1], $items[$match[2] - 1]] = [$items[$match[2] - 1], $items[$match[1] - 1]];
                    break;
                case 'terminal-quest':
                    if ($command === 'ls') {
                        break;
                    }
                    if (preg_match('/^cat ([a-z][a-z0-9_-]{0,19}\.[a-z]{1,5})$/D', $command, $match) && isset($files[$match[1]])) {
                        $readDestination = $match[1] === $scenario['destination'] && $files[$match[1]] === $scenario['content'];
                    } elseif (preg_match('/^cp ([a-z][a-z0-9_-]{0,19}\.[a-z]{1,5}) ([a-z][a-z0-9_-]{0,19}\.[a-z]{1,5})$/D', $command, $match) && isset($files[$match[1]])) {
                        $files[$match[2]] = $files[$match[1]];
                        $readDestination = false;
                    } else {
                        return false;
                    }
                    break;
            }
        }
        $targetItems = $scenario['items'] ?? [];
        sort($targetItems, SORT_NUMERIC);
        $targetPixels = [];
        foreach ($scenario['pixels'] ?? [] as $pixel) {
            [$x, $y, $color] = explode(' ', $pixel);
            $targetPixels[$x.' '.$y] = $color;
        }
        ksort($targetPixels);
        ksort($pixels);

        return match ($instance['template']) {
            'pixel-studio' => $pixels === $targetPixels, 'number-machine' => $number === $scenario['target'],
            'sort-lab' => $items === $targetItems, 'terminal-quest' => $readDestination && ($files[$scenario['destination']] ?? null) === $scenario['content'],
        };
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['game_scenario' => 'Check the scenario format and objective. Use the template example; every objective must be reachable.']);
    }
}
