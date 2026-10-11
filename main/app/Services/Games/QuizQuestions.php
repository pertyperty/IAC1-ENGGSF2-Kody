<?php

namespace App\Services\Games;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuizQuestions
{
    public function validate(array $question): array
    {
        $type = $question['type'] ?? 'choice';
        $fields = match ($type) {
            'choice' => ['options', 'answer'], 'blank' => ['accepted', 'case_sensitive'], 'matching' => ['pairs'], default => [],
        };
        $rules = ['question' => ['required', 'array:'.implode(',', ['id', 'type', 'question', 'explanation', ...$fields])],
            'question.id' => ['required', 'string', 'regex:/\A[a-z0-9_-]{1,30}\z/'],
            'question.type' => ['sometimes', Rule::in(['choice', 'blank', 'matching'])],
            'question.question' => ['required', 'string', 'max:500'], 'question.explanation' => ['required', 'string', 'max:1000']];
        $rules += match ($type) {
            'choice' => ['question.options' => ['required', 'array', 'list', 'min:2', 'max:6'],
                'question.options.*' => ['required', 'array:id,label'],
                'question.options.*.id' => ['required', 'string', 'regex:/\A[a-z0-9_-]{1,30}\z/', 'distinct:strict'],
                'question.options.*.label' => ['required', 'string', 'max:300'], 'question.answer' => ['required', 'string', 'max:30']],
            'blank' => ['question.accepted' => ['required', 'array', 'list', 'min:1', 'max:6'],
                'question.accepted.*' => ['required', 'string', 'max:100'], 'question.case_sensitive' => ['required', 'boolean']],
            'matching' => ['question.pairs' => ['required', 'array', 'list', 'min:2', 'max:6'],
                'question.pairs.*' => ['required', 'array:id,left,right'],
                'question.pairs.*.id' => ['required', 'string', 'regex:/\A[a-z0-9_-]{1,30}\z/', 'distinct:strict'],
                'question.pairs.*.left' => ['required', 'string', 'max:300'], 'question.pairs.*.right' => ['required', 'string', 'max:300']],
            default => [],
        };
        $valid = Validator::make(['question' => $question], $rules)->validate()['question'];
        $invalid = match ($type) {
            'choice' => ! in_array($valid['answer'], array_column($valid['options'], 'id'), true)
                || ! $this->distinct(array_column($valid['options'], 'label')),
            'blank' => ! $this->distinct(array_map(fn ($answer) => $this->normalize($answer, (bool) $valid['case_sensitive']), $valid['accepted'])),
            'matching' => ! $this->distinct(array_column($valid['pairs'], 'left')) || ! $this->distinct(array_column($valid['pairs'], 'right')),
            default => true,
        };
        if ($invalid) {
            throw ValidationException::withMessages(['question' => 'Use distinct choices, accepted answers or matching labels, and a correct answer belonging to the question.']);
        }
        if ($type === 'blank') {
            $valid['case_sensitive'] = (bool) $valid['case_sensitive'];
        }

        return $valid;
    }

    public function succeeds(array $question, mixed $answer): bool
    {
        return match ($question['type'] ?? 'choice') {
            'choice' => is_string($answer) && $answer === $question['answer'],
            'blank' => is_string($answer) && mb_strlen($answer) <= 100
                && in_array($this->normalize($answer, $question['case_sensitive']), array_map(fn ($accepted) => $this->normalize($accepted, $question['case_sensitive']), $question['accepted']), true),
            'matching' => is_array($answer) && count($answer) === count($question['pairs'])
                && collect($question['pairs'])->every(fn ($pair) => ($answer[$pair['id']] ?? null) === $pair['id']),
            default => false,
        };
    }

    public function answerRules(): array
    {
        return ['required', function (string $attribute, mixed $answer, \Closure $fail): void {
            if (is_string($answer) && mb_strlen($answer) <= 100) {
                return;
            }
            if (is_array($answer) && count($answer) >= 2 && count($answer) <= 6) {
                foreach ($answer as $id => $value) {
                    // PHP converts numeric JSON object keys to integers; their IDs remain valid.
                    if (! preg_match('/\A[a-z0-9_-]{1,30}\z/', (string) $id)
                        || ! is_string($value) || ! preg_match('/\A[a-z0-9_-]{1,30}\z/', $value)) {
                        $fail('Matching answers must be bounded question identifiers.');

                        return;
                    }
                }

                return;
            }
            $fail('Use a short answer of up to 100 characters or two to six matching selections.');
        }];
    }

    public function normalize(string $answer, bool $caseSensitive): string
    {
        $answer = trim(preg_replace('/[ \t\r\n\f\v]+/', ' ', $answer), ' ');

        return $caseSensitive ? $answer : mb_strtolower($answer);
    }

    private function distinct(array $labels): bool
    {
        $labels = array_map(trim(...), $labels);

        return ! in_array('', $labels, true) && count(array_unique($labels)) === count($labels);
    }
}
