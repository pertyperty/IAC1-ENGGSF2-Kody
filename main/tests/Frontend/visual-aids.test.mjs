import test from 'node:test';
import assert from 'node:assert/strict';
import {
    tokenizeCode,
    buildTruthTable,
    analyzeSortArray,
    numberLineMetrics,
    previewGardenRoute,
    splitQuizPrompt,
} from '../../resources/js/games/visual-aids.js';

test('tokenizeCode preserves exact text across all pseudocode templates', () => {
    const samples = [
        'kody.move_right()',
        'value = value * 2',
        'canvas.paint(1, 2, "mint")',
        'swap(items, 1, 3)',
        'inputs[2] = not inputs[2]',
        'text = text + "_ok"',
        'stack.duplicate_top()',
        'cp draft.txt publish.txt',
    ];
    for (const sample of samples) {
        const tokens = tokenizeCode(sample);
        assert.equal(tokens.map((t) => t.value).join(''), sample);
        assert.ok(tokens.some((t) => t.type !== 'plain'));
    }
});

test('buildTruthTable generates full 2-input tables and summary multi-input rules with active row', () => {
    const and2 = buildTruthTable('and', [true, false]);
    assert.equal(and2.mode, 'full');
    assert.equal(and2.rows.length, 4);
    assert.equal(and2.rows.filter((r) => r.active).length, 1);
    assert.equal(and2.rows.find((r) => r.active).output, false);

    const xor3 = buildTruthTable('xor', [true, false, false]);
    assert.equal(xor3.mode, 'summary');
    assert.equal(xor3.rows.find((r) => r.active).output, true);
});

test('analyzeSortArray flags adjacent inversions and clears when sorted', () => {
    const unsorted = analyzeSortArray([5, 1, 4, 2, 3]);
    assert.equal(unsorted.adjacentErrors, 2);
    assert.equal(unsorted.inversions, 6);
    const sorted = analyzeSortArray([1, 2, 3, 4, 5]);
    assert.equal(sorted.adjacentErrors, 0);
    assert.equal(sorted.inversions, 0);
});

test('numberLineMetrics computes bounded pins and delta labels', () => {
    const mid = numberLineMetrics(2, 5, 18);
    assert.equal(mid.deltaLabel, '+13 to reach target');
    assert.ok(mid.startPct < mid.currentPct && mid.currentPct < mid.targetPct);
    const hit = numberLineMetrics(2, 18, 18);
    assert.equal(hit.deltaLabel, 'Exact target match');
});

test('previewGardenRoute tracks loop passes, crystal collection, and off-path collisions', () => {
    const loopInstance = {
        width: 5, height: 4, start: [0, 2], goal: [2, 2], mode: 'loop',
        path: [[0, 2], [1, 2], [2, 2]], crystals: [],
    };
    const loopPreview = previewGardenRoute(loopInstance, ['right'], { repeat: true });
    assert.equal(loopPreview.collision, null);
    assert.equal(loopPreview.segments.length, 2);
    assert.deepEqual(loopPreview.segments.map((s) => s.cycle), [1, 2]);

    const crashPreview = previewGardenRoute(loopInstance, ['up']);
    assert.ok(crashPreview.collision);
    assert.equal(crashPreview.collision.step, 1);
});

test('splitQuizPrompt extracts Python code snippets while leaving single-line questions intact', () => {
    const plain = splitQuizPrompt('Why use a loop?');
    assert.equal(plain.code, '');
    assert.equal(plain.question, 'Why use a loop?');

    const py = splitQuizPrompt('Python: score = 10\nif score >= 10: badge = "ready"\nelse: badge = "try"\nWhat is badge?');
    assert.equal(py.lead, 'Python');
    assert.equal(py.question, 'What is badge?');
    assert.match(py.code, /if score >= 10/);
});
