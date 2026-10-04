<?php

namespace App\Services\Games;

use Illuminate\Support\Facades\Validator;

class QuizAuthoring
{
    public function rules(): array
    {
        return [
            'quiz_questions' => ['sometimes', 'required', 'array', 'list', 'min:1', 'max:10'],
            'quiz_questions.*' => ['required', 'array:id,question,options,answer,explanation', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_array($value) || ! is_array($value['options'] ?? null)) {
                    return;
                }
                $ids = array_column($value['options'], 'id');
                $labels = array_map(fn ($label) => is_string($label) ? trim($label) : '', array_column($value['options'], 'label'));
                if (count(array_unique($ids, SORT_REGULAR)) !== count($ids)
                    || count(array_unique($labels)) !== count($labels)) {
                    $fail('Each question needs distinct choice identifiers and different choice text.');
                }
                if (! in_array($value['answer'] ?? null, $ids, true)) {
                    $fail('Choose a correct answer that belongs to this question.');
                }
            }],
            'quiz_questions.*.id' => ['required', 'string', 'regex:/\A[a-z0-9_-]{1,30}\z/', 'distinct:strict'],
            'quiz_questions.*.question' => ['required', 'string', 'max:500'],
            'quiz_questions.*.options' => ['required', 'array', 'list', 'min:2', 'max:6'],
            'quiz_questions.*.options.*' => ['required', 'array:id,label'],
            'quiz_questions.*.options.*.id' => ['required', 'string', 'regex:/\A[a-z0-9_-]{1,30}\z/'],
            'quiz_questions.*.options.*.label' => ['required', 'string', 'max:300'],
            'quiz_questions.*.answer' => ['required', 'string', 'max:30'],
            'quiz_questions.*.explanation' => ['required', 'string', 'max:1000'],
        ];
    }

    public function instance(string $title, array $questions): array
    {
        $questions = Validator::make(['quiz_questions' => $questions, 'title' => $title], $this->rules()
            + ['title' => ['required', 'string', 'max:100']])->validate()['quiz_questions'];
        if (count($questions) === 1) {
            return ['template' => 'choice-quiz', 'version' => 1, 'title' => $title]
                + array_intersect_key($questions[0], array_flip(['question', 'options', 'answer', 'explanation']));
        }

        return ['template' => 'choice-quiz', 'version' => 2, 'title' => $title, 'questions' => $questions];
    }

    public function questions(array $instance): array
    {
        return ($instance['version'] ?? null) === 2 ? $instance['questions']
            : [['id' => 'q1'] + array_intersect_key($instance, array_flip(['question', 'options', 'answer', 'explanation']))];
    }

    public function succeeds(array $instance, array $input): bool
    {
        if (($instance['template'] ?? null) !== 'choice-quiz' || ! in_array($instance['version'] ?? null, [1, 2], true)) {
            return false;
        }
        if ($instance['version'] === 1) {
            return isset($input['answer']) && $input['answer'] === $instance['answer'];
        }
        $answers = $input['answers'] ?? null;
        $questions = $instance['questions'];
        if (! is_array($answers) || count($answers) !== count($questions)) {
            return false;
        }
        foreach ($questions as $question) {
            if (($answers[$question['id']] ?? null) !== $question['answer']) {
                return false;
            }
        }

        return true;
    }
}
