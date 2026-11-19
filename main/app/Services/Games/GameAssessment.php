<?php

namespace App\Services\Games;

use Illuminate\Validation\ValidationException;

class GameAssessment
{
    public function instance(array $data): array
    {
        if (! empty($data['game_challenge'])) {
            $instance = app(AssessmentChallenges::class)->instance($data['game_challenge']);
            if ($instance['assessment']['basis'] !== $data['game_preset']) {
                throw ValidationException::withMessages(['game_challenge' => 'The assessment task must match the selected template.']);
            }

            return $instance;
        }

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
        if (! app(AssessmentChallenges::class)->understands($instance, $input)) {
            return false;
        }

        return ($instance['template'] ?? null) === 'command-garden'
            ? app(CommandGarden::class)->succeeds($instance, $input['program'], $input['repeat'] ?? false, $input['conditional'] ?? false)
            : app(ArcadeGames::class)->succeeds($instance, $input['program']);
    }
}
