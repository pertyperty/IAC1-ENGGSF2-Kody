<?php

namespace App\Services\Games;

class PracticeChallenges
{
    public const LEVELS = ['warmup' => 'Warm-up', 'challenge' => 'Challenge', 'stretch' => 'Stretch'];

    public function instance(string $template, string $level): array
    {
        $definition = config('practice-challenges.'.$template);
        $game = app(AssessmentChallenges::class)->instance($definition['challenge']);
        if ($level === 'challenge') {
            return $game;
        }
        $task = $definition[$level];
        $game = array_replace($game, $task['layout'] ?? [], isset($task['scenario']) ? ['scenario' => $task['scenario']] : []);
        $game['title'] = $task['title'];
        $game['instructions'] = $task['task'];
        $game['hint'] = $task['hint'];
        $game['maxCommands'] = $task['limit'];
        $game['assessment'] = array_replace($game['assessment'], [
            'id' => 'practice-'.$template.'-'.$level,
            'limit' => $task['limit'], 'commands' => $task['commands'] ?? [],
            'check' => $task['check'] ?? $game['assessment']['check'],
        ]);

        return $game;
    }

    public function catalog(string $level): array
    {
        $games = [];
        foreach (array_keys(config('practice-challenges')) as $template) {
            $games[$template] = $this->instance($template, $level);
        }

        return $games;
    }
}
