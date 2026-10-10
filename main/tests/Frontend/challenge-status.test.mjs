import test from 'node:test';
import assert from 'node:assert/strict';
import { mountChallengeStatus } from '../../resources/js/challenge-status.js';

async function withStatusPage(run) {
    const saved = { window: globalThis.window, fetch: globalThis.fetch, setTimeout: globalThis.setTimeout, clearTimeout: globalThis.clearTimeout };
    const timers = new Map();
    const events = {};
    let id = 0;
    globalThis.window = { location: { origin: 'https://kody.test' }, addEventListener: (name, callback) => { events[name] = callback; } };
    globalThis.setTimeout = (callback) => { timers.set(++id, callback); return id; };
    globalThis.clearTimeout = (key) => timers.delete(key);
    const nodes = Object.fromEntries(['status', 'feedback', 'counts'].map((name) => [name, { textContent: '' }]));
    const root = { dataset: { challengeStatus: '/challenge-attempts/id/status' },
        querySelector: (selector) => nodes[selector.match(/data-attempt-(\w+)/)[1]] };
    const tick = async () => {
        const [key, callback] = timers.entries().next().value;
        timers.delete(key);
        await callback();
    };
    try { await run({ root, nodes, timers, events, tick }); }
    finally { Object.assign(globalThis, saved); }
}

test('attempt feedback renders as text and polling stops at a durable terminal result', async () => {
    await withStatusPage(async ({ root, nodes, timers, tick }) => {
        let calls = 0;
        globalThis.fetch = async (url, options) => {
            calls++;
            assert.equal(url.origin, 'https://kody.test');
            assert.equal(options.credentials, 'same-origin');
            assert.equal(options.cache, 'no-store');
            return { ok: true, status: 200, json: async () => ({ status: 'Passed', completed: true,
                passed_cases: 2, total_cases: 2, feedback: '<script>untrusted</script>' }) };
        };
        mountChallengeStatus(root);
        await tick();
        assert.equal(nodes.feedback.textContent, '<script>untrusted</script>');
        assert.equal(nodes.counts.textContent, '2 / 2 test cases passed.');
        assert.equal(timers.size, 0);
        assert.equal(calls, 1);
    });
});

test('status polling stops on revoked authorization and refuses external URLs', async () => {
    await withStatusPage(async ({ root, timers, tick }) => {
        let calls = 0;
        globalThis.fetch = async () => { calls++; return { ok: false, status: 403 }; };
        root.dataset.challengeStatus = 'https://external.test/status';
        mountChallengeStatus(root);
        assert.equal(timers.size, 0);
        root.dataset.challengeStatus = '/challenge-attempts/id/status';
        mountChallengeStatus(root);
        await tick();
        assert.equal(calls, 1);
        assert.equal(timers.size, 0);
    });
});

test('transient status failures remain retryable and closing the page stops polling', async () => {
    await withStatusPage(async ({ root, nodes, timers, events, tick }) => {
        globalThis.fetch = async () => { throw new Error('offline'); };
        mountChallengeStatus(root);
        await tick();
        assert.equal(timers.size, 1);
        assert.equal(nodes.status.textContent, '');
        events.pagehide();
        assert.equal(timers.size, 0);
    });
});

test('restoring a pending attempt from page cache resumes polling without duplicating requests', async () => {
    await withStatusPage(async ({ root, timers, events, tick }) => {
        let calls = 0;
        globalThis.fetch = async () => { calls++; return { ok: true, json: async () => ({ status: 'Evaluating', completed: false }) }; };
        mountChallengeStatus(root);
        await tick();
        events.pagehide();
        assert.equal(timers.size, 0);
        events.pageshow({ persisted: true });
        await tick();
        assert.equal(calls, 2);
        assert.equal(timers.size, 1);
    });
});

test('incomplete and impossible terminal results never display a false pass or stop updates', async () => {
    for (const patch of [{ completed: false }, { total_cases: -1 }, { passed_cases: 3 }, { feedback: null }]) {
        await withStatusPage(async ({ root, nodes, timers, tick }) => {
            globalThis.fetch = async () => ({ ok: true, json: async () => ({ status: 'Passed', completed: true, feedback: 'Passed', passed_cases: 2, total_cases: 2, ...patch }) });
            mountChallengeStatus(root);
            await tick();
            assert.equal(nodes.status.textContent, '');
            assert.equal(timers.size, 1);
        });
    }
});
