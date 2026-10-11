<?php

namespace App\Services\Games;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuizAuthoring
{
    public function rules(): array
    {
        return [
            'quiz_questions' => ['sometimes', 'required', 'array', 'list', 'min:1', 'max:10'],
            'quiz_questions.*' => ['required', 'array:id,type,question,explanation,options,answer,accepted,case_sensitive,pairs', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_array($value)) {
                    return;
                }
                try {
                    app(QuizQuestions::class)->validate($value);
                } catch (ValidationException $exception) {
                    $fail($exception->errors()[array_key_first($exception->errors())][0]);
                }
            }],
            'quiz_questions.*.id' => ['required', 'string', 'regex:/\A[a-z0-9_-]{1,30}\z/', 'distinct:strict'],
        ];
    }

    public function instance(string $title, array $questions): array
    {
        $questions = Validator::make(['quiz_questions' => $questions, 'title' => $title], $this->rules()
            + ['title' => ['required', 'string', 'max:100']])->validate()['quiz_questions'];
        $mixed = collect($questions)->contains(fn ($question) => ($question['type'] ?? 'choice') !== 'choice');
        $questions = array_map(fn ($question) => app(QuizQuestions::class)->validate($question), $questions);
        if (! $mixed && count($questions) === 1) {
            return ['template' => 'choice-quiz', 'version' => 1, 'title' => $title]
                + array_intersect_key($questions[0], array_flip(['question', 'options', 'answer', 'explanation']));
        }

        return ['template' => 'choice-quiz', 'version' => $mixed ? 3 : 2, 'title' => $title, 'questions' => $questions];
    }

    public function questions(array $instance): array
    {
        return in_array($instance['version'] ?? null, [2, 3], true) ? $instance['questions']
            : [['id' => 'q1'] + array_intersect_key($instance, array_flip(['question', 'options', 'answer', 'explanation']))];
    }

    public function succeeds(array $instance, array $input): bool
    {
        if (($instance['template'] ?? null) !== 'choice-quiz' || ! in_array($instance['version'] ?? null, [1, 2, 3], true)) {
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
            if (! app(QuizQuestions::class)->succeeds($question, $answers[$question['id']] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
