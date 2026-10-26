<?php

use App\Services\Games\QuizAuthoring;
use Illuminate\Validation\ValidationException;

test('optional retry teaching survives typed publication without changing legacy quizzes or grading', function () {
    $question = ['id' => 'q1', 'question' => 'What is two plus two?', 'explanation' => 'Adding two pairs gives four.',
        'options' => [['id' => 'a', 'label' => 'Four'], ['id' => 'b', 'label' => 'Five']], 'answer' => 'a'];
    $authoring = app(QuizAuthoring::class);
    expect($authoring->instance('Addition', [$question])['version'])->toBe(1);
    $question['retry_explanation'] = 'Count the first pair, then add the second pair. Try drawing four dots.';
    $quiz = $authoring->instance('Addition', [$question]);
    expect($quiz['version'])->toBe(3)->and($quiz['questions'][0]['retry_explanation'])->toBe($question['retry_explanation']);
    expect($authoring->succeeds($quiz, ['answers' => ['q1' => 'a']]))->toBeTrue();
    expect($authoring->succeeds($quiz, ['answers' => ['q1' => 'b']]))->toBeFalse();
    $question['retry_explanation'] = str_repeat('x', 1001);
    expect(fn () => $authoring->instance('Addition', [$question]))->toThrow(ValidationException::class);
});
