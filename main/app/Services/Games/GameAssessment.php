<?php

namespace App\Services\Games;

class GameAssessment
{
    public function instance(array $data): array
    {
        return isset(config('arcade')[$data['game_preset']])
            ? app(ArcadeGames::class)->instance($data['game_preset'], $data['game_scenario'] ?? null)
            : app(GardenLayout::class)->instance($data);
    }

    public function supports(array $instance): bool
    {
        return (($instance['template'] ?? null) === 'command-garden' && ($instance['version'] ?? null) === 1)
            || app(ArcadeGames::class)->supports($instance);
    }

    public function succeeds(array $instance, array $input): bool
    {
        return ($instance['template'] ?? null) === 'command-garden'
            ? app(CommandGarden::class)->succeeds($instance, $input['program'], $input['repeat'] ?? false, $input['conditional'] ?? false)
            : app(ArcadeGames::class)->succeeds($instance, $input['program']);
    }
}
