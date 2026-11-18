import test from 'node:test';
import assert from 'node:assert/strict';
import { phpFixture } from '../Support/php-fixture.mjs';
import { runProgram } from '../../resources/js/games/command-garden.js';
import { evaluateArcade } from '../../resources/js/games/arcade-games.js';
import { conceptAnswerCorrect, programRuleError, objectiveFeedback } from '../../resources/js/games/assessment-challenges.js';

test('all guided reference programs and hostile shortcuts agree with the authoritative server', () => {
    const definitions = JSON.parse(phpFixture(['-r', 'echo json_encode(require "config/assessment-challenges.php");']).toString());
    const cases = [];
    for (const [id, definition] of Object.entries(definitions)) {
        const valid = { ...definition.reference, assessment_answer: definition.check.answer };
        for (const input of [valid, { ...valid, assessment_answer: 'wrong' }, { ...definition.reference },
            { ...valid, program: ['bad'] }, { ...valid, program: Array(13).fill(valid.program[0]) }]) cases.push({ id, input });
    }
    cases.push({ id: 'number-order', input: { program: ['add 16'], assessment_answer: 'b' } });
    const { games, results } = JSON.parse(phpFixture(['tests/Support/assessment-evaluation.php'], { input: JSON.stringify(cases) }).toString());
    const actual = cases.map(({ id, input }) => {
        const game = games[id];
        const result = game.template === 'command-garden' ? runProgram(game, input.program, input) : evaluateArcade(game, input.program);
        return result.success && conceptAnswerCorrect(game, input.assessment_answer);
    });
    assert.deepEqual(actual, results);
    assert.equal(results.filter(Boolean).length, 10);
    assert.ok(Object.values(games).every(game => !Object.hasOwn(game, 'reference')));
    assert.match(programRuleError(games['number-order'], ['add 16']), /provided/);
    assert.match(objectiveFeedback(games['number-order'], evaluateArcade(games['number-order'], ['add 3'])), /value is 5/);
});
