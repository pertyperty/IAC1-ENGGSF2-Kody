<?php

namespace App\Services\Games;

use Illuminate\Validation\ValidationException;

class AssessmentChallenges
{
    public function instance(string $id): array
    {
        $task = config('assessment-challenges')[$id] ?? null;
        if (! is_array($task)) {
            throw ValidationException::withMessages(['game_challenge' => 'Choose an available assessment task.']);
        }
        $base = (config('learning.instances') + config('arcade'))[$task['basis']];

        return array_replace($base, $task['layout'] ?? [], isset($task['scenario']) ? ['scenario' => $task['scenario']] : [], [
            'title' => $task['title'], 'concept' => $task['concept'], 'instructions' => $task['task'],
            'hint' => $task['hint'], 'learningIdea' => $task['outcome'], 'maxCommands' => $task['limit'],
            'assessment' => ['version' => 1, 'id' => $id, 'basis' => $task['basis'], 'limit' => $task['limit'],
                'commands' => $task['commands'] ?? [], 'check' => $task['check']],
        ]);
    }

    public function catalog(): array
    {
        $catalog = [];
        foreach (array_keys(config('assessment-challenges')) as $id) {
            $catalog[$id] = $this->instance($id);
        }

        return $catalog;
    }

    public function allows(array $instance, array $program): bool
    {
        if (! isset($instance['assessment'])) {
            return true;
        }
        $rules = $instance['assessment'];

        return ($rules['version'] ?? null) === 1 && array_is_list($program) && count($program) <= $rules['limit']
            && ($rules['commands'] === [] || array_all($program, fn ($command) => in_array($command, $rules['commands'], true)));
    }

    public function understands(array $instance, array $input): bool
    {
        return ! isset($instance['assessment'])
            || ($input['assessment_answer'] ?? null) === $instance['assessment']['check']['answer'];
    }
}
