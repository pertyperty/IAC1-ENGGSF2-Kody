<?php

namespace App\Services\Games;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PresetConfiguration
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'record_version' => ['required', 'integer', 'min:1'],
            'basis' => ['required', Rule::in(array_merge(['sequences', 'loops', 'conditions', 'quiz'], array_keys(config('arcade'))))],
            'title' => ['required', 'string', 'max:100'],
            'game_scenario' => ['nullable', 'string', 'max:4000'],
            'instructions' => ['required_unless:basis,quiz', 'nullable', 'string', 'max:1000'],
            'hint' => ['required_unless:basis,quiz', 'nullable', 'string', 'max:1000'],
            'learning_idea' => ['required_unless:basis,quiz', 'nullable', 'string', 'max:1000'],
            'question' => ['exclude_with:quiz_questions', 'required_if:basis,quiz', 'nullable', 'string', 'max:500'],
            'choice_a' => ['exclude_with:quiz_questions', 'required_if:basis,quiz', 'nullable', 'string', 'max:300', 'different:choice_b'],
            'choice_b' => ['exclude_with:quiz_questions', 'required_if:basis,quiz', 'nullable', 'string', 'max:300'],
            'answer' => ['exclude_with:quiz_questions', 'required_if:basis,quiz', 'nullable', Rule::in(['a', 'b'])],
            'explanation' => ['exclude_with:quiz_questions', 'required_if:basis,quiz', 'nullable', 'string', 'max:1000'],
            'reward_mode' => ['required', Rule::in(['Deferred'])],
            'scoring' => ['prohibited'], 'participation' => ['prohibited'], 'instance' => ['prohibited'],
            'created_by' => ['prohibited'], 'status' => ['prohibited'], 'current_revision_id' => ['prohibited'],
            'reward_points' => ['prohibited'], 'source' => ['prohibited']] + app(QuizAuthoring::class)->rules();
    }

    public function validated(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        return Validator::make($data, $this->rules())->validate();
    }

    public function instance(array $data): array
    {
        if ($data['basis'] === 'quiz') {
            if (isset($data['quiz_questions'])) {
                return app(QuizAuthoring::class)->instance($data['title'], $data['quiz_questions']);
            }

            return ['template' => 'choice-quiz', 'version' => 1, 'title' => $data['title'], 'question' => $data['question'],
                'options' => [['id' => 'a', 'label' => $data['choice_a']], ['id' => 'b', 'label' => $data['choice_b']]],
                'answer' => $data['answer'], 'explanation' => $data['explanation']];
        }

        return array_replace(isset(config('arcade')[$data['basis']]) ? app(ArcadeGames::class)->instance($data['basis'], $data['game_scenario'] ?? null) : config('learning.instances')[$data['basis']], ['basis' => $data['basis'],
            'title' => $data['title'], 'instructions' => $data['instructions'], 'hint' => $data['hint'], 'learningIdea' => $data['learning_idea']]);
    }
}
