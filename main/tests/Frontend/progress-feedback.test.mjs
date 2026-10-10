import test from 'node:test';
import assert from 'node:assert/strict';
import { mountProgressStrip, progressValues } from '../../resources/js/progress-strip.js';

const saved = { progress: { current_streak: 2, active_today: true, completed_count: 1, levels: { sequences: {}, loops: {}, conditions: {} } }, achievements: { xp: 1200, rank: 'Explorer' } };

test('progress display rejects incomplete or impossible snapshots', () => {
    assert.equal(progressValues(null), null);
    assert.equal(progressValues({ ...saved, achievements: { xp: -1, rank: 'Explorer' } }), null);
    assert.equal(progressValues({ ...saved, progress: { ...saved.progress, completed_count: 4 } }), null);
    assert.deepEqual(progressValues(saved), { streak: '2', today: 'Today saved', xp: '1,200', rank: 'Explorer', level: '1 / 3' });
});

test('only validated save feedback refreshes progress; failure keeps the snapshot and offers a retry', async () => {
    const events = {}, elements = {}, calls = [];
    for (const key of ['streak', 'today', 'xp', 'rank', 'level', 'retry']) elements[`[data-progress-${key}]`] = { textContent: 'old', hidden: true, addEventListener: (name, fn) => { events.retry = fn; } };
    const strip = { dataset: { progressUrl: '/play/progress' }, querySelector: selector => elements[selector], setAttribute() {}, removeAttribute() {} };
    const doc = { querySelector: () => strip, addEventListener: (name, fn) => { events[name] = fn; } };
    const win = { addEventListener() {} };
    let fails = true;
    mountProgressStrip(doc, win, async (url, options) => {
        calls.push({ url, options });
        if (fails) throw new Error('offline');
        return { ok: true, json: async () => saved };
    });
    events['kody:feedback']({ detail: { progressSaved: false } });
    assert.equal(calls.length, 0);
    events['kody:feedback']({ detail: { progressSaved: true } });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(elements['[data-progress-xp]'].textContent, 'old');
    assert.equal(elements['[data-progress-retry]'].hidden, false);
    fails = false;
    await events.retry();
    assert.equal(elements['[data-progress-xp]'].textContent, '1,200');
    assert.equal(elements['[data-progress-retry]'].hidden, true);
    assert.equal(calls[1].options.cache, 'no-store');
    assert.equal(calls[1].options.credentials, 'same-origin');
});
