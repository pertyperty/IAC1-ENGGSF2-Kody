<?php

namespace App\Services\Games;

class CommandGarden
{
    public function succeeds(array $instance, array $program, bool $repeat, bool $conditional): bool
    {
        if ($program === [] || count($program) > $instance['maxCommands'] || ($instance['mode'] === 'loop' && ! $repeat)) {
            return false;
        }
        $moves = ['up' => [0, -1], 'down' => [0, 1], 'left' => [-1, 0], 'right' => [1, 0]];
        $position = $instance['start'];
        $crystals = array_map(fn (array $point) => implode(',', $point), $instance['crystals']);
        for ($cycle = 0; $cycle < ($instance['mode'] === 'loop' && $repeat ? 2 : 1); $cycle++) {
            foreach ($program as $command) {
                if (! is_string($command) || ! isset($moves[$command])) {
                    return false;
                }
                $position = [$position[0] + $moves[$command][0], $position[1] + $moves[$command][1]];
                if (! in_array($position, $instance['path'], true)) {
                    return false;
                }
                if ($instance['mode'] === 'conditional' && $conditional) {
                    $crystals = array_diff($crystals, [implode(',', $position)]);
                }
            }
        }

        return $position === $instance['goal'] && $crystals === [];
    }
}
