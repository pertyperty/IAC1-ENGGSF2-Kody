import test from 'node:test';
import assert from 'node:assert/strict';
import { phpFixture } from '../Support/php-fixture.mjs';
import { runProgram } from '../../resources/js/games/command-garden.js';
import { evaluateArcade } from '../../resources/js/games/arcade-games.js';
import { missionProgress } from '../../resources/js/games/mission.js';

test('all thirty practice tasks are reachable and agree with authoritative replay', () => {
    const cases = JSON.parse(phpFixture(['tests/Support/practice-evaluation.php']).toString());
    assert.equal(cases.length, 30);
    for (const { template, level, game, input, accepted } of cases) {
        assert.equal(accepted, true, `${template}/${level} server`);
        const evaluate = game.template === 'command-garden' ? runProgram : evaluateArcade;
        assert.equal(evaluate(game, input.program, input).success, true, `${template}/${level} browser`);
        assert.equal(evaluate(game, Array(13).fill(input.program[0]), input).success, false);
        assert.equal(Object.hasOwn(game, 'reference'), false);
    }
});

test('goal comparisons account for blank cells, extra text and stack order', () => {
    assert.equal(missionProgress({ template: 'pixel-studio', scenario: { pixels: ['1 1 mint'] } }, { pixels: {} }).value, 8);
    assert.equal(missionProgress({ template: 'pixel-studio', scenario: { pixels: ['1 1 mint'] } }, { pixels: { '1 1': 'mint', '2 1': 'mint' } }).value, 8);
    assert.deepEqual(missionProgress({ template: 'string-forge', scenario: { target: 'AB' } }, { text: 'ABX' }), { value: 2, max: 3, matched: false, text: '2 matching characters · Length 3 / target 2' });
    assert.equal(missionProgress({ template: 'string-forge', scenario: { target: 'AB' } }, { text: 'AB' }).matched, true);
    assert.equal(missionProgress({ template: 'memory-trail', scenario: { target: [2, 3] } }, { stack: [3, 2] }).value, 0);
    assert.equal(missionProgress({ template: 'memory-trail', scenario: { target: [] } }, { stack: [] }).value, 1);
});

test('stretch tasks resist shorter-program shortcuts and sorting requires more swaps', () => {
    const cases = JSON.parse(phpFixture(['tests/Support/practice-evaluation.php']).toString());
    const stretch = Object.fromEntries(cases.filter(item => item.level === 'stretch').map(item => [item.template, item]));
    for (const [template, minimum] of [['loops', 4], ['number-machine', 5], ['string-forge', 5], ['memory-trail', 5]]) {
        const { game } = stretch[template];
        const commands = template === 'loops' ? ['up', 'down', 'left', 'right'] : game.assessment.commands;
        const evaluate = template === 'loops' ? runProgram : evaluateArcade;
        let programs = [[]];
        for (let length = 1; length < minimum; length++) {
            programs = programs.flatMap(program => commands.map(command => [...program, command]));
            assert.ok(programs.every(program => !evaluate(game, program, { repeat: true }).success), `${template}: no ${length}-instruction shortcut`);
        }
    }
    // A distinct-value permutation needs n minus cycle-count swaps.
    const swapsNeeded = items => {
        const visited = new Set(); let cycles = 0;
        for (let i = 0; i < items.length; i++) {
            if (visited.has(i)) continue;
            cycles++; let cursor = i;
            while (!visited.has(cursor)) { visited.add(cursor); cursor = items[cursor] - 1; }
        }
        return items.length - cycles;
    };
    assert.equal(swapsNeeded(stretch['sort-lab'].game.scenario.items), 5);
    assert.equal(swapsNeeded(cases.find(item => item.template === 'sort-lab' && item.level === 'challenge').game.scenario.items), 4);
    const route = stretch.sequences.game;
    const path = new Set(route.path.map(point => point.join(',')));
    const queue = [[route.start, 0]]; const seen = new Set(); let distance;
    while (queue.length) {
        const [point, length] = queue.shift(); const key = point.join(',');
        if (seen.has(key) || !path.has(key)) continue;
        seen.add(key);
        if (key === route.goal.join(',')) { distance = length; break; }
        for (const [dx, dy] of [[0, 1], [0, -1], [1, 0], [-1, 0]]) queue.push([[point[0] + dx, point[1] + dy], length + 1]);
    }
    assert.equal(distance, 8);
});
