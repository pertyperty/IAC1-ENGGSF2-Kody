<?php

namespace App\Services\Games;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PuzzleGames
{
    public const TEMPLATES = ['logic-switchboard', 'string-forge', 'memory-trail'];

    public function validate(string $template, array $scenario): void
    {
        $rules = match ($template) {
            'logic-switchboard' => ['scenario' => ['required', 'array:inputs,gate,target'], 'scenario.inputs' => ['required', 'array', 'list', 'min:2', 'max:4'],
                'scenario.inputs.*' => ['boolean'], 'scenario.gate' => ['required', 'in:and,or,xor'], 'scenario.target' => ['required', 'boolean']],
            'string-forge' => ['scenario' => ['required', 'array:start,target'], 'scenario.start' => ['required', 'string', 'regex:/^[A-Za-z0-9 _-]{1,40}$/D'],
                'scenario.target' => ['required', 'string', 'regex:/^[A-Za-z0-9 _-]{1,40}$/D']],
            'memory-trail' => ['scenario' => ['required', 'array:start,target'], 'scenario.start' => ['present', 'array', 'list', 'max:6'],
                'scenario.target' => ['present', 'array', 'list', 'max:6'], 'scenario.start.*' => ['integer', 'between:0,9'], 'scenario.target.*' => ['integer', 'between:0,9']],
        };
        Validator::make(['scenario' => $scenario], $rules)->validate();
        $values = $template === 'logic-switchboard' ? [...$scenario['inputs'], $scenario['target']]
            : ($template === 'memory-trail' ? [...$scenario['start'], ...$scenario['target']] : []);
        foreach ($values as $value) {
            if ($template === 'logic-switchboard' ? ! is_bool($value) : ! is_int($value)) {
                $this->invalid();
            }
        }
        if ($template === 'string-forge') {
            $candidates = [$scenario['start']];
            for ($i = 0; $i < 4; $i++) {
                foreach ($candidates as $candidate) {
                    array_push($candidates, strtoupper($candidate), strtolower($candidate), strrev($candidate), trim($candidate));
                }
                $candidates = array_values(array_unique($candidates));
            }
            if (! array_any($candidates, fn ($value) => str_starts_with($scenario['target'], $value) && preg_match('/^[A-Za-z0-9_-]*$/D', substr($scenario['target'], strlen($value))))) {
                $this->invalid();
            }
        }
    }

    public function succeeds(array $game, array $program): bool
    {
        if (! array_is_list($program) || count($program) < 1 || count($program) > 12) {
            return false;
        }
        $scenario = $game['scenario'];
        $state = $game['template'] === 'logic-switchboard' ? $scenario['inputs'] : $scenario['start'];
        foreach ($program as $command) {
            if (! is_string($command) || strlen($command) > 100 || trim($command) !== $command) {
                return false;
            }
            if ($game['template'] === 'logic-switchboard') {
                if (! preg_match('/^flip ([1-4])$/D', $command, $match) || ! isset($state[$match[1] - 1])) {
                    return false;
                }
                $state[$match[1] - 1] = ! $state[$match[1] - 1];
            } elseif ($game['template'] === 'string-forge') {
                $state = match ($command) {
                    'upper' => strtoupper($state), 'lower' => strtolower($state), 'reverse' => strrev($state), 'trim' => trim($state),
                    default => preg_match('/^append ([A-Za-z0-9_-]{1,12})$/D', $command, $match) ? $state.$match[1] : null,
                };
                if ($state === null || strlen($state) > 100) {
                    return false;
                }
            } else {
                if (preg_match('/^push ([0-9])$/D', $command, $match) && count($state) < 6) {
                    $state[] = (int) $match[1];
                } elseif ($command === 'pop' && $state !== []) {
                    array_pop($state);
                } elseif ($command === 'duplicate' && $state !== [] && count($state) < 6) {
                    $state[] = end($state);
                } elseif ($command === 'swap' && count($state) >= 2) {
                    $last = count($state) - 1;
                    [$state[$last], $state[$last - 1]] = [$state[$last - 1], $state[$last]];
                } else {
                    return false;
                }
            }
        }
        if ($game['template'] === 'logic-switchboard') {
            $on = count(array_filter($state));
            $value = match ($scenario['gate']) {
                'and' => $on === count($state), 'or' => $on > 0, 'xor' => $on % 2 === 1
            };

            return $value === $scenario['target'];
        }

        return $state === $scenario['target'];
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['game_scenario' => 'Use typed values and a reachable objective. Text targets must be reachable by case changes, reversal, trimming and appending up to 12 letters, digits, underscores or hyphens at a time.']);
    }
}
