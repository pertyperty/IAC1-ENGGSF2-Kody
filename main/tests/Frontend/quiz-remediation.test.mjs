import test from 'node:test';
import assert from 'node:assert/strict';
import { validateQuestion, checkQuestion } from '../../resources/js/games/quiz-types.js';
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
