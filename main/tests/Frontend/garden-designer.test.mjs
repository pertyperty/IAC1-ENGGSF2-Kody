import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { gardenLayout, paintGarden } from '../../resources/js/games/garden-designer.js';
import { runProgram } from '../../resources/js/games/command-garden.js';

const presets = JSON.parse(execFileSync('php', ['-r', 'echo json_encode(require "config/learning.php");']).toString()).instances;

test('garden painting preserves inputs and protects objectives while changing path tiles', () => {
    const layout = gardenLayout(presets.sequences);
    const before = structuredClone(layout);
    const changed = paintGarden(layout, [1, 2], 'path', 'sequence');
    assert.deepEqual(layout, before);
    assert.equal(changed.path.some(([x, y]) => x === 1 && y === 2), false);
    assert.deepEqual(paintGarden(layout, layout.start, 'path', 'sequence'), layout);
    assert.deepEqual(paintGarden(layout, layout.goal, 'path', 'sequence'), layout);
    assert.deepEqual(paintGarden(layout, [99, 0], 'path', 'sequence'), layout);
});

test('moving objectives adds walkable tiles and prevents overlap', () => {
    const layout = gardenLayout(presets.sequences);
    const changed = paintGarden(layout, [0, 0], 'start', 'sequence');
    assert.deepEqual(changed.start, [0, 0]);
    assert.ok(changed.path.some(([x, y]) => x === 0 && y === 0));
    assert.deepEqual(paintGarden(layout, layout.goal, 'start', 'sequence'), layout);
});

test('crystals use conditional mechanics and disappear when their tile is removed', () => {
    const layout = gardenLayout(presets.conditions);
    assert.deepEqual(paintGarden(layout, layout.start, 'crystal', 'conditional'), layout);
    assert.deepEqual(paintGarden(layout, [2, 2], 'crystal', 'sequence'), layout);
    assert.deepEqual(paintGarden(layout, [0, 0], 'crystal', 'conditional'), layout);
    const changed = paintGarden(layout, [1, 2], 'path', 'conditional');
    assert.equal(changed.crystals.some(([x, y]) => x === 1 && y === 2), false);
    assert.equal(paintGarden(layout, [2, 2], 'crystal', 'conditional').crystals.length, 3);
});

test('custom garden layouts replay through the existing interpreter', () => {
    const layout = { start: [0, 0], goal: [4, 0], path: [[0, 0], [1, 0], [2, 0], [3, 0], [4, 0]], crystals: [] };
    assert.equal(runProgram({ ...presets.sequences, ...layout }, ['right', 'right', 'right', 'right']).success, true);
    assert.equal(runProgram({ ...presets.loops, ...layout }, ['right', 'right'], { repeat: true }).success, true);
    assert.equal(runProgram({ ...presets.conditions, ...layout, crystals: [[2, 0], [4, 0]] }, ['right', 'right', 'right', 'right'], { conditional: true }).success, true);
});
