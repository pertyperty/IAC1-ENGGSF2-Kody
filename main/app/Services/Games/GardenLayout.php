<?php

namespace App\Services\Games;

use Illuminate\Validation\ValidationException;
use JsonException;

class GardenLayout
{
    public function instance(array $data): array
    {
        $instance = config('learning.instances')[$data['game_preset']];
        if (! isset($data['game_layout']) || $data['game_layout'] === '') {
            return $instance;
        }
        try {
            if (! is_string($data['game_layout']) || strlen($data['game_layout']) > 4000) {
                $this->invalid();
            }
            $layout = json_decode($data['game_layout'], true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->invalid();
        }
        if (! is_array($layout) || array_diff(array_keys($layout), ['start', 'goal', 'path', 'crystals']) !== []
            || count($layout) !== 4 || ! $this->point($layout['start'] ?? null, $instance) || ! $this->point($layout['goal'] ?? null, $instance)
            || $layout['start'] === $layout['goal']) {
            $this->invalid();
        }
        foreach (['path' => 20, 'crystals' => 4] as $field => $limit) {
            if (! is_array($layout[$field] ?? null) || ! array_is_list($layout[$field]) || count($layout[$field]) > $limit) {
                $this->invalid();
            }
            $keys = [];
            foreach ($layout[$field] as $point) {
                if (! $this->point($point, $instance) || in_array(implode(',', $point), $keys, true)) {
                    $this->invalid();
                }
                $keys[] = implode(',', $point);
            }
        }
        if (! in_array($layout['start'], $layout['path'], true) || ! in_array($layout['goal'], $layout['path'], true)
            || in_array($layout['start'], $layout['crystals'], true)
            || ($instance['mode'] !== 'conditional' && $layout['crystals'] !== [])) {
            $this->invalid();
        }
        foreach ($layout['crystals'] as $crystal) {
            if (! in_array($crystal, $layout['path'], true)) {
                $this->invalid();
            }
        }
        $instance = array_replace($instance, $layout);
        if ($this->solution($instance) === null) {
            throw ValidationException::withMessages(['game_layout' => 'This trail cannot be completed within its instruction limit. Connect the start, goal and crystals, then try again.']);
        }

        return $instance;
    }

    /** Bounded search: 20 tiles x 16 crystal sets; loop patterns use at most three instructions. */
    public function solution(array $instance): ?array
    {
        $moves = ['up' => [0, -1], 'down' => [0, 1], 'left' => [-1, 0], 'right' => [1, 0]];
        $queue = [[$instance['start'], [], $instance['crystals']]];
        $visited = [];
        for ($index = 0; $index < count($queue); $index++) {
            [$position, $program, $remaining] = $queue[$index];
            if ($program !== [] && app(CommandGarden::class)->succeeds($instance, $program, true, true)) {
                return $program;
            }
            if (count($program) >= $instance['maxCommands']) {
                continue;
            }
            foreach ($moves as $command => $move) {
                $next = [$position[0] + $move[0], $position[1] + $move[1]];
                if (! in_array($next, $instance['path'], true)) {
                    continue;
                }
                $crystals = array_values(array_filter($remaining, fn (array $point) => $point !== $next));
                $key = json_encode([$next, $crystals], JSON_THROW_ON_ERROR);
                // Loop patterns must stay distinct: equal intermediate positions can repeat differently.
                if ($instance['mode'] !== 'loop' && isset($visited[$key])) {
                    continue;
                }
                $visited[$key] = true;
                $queue[] = [$next, [...$program, $command], $crystals];
            }
        }

        return null;
    }

    private function point(mixed $point, array $instance): bool
    {
        return is_array($point) && array_is_list($point) && count($point) === 2 && is_int($point[0]) && is_int($point[1])
            && $point[0] >= 0 && $point[0] < $instance['width'] && $point[1] >= 0 && $point[1] < $instance['height'];
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['game_layout' => 'Use a valid garden layout with distinct start and goal, connected path tiles and up to four crystals.']);
    }
}
