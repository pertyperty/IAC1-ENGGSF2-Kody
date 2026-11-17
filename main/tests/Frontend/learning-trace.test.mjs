import test from 'node:test';
import assert from 'node:assert/strict';
import { phpFixture } from '../Support/php-fixture.mjs';
import { arcadeTrace, readableInstruction, stateText } from '../../resources/js/games/learning-trace.js';
import { evaluateArcade } from '../../resources/js/games/arcade-games.js';
import { runProgram } from '../../resources/js/games/command-garden.js';

const games = JSON.parse(phpFixture(['-r', 'echo json_encode(require "config/arcade.php");'], { encoding: 'utf8' }));
const { instances } = JSON.parse(phpFixture(['-r', 'echo json_encode(require "config/learning.php");'], { encoding: 'utf8' }));
test('trace states come from the actual interpreter and stop at the first invalid instruction', () => {
    const game = games['number-machine'];
    const trace = arcadeTrace(game, ['add 3', 'multiply 2', 'bad', 'add 1'], evaluateArcade);
    assert.equal(trace.initial.number, game.scenario.start);
    assert.equal(trace.frames.length, 3);
    assert.equal(trace.frames[1].state.number, 10);
    assert.match(trace.frames[2].explanation, /Use add/);
    assert.equal(evaluateArcade(game, ['add 3', 'multiply 2']).state.number, trace.frames[1].state.number);
    assert.equal(readableInstruction(game.template, 'multiply 2'), 'value = value × 2');
});
test('all seven arcade state displays and readable programs describe their bounded operations', () => {
    for (const game of Object.values(games)) {
        const initial = evaluateArcade(game, []).state;
        assert.equal(typeof stateText(game, initial), 'string');
        assert.ok(stateText(game, initial).length);
    }
    assert.match(readableInstruction('string-forge', 'append Code'), /"Code"/);
    assert.match(readableInstruction('sort-lab', 'swap 1 2'), /position 1.*position 2/);
});
test('Garden trace distinguishes loop iterations and conditional checks without altering wins', () => {
    const loop = runProgram(instances.loops, ['right', 'up', 'right'], { repeat: true });
    assert.deepEqual(loop.trace.map(frame => frame.cycle), [1, 1, 1, 2, 2, 2]);
    const conditional = runProgram(instances.conditions, ['right', 'right', 'up', 'right', 'right'], { conditional: true });
    assert.equal(conditional.trace[0].crystalHere, true);
    assert.equal(conditional.trace[1].crystalHere, false);
    assert.equal(conditional.success, true);
});
