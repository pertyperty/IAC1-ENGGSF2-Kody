import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { evaluateArcade, validScenario } from '../../resources/js/games/arcade-games.js';

const games = JSON.parse(execFileSync('php', ['-r', 'echo json_encode(require "config/arcade.php");']).toString());
const solutions = { 'pixel-studio': ['paint 1 1 mint', 'paint 2 2 peach'], 'number-machine': ['add 3', 'multiply 2'],
    'sort-lab': ['swap 1 2', 'swap 2 3'], 'terminal-quest': ['ls', 'cp hello.txt release.txt', 'cat release.txt'] };
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
        for (const program of [solution, solution.slice(0, 1), [], ['bad'], [...solution, 'bad'], Array(13).fill(solution[0]), ['cat ../file.txt'], ['add 999'], ['swap 6 6'], ['paint 1 1 mint\n']]) cases.push({ template, program });
    }
    const server = JSON.parse(execFileSync('php', ['tests/Support/arcade-evaluation.php'], { input: JSON.stringify(cases) }).toString());
    assert.deepEqual(server, cases.map(({ template, program }) => evaluateArcade(games[template], program).success));
});
