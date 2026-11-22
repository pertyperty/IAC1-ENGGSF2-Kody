<?php

use App\Services\Games\QuizAuthoring;
use App\Services\Games\QuizQuestions;
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

test('choice hints survive authoring without appearing in legacy quiz versions or changing grading', function () {
    $question = ['id' => 'q2', 'question' => '2 + 2?', 'explanation' => 'Two pairs make four.', 'answer' => 'a',
        'options' => [['id' => 'a', 'label' => 'Four'], ['id' => 'b', 'label' => 'Five', 'wrong_hint' => null]]];
    $authoring = app(QuizAuthoring::class);
    $legacy = $authoring->instance('Addition', [$question]);
    expect($legacy['version'])->toBe(1)->and($legacy['options'][1])->not->toHaveKey('wrong_hint');

    $question['options'][1]['wrong_hint'] = 'Check whether a dot was counted twice.';
    $quiz = $authoring->instance('Addition', [$question]);
    expect($quiz['version'])->toBe(3)
        ->and($quiz['questions'][0]['options'][1]['wrong_hint'])->toBe($question['options'][1]['wrong_hint'])
        ->and($authoring->succeeds($quiz, ['answers' => ['q2' => 'b']]))->toBeFalse()
        ->and($authoring->succeeds($quiz, ['answers' => ['q2' => 'a']]))->toBeTrue();

    $question['options'][1]['wrong_hint'] = str_repeat('x', 1001);
    expect(fn () => $authoring->instance('Addition', [$question]))->toThrow(ValidationException::class);
});

test('matching renders a stable permutation by question position while grading the original IDs', function () {
    $pairs = array_map(fn ($id) => ['id' => $id, 'left' => 'Prompt '.$id, 'right' => 'Match '.$id], ['a', 'b', 'c', 'd']);
    $question = ['id' => 'q2', 'type' => 'matching', 'question' => 'Match the terms.', 'explanation' => 'Pair terms with their meanings.', 'pairs' => $pairs];
    $questions = app(QuizQuestions::class);
    $orders = array_map(fn ($index) => implode(array_column($questions->matchingChoices($question, $index), 'id')), range(0, 3));
    expect($orders)->toBe(['bdac', 'bacd', 'cdba', 'cdab']);
    expect($questions->succeeds($question, array_combine(['a', 'b', 'c', 'd'], ['a', 'b', 'c', 'd'])))->toBeTrue();
    $quiz = app(QuizAuthoring::class)->instance('Matching', [$question]);
    expect(view('learning.quiz', ['quiz' => $quiz])->render())->toContain('value="b">Match b</option><option value="d">Match d');
});
