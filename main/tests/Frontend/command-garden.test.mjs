import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { runProgram, validateInstance } from '../../resources/js/games/command-garden.js';

// Exercise the actual built-in creator-slot data, not a duplicate fixture map.
const { instances } = JSON.parse(execFileSync(process.env.KODY_TEST_PHP || 'php', ['-r', 'echo json_encode(require "config/learning.php");'], { encoding: 'utf8' }));

test('sequences teach order and finish only at the goal', () => {
    assert.equal(runProgram(instances.sequences, ['right', 'right', 'up', 'right', 'right']).success, true);
    assert.equal(runProgram(instances.sequences, ['up', 'right']).success, false);
    assert.equal(runProgram(instances.sequences, ['right']).success, false);
    assert.equal(runProgram(instances.sequences, []).success, false);
});

test('loops require repeating a short pattern', () => {
    assert.equal(runProgram(instances.loops, ['right', 'up', 'right'], { repeat: true }).success, true);
    assert.equal(runProgram(instances.loops, ['right', 'up', 'right']).success, false);
    assert.equal(runProgram(instances.loops, ['right', 'up', 'right', 'right', 'up', 'right'], { repeat: true }).success, false);
});

test('conditions collect crystals only when the rule is enabled', () => {
    const program = ['right', 'right', 'up', 'right', 'right'];
    const missed = runProgram(instances.conditions, program);
    assert.equal(missed.success, false);
    assert.equal(missed.crystals.length, 2);
    const collected = runProgram(instances.conditions, program, { conditional: true });
    assert.equal(collected.success, true);
    assert.equal(collected.crystals.length, 0);
    assert.equal(collected.trace[0].crystals.length, 1);
    assert.equal(collected.trace[1].crystals.length, 1);
});

test('unknown commands, prototype keys and oversized programs never execute', () => {
    for (const command of ['constructor', '__proto__', 'eval', '<script>']) {
        assert.equal(runProgram(instances.sequences, [command]).success, false);
    }
    assert.equal(runProgram(instances.sequences, Array(13).fill('right')).trace.length, 0);
});

test('unsafe, unsupported or malformed template data is rejected', () => {
    for (const change of [{ template: 'uploaded-script' }, { version: 2 }, { width: 10000 }, { path: [[-1, 0]] }, { crystals: [[4, 3]] }, { start: [0, 0] }, { goal: [0, 2] }, { mode: 'execute' }, { hint: 'x'.repeat(1001) }]) {
        assert.throws(() => validateInstance({ ...instances.sequences, ...change }));
    }
});

test('retries do not mutate the instance or previous program', () => {
    const snapshot = JSON.stringify(instances.conditions);
    const program = ['right', 'right', 'up', 'right', 'right'];
    const first = runProgram(instances.conditions, program, { conditional: true });
    assert.deepEqual(first, runProgram(instances.conditions, program, { conditional: true }));
    assert.equal(JSON.stringify(instances.conditions), snapshot);
    assert.equal(program.length, 5);
});

test('a different creator scenario can use the same mechanics', () => {
    const custom = { ...instances.sequences, title: 'A new world', start: [1, 1], goal: [3, 1], path: [[1, 1], [2, 1], [3, 1]] };
    assert.equal(runProgram(custom, ['right', 'right']).success, true);
    assert.equal(runProgram(custom, ['right']).success, false);
});

test('browser preview and authoritative PHP evaluator agree on success and failure', () => {
    const cases = [];
    for (const level of Object.keys(instances)) {
        for (const program of [[], ['right'], ['up'], ['right', 'right', 'up', 'right', 'right'], ['right', 'up', 'right'], ['left', 'right'], Array(13).fill('right'), ['constructor']]) {
            for (const repeat of [false, true]) for (const conditional of [false, true]) cases.push({ level, program, repeat, conditional });
        }
    }
    const server = JSON.parse(execFileSync(process.env.KODY_TEST_PHP || 'php', ['tests/Support/game-evaluation.php'], { input: JSON.stringify(cases), encoding: 'utf8' }));
    assert.deepEqual(server, cases.map(({level, program, repeat, conditional}) => runProgram(instances[level], program, {repeat, conditional}).success));
});
