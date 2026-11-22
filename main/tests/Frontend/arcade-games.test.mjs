import test from 'node:test';
import assert from 'node:assert/strict';
import { phpFixture } from '../Support/php-fixture.mjs';
import { evaluateArcade, validScenario } from '../../resources/js/games/arcade-games.js';
import { normalizeArcadeCommand } from '../../resources/js/games/arcade-pseudocode.js';
import { readableInstruction } from '../../resources/js/games/learning-trace.js';

const games = JSON.parse(phpFixture(['-r', 'echo json_encode(require "config/arcade.php");']).toString());
const solutions = { 'pixel-studio': ['paint 1 1 mint', 'paint 2 2 peach'], 'number-machine': ['add 3', 'multiply 2'],
    'sort-lab': ['swap 1 2', 'swap 2 3'], 'terminal-quest': ['ls', 'cp hello.txt release.txt', 'cat release.txt'], 'logic-switchboard': ['flip 1', 'flip 2'], 'string-forge': ['upper', 'reverse'], 'memory-trail': ['push 2', 'push 3'] };
for (const [template, program] of Object.entries(solutions)) {
    test(`${template} wins with deterministic immutable state and rejects invalid commands`, () => {
        const before = structuredClone(games[template]);
        assert.equal(evaluateArcade(games[template], program).success, true);
        assert.equal(evaluateArcade(games[template], ['eval alert(1)']).success, false);
        assert.equal(evaluateArcade(games[template], [...program, 'invalid']).success, false);
        assert.equal(evaluateArcade(games[template], Array(13).fill(program[0])).success, false);
        assert.deepEqual(games[template], before);
    });
}
test('terminal requires copy plus inspection and rejects host commands or traversal', () => {
    for (const program of [['cp hello.txt release.txt'], ['cat ../secrets.txt'], ['ls; whoami'], ['bash'], ['cp hello.txt release.txt', 'cat release.txt', 'cp hello.txt other.txt']]) assert.equal(evaluateArcade(games['terminal-quest'], program).success, false);
});

test('scenario previews reject malformed types, duplicate coordinates, extra fields and unreachable objectives', () => {
    assert.equal(validScenario('pixel-studio', { pixels: '1 1 mint' }), false);
    assert.equal(validScenario('pixel-studio', { pixels: ['1 1 mint', '1 1 peach'] }), false);
    assert.equal(validScenario('number-machine', { start: '2', target: 10 }), false);
    assert.equal(validScenario('number-machine', { start: 2, target: 10, source: 'alert(1)' }), false);
    assert.equal(validScenario('sort-lab', { items: [1, null] }), false);
    assert.equal(validScenario('terminal-quest', { ...games['terminal-quest'].scenario, content: 'Missing' }), false);
    for (const game of Object.values(games)) assert.equal(validScenario(game.template, game.scenario), true);
});
test('pixel objectives require exact color and blank cells; arithmetic is bounded', () => {
    assert.equal(evaluateArcade(games['pixel-studio'], ['paint 1 1 mint', 'paint 2 2 peach', 'paint 3 3 mint']).success, false);
    assert.equal(evaluateArcade(games['number-machine'], ['multiply 100', 'multiply 100']).success, false);
});
test('browser and authoritative server agree across valid and hostile programs', () => {
    const cases = [];
    for (const [template, solution] of Object.entries(solutions)) {
        const displayed = solution.map(command => readableInstruction(template, command));
        assert.deepEqual(displayed.map(command => normalizeArcadeCommand(template, command)), solution);
        assert.equal(evaluateArcade(games[template], displayed).success, true);
        for (const program of [solution, displayed, [solution[0], ...displayed.slice(1)], solution.slice(0, 1), [], ['bad'], [...solution, 'bad'], Array(13).fill(solution[0]), ['cat ../file.txt'], ['add 999'], ['swap 6 6'], ['paint 1 1 mint\n'], [displayed[0] + '; alert(1)'], [displayed[0] + '\n'], [displayed[0] + ' '.repeat(110)]]) cases.push({ template, program });
    }
    const server = JSON.parse(phpFixture(['tests/Support/arcade-evaluation.php'], { input: JSON.stringify(cases) }).toString());
    assert.deepEqual(server, cases.map(({ template, program }) => evaluateArcade(games[template], program).success));
});

test('each supported operation round-trips without evaluating expressions', () => {
    const examples = {
        'number-machine': ['subtract -100', 'multiply -9'],
        'string-forge': ['append a_9-z', 'lower', 'trim'],
        'memory-trail': ['pop', 'duplicate', 'swap'],
    };
    for (const [template, commands] of Object.entries(examples)) {
        for (const command of commands) assert.equal(normalizeArcadeCommand(template, readableInstruction(template, command)), command);
    }
    for (const [template, command] of [
        ['logic-switchboard', 'inputs[1] = not inputs[2]'],
        ['number-machine', 'value = value + (3 * 2)'],
        ['pixel-studio', 'canvas.paint(1, 1, document.cookie)'],
        ['string-forge', 'text = eval(text)'],
        ['memory-trail', 'stack.push(3); stack.pop()'],
    ]) assert.equal(evaluateArcade(games[template], [command]).success, false);
});
