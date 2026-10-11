import test from 'node:test';
import assert from 'node:assert/strict';
import { quizFromQuestions } from '../../resources/js/games/quiz-designer.js';
import { checkPracticeAnswer } from '../../resources/js/games/choice-quiz.js';

const questions = [
    { id: 'blank', type: 'blank', question: 'Use ___ to choose an action.', accepted: ['if', 'if statement'], case_sensitive: false, explanation: 'A condition chooses an action.' },
    { id: 'pairs', type: 'matching', question: 'Connect the ideas.', pairs: [{ id: 'loop', left: 'Loop', right: 'Repeat' }, { id: 'value', left: 'Variable', right: 'Store a value' }], explanation: 'Each has its own purpose.' },
];
const answer = { blank: ' IF   STATEMENT ', pairs: { loop: 'loop', value: 'value' } };

test('mixed quizzes grade short accepted answers and every matching pair automatically', () => {
    const quiz = quizFromQuestions('Recall and connect', questions);
    assert.equal(quiz.version, 3);
    assert.equal(checkPracticeAnswer(quiz, answer).correct, true);
    for (const answers of [undefined, { ...answer, blank: 'otherwise' }, { ...answer, pairs: { loop: 'value', value: 'loop' } },
        { ...answer, pairs: { loop: 'loop', value: 'value', extra: 'loop' } }, { ...answer, extra: 'if' }, { ...answer, pairs: ['loop', 'value'] }]) assert.equal(checkPracticeAnswer(quiz, answers).correct, false);
    const sensitive = quizFromQuestions('Case matters', [{ ...questions[0], case_sensitive: true }]);
    assert.equal(checkPracticeAnswer(sensitive, { blank: 'IF' }).correct, false);
    assert.equal(checkPracticeAnswer(sensitive, { blank: ' if ' }).correct, true);
});

test('new quiz authoring rejects ambiguous pairs answers and executable or unsupported fields', () => {
    for (const question of [{ ...questions[0], accepted: ['if', 'IF'] }, { ...questions[0], accepted: [' '] },
        { ...questions[0], accepted: ['x'.repeat(101)] }, { ...questions[0], type: 'open-ended' }, { ...questions[0], source: 'alert(1)' },
        { ...questions[1], pairs: [{ id: 'a', left: 'Same', right: 'First' }, { id: 'b', left: 'Same', right: 'Second' }] }]) assert.throws(() => quizFromQuestions('Invalid', [question]));
});
