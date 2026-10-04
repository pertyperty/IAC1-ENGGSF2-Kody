import test from 'node:test';
import assert from 'node:assert/strict';
import { quizFromQuestions } from '../../resources/js/games/quiz-designer.js';
import { checkPracticeAnswer, validateQuiz } from '../../resources/js/games/choice-quiz.js';
import { firstUnusedId } from '../../resources/js/games/quiz-editor.js';

const questions = [
    { id: 'sequence', question: 'What runs first?', options: [{ id: 'last', label: 'Last' }, { id: 'first', label: 'First' }, { id: 'middle', label: 'Middle' }], answer: 'first', explanation: 'Instructions run in order.' },
    { id: 'loop', question: 'What repeats?', options: [{ id: 'yes', label: 'A loop' }, { id: 'no', label: 'A value' }], answer: 'yes', explanation: 'Loops repeat.' },
];

test('multiquestion practice checks every answer with separate feedback', () => {
    const quiz = quizFromQuestions('Two ideas', structuredClone(questions));
    assert.equal(quiz.version, 2);
    assert.equal(checkPracticeAnswer(quiz, { sequence: 'first', loop: 'yes' }).correct, true);
    for (const answers of [undefined, { sequence: 'first' }, { sequence: 'last', loop: 'yes' }, { sequence: 'first', loop: 'yes', extra: 'yes' }]) {
        const result = checkPracticeAnswer(quiz, answers);
        assert.equal(result.correct, false);
        assert.equal(result.results.length, 2);
    }
});

test('single questions support all two to six choices without changing version', () => {
    for (let count = 2; count <= 6; count++) {
        const question = { ...questions[0], options: Array.from({ length: count }, (_, i) => ({ id: `o${i}`, label: `Option ${i}` })), answer: `o${count - 1}` };
        const quiz = quizFromQuestions('One idea', [question]);
        assert.equal(quiz.version, 1);
        assert.equal(checkPracticeAnswer(quiz, question.answer).correct, true);
    }
});

test('reordering choices and questions preserves correct answers by identifier', () => {
    const reordered = structuredClone(questions).reverse();
    reordered.forEach((question) => question.options.reverse());
    assert.equal(checkPracticeAnswer(quizFromQuestions('Reordered', reordered), { sequence: 'first', loop: 'yes' }).correct, true);
    assert.equal(firstUnusedId(['q1', 'q3']), 'q2');
    assert.equal(firstUnusedId(['o1', 'o2'], 'o'), 'o3');
});

test('creator quiz validation rejects incomplete ambiguous and oversized collections', () => {
    const invalid = [[], Array.from({ length: 11 }, () => questions[0]), [questions[0], questions[0]], [{ ...questions[0], answer: 'missing' }],
        [{ ...questions[0], options: [{ id: 'a', label: 'Same' }, { id: 'b', label: ' Same ' }] }], [{ ...questions[0], explanation: '' }]];
    invalid.forEach((collection) => assert.throws(() => quizFromQuestions('Invalid', collection)));
    assert.throws(() => validateQuiz({ template: 'choice-quiz', version: 2, title: 'Bad', questions: [{ ...questions[0], id: '<script>' }, questions[1]] }));
});
