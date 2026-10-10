<?php

namespace App\Services\Games;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TowerAuthoring
{
    /** Rebuild instances through the same typed creator boundaries; executable payloads are never accepted. */
    public function stages(array $stages): array
    {
        Validator::make(['stages' => $stages], ['stages' => ['required', 'array', 'list', 'min:1', 'max:4'], 'stages.*' => ['required', 'array']])->validate();

        return array_map(function (array $stage): array {
            if (($stage['template'] ?? null) === 'choice-quiz') {
                Validator::make($stage, ['version' => ['required', 'integer', 'in:1,2'], 'title' => ['required', 'string', 'max:100']])->validate();
                if ($stage['version'] === 2) {
                    Validator::make(['quiz_questions' => $stage['questions'] ?? null], app(QuizAuthoring::class)->rules())->validate();
                } else {
                    Validator::make($stage, ['question' => ['required', 'string'], 'options' => ['required', 'array'], 'answer' => ['required', 'string'], 'explanation' => ['required', 'string']])->validate();
                }

                return app(QuizAuthoring::class)->instance($stage['title'] ?? '', app(QuizAuthoring::class)->questions($stage));
            }
            $basis = ($stage['template'] ?? null) === 'command-garden'
                ? match ($stage['mode'] ?? null) {
                    'sequence' => 'sequences', 'loop' => 'loops', 'conditional' => 'conditions', default => null
                }
            : ($stage['template'] ?? null);
            if (! is_string($basis) || ! isset((config('learning.instances') + config('arcade'))[$basis])) {
                throw ValidationException::withMessages(['stages' => 'Choose a supported game or practice quiz template.']);
            }
            Validator::make($stage, ['version' => ['required', 'integer', 'in:1'], 'title' => ['required', 'string', 'max:100'],
                'concept' => ['required', 'string', 'max:100'], 'instructions' => ['required', 'string', 'max:1000'],
                'hint' => ['required', 'string', 'max:1000'], 'learningIdea' => ['required', 'string', 'max:1000']])->validate();
            $layout = array_intersect_key($stage, array_flip(['start', 'goal', 'path', 'crystals']));
            $game = app(GameAssessment::class)->instance(['game_preset' => $basis,
                'game_layout' => json_encode($layout, JSON_THROW_ON_ERROR), 'game_scenario' => json_encode($stage['scenario'] ?? [], JSON_THROW_ON_ERROR)]);

            return array_replace($game, array_intersect_key($stage, array_flip(['title', 'concept', 'instructions', 'hint', 'learningIdea'])));
        }, $stages);
    }
}
