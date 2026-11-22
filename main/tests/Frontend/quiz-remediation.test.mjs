import test from 'node:test';
import assert from 'node:assert/strict';
import { validateQuestion, checkQuestion, matchingChoices } from '../../resources/js/games/quiz-types.js';
import { quizFromQuestions } from '../../resources/js/games/quiz-designer.js';

test('incorrect answers get optional remediation while correct answers keep the explanation', () => {
    const question = { id: 'q1', question: '2 + 2?', explanation: 'Two pairs make four.', retry_explanation: 'Draw two pairs and count every dot.',
        options: [{ id: 'a', label: 'Four' }, { id: 'b', label: 'Five' }], answer: 'a' };
    validateQuestion(question);
    assert.match(checkQuestion(question, 'b').message, /Draw two pairs/);
    assert.match(checkQuestion(question, 'a').message, /Two pairs make four/);
    assert.equal(quizFromQuestions('Addition', [question]).version, 3);
    delete question.retry_explanation;
    assert.match(checkQuestion(question, 'b').message, /Two pairs make four/);
    assert.equal(quizFromQuestions('Addition', [question]).version, 1);
    assert.throws(() => validateQuestion({ ...question, retry_explanation: 'x'.repeat(1001) }));
});

test('a wrong choice gets its own hint before the general retry message', () => {
    const question = { id: 'q2', question: '2 + 2?', explanation: 'Two pairs make four.', retry_explanation: 'Draw two pairs.',
        options: [{ id: 'a', label: 'Four' }, { id: 'b', label: 'Five', wrong_hint: 'Check whether a dot was counted twice.' }, { id: 'c', label: 'Three' }], answer: 'a' };
    assert.equal(quizFromQuestions('Addition', [question]).version, 3);
    assert.equal(checkQuestion(question, 'b').message, 'Try again. Check whether a dot was counted twice.');
    assert.equal(checkQuestion(question, 'c').message, 'Try again. Draw two pairs.');
    assert.equal(checkQuestion(question, 'a').message, 'Correct. Two pairs make four.');
    question.options[1].wrong_hint = 'x'.repeat(1001);
    assert.throws(() => validateQuestion(question));
});

test('matching has the same stable ordering as server rendering', () => {
    const pairs = ['a', 'b', 'c', 'd'].map(id => ({ id }));
    const question = { id: 'q2', pairs };
    const orders = [0, 1, 2, 3].map(index => matchingChoices(question, index).map(pair => pair.id).join(''));
    assert.deepEqual(orders, ['bdac', 'bacd', 'cdba', 'cdab']);
    assert.deepEqual(pairs.map(pair => pair.id), ['a', 'b', 'c', 'd']);
});
