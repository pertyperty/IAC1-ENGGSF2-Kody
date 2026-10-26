import test from 'node:test';
import assert from 'node:assert/strict';
import { phpFixture } from '../Support/php-fixture.mjs';
import { checkPracticeAnswer, validateQuiz } from '../../resources/js/games/choice-quiz.js';

const { quizzes } = JSON.parse(phpFixture(['-r', 'echo json_encode(require "config/learning.php");'], { encoding: 'utf8' }));

test('quiz character limits match server Unicode character counts', () => {
    const quiz = structuredClone(quizzes.sequences);
    quiz.explanation = '\u{1f600}'.repeat(1000);
    assert.equal(validateQuiz(quiz), quiz);
    quiz.explanation += 'x';
    assert.throws(() => validateQuiz(quiz));
});

test('each practice quiz distinguishes correct, wrong and missing answers', () => {
    for (const quiz of Object.values(quizzes)) {
        assert.equal(checkPracticeAnswer(quiz, quiz.answer).correct, true);
        assert.equal(checkPracticeAnswer(quiz, quiz.options.find((option) => option.id !== quiz.answer).id).correct, false);
        assert.equal(checkPracticeAnswer(quiz, '<script>').message, 'Choose an answer first.');
        assert.equal(checkPracticeAnswer(quiz).correct, false);
    }
});

test('quiz placeholders reject unsupported versions and malformed options', () => {
    for (const change of [{ version: 2 }, { template: 'script' }, { options: [] }, { answer: 'missing' }, { question: null }, { options: [{ id: 'same', label: 'A' }, { id: 'same', label: 'B' }] }]) {
        assert.throws(() => validateQuiz({ ...quizzes.loops, ...change }));
    }
});

test('creators can replace quiz text without changing mechanics', () => {
    const quiz = { ...quizzes.sequences, title: 'A new lesson', question: 'Which comes first?', options: [{ id: 'one', label: 'First step' }, { id: 'two', label: 'Second step' }], answer: 'one', explanation: 'Start with the first instruction.' };
    assert.equal(checkPracticeAnswer(quiz, 'one').message, 'You got it! Start with the first instruction.');
});
