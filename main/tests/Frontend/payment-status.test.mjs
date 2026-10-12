import test from 'node:test';
import assert from 'node:assert/strict';
import { mountPaymentStatus } from '../../resources/js/payment-status.js';

const path = '/wallet/purchases/00000000-0000-4000-8000-000000000001';
async function fixture(run) {
    const original = globalThis.fetch, timers = new Map(), events = {}, listeners = {}; let next = 0;
    const nodes = Object.fromEntries(['refresh', 'state', 'feedback', 'checkout', 'receipt'].map(name => [name, { textContent: '', hidden: false, disabled: false, addEventListener: (event, fn) => listeners[`${name}:${event}`] = fn, removeAttribute(name) { delete this[name]; } }]));
    const balance = { textContent: '' }, doc = { hidden: false, querySelectorAll: () => [balance], addEventListener: (name, fn) => events[name] = fn };
    const root = { ownerDocument: doc, dataset: { paymentStatus: path + '/status' }, querySelector: selector => nodes[selector.match(/data-payment-(\w+)/)[1]] };
    const win = { location: { origin: 'https://kody.test' }, setTimeout: fn => { timers.set(++next, fn); return next; }, clearTimeout: id => timers.delete(id), addEventListener: (name, fn) => events[name] = fn };
    const settle = async () => { for (let i = 0; i < 12; i++) await Promise.resolve(); };
    try { await run({ root, win, doc, nodes, balance, timers, events, listeners, settle }); } finally { globalThis.fetch = original; }
}

test('payment polling accepts only owned server snapshots and stops after verified success', async () => fixture(async ({ root, win, nodes, balance, timers, settle }) => {
    globalThis.fetch = async (url, options) => {
        assert.equal(url.href, 'https://kody.test' + path + '/status'); assert.equal(options.cache, 'no-store');
        return { ok: true, status: 200, json: async () => ({ state: 'Succeeded', verified: true, available: 105, checkout: null, receipt: path + '/receipt' }) };
    };
    mountPaymentStatus(root, win); await settle();
    assert.equal(nodes.state.textContent, 'Succeeded'); assert.equal(balance.textContent, '105');
    assert.equal(nodes.receipt.hidden, false); assert.equal(nodes.checkout.hidden, true); assert.equal(timers.size, 0);
}));

test('payment URLs remain local and unknown or contradictory snapshots do not update balances', async () => fixture(async ({ root, win, nodes, balance, settle }) => {
    let calls = 0; globalThis.fetch = async () => { calls++; return { ok: true, status: 200, json: async () => ({ state: 'Succeeded', verified: false, available: 100, receipt: 'https://external.test' }) }; };
    root.dataset.paymentStatus = 'https://external.test/status'; mountPaymentStatus(root, win); await settle(); assert.equal(calls, 0);
    root.dataset.paymentStatus = path + '/status'; mountPaymentStatus(root, win); await settle();
    assert.equal(balance.textContent, ''); assert.match(nodes.feedback.textContent, /Could not refresh/);
}));

test('payment polling pauses on navigation and resumes pending work after history restoration', async () => fixture(async ({ root, win, doc, nodes, timers, events, settle }) => {
    let calls = 0; globalThis.fetch = async () => { calls++; return { ok: true, status: 200, json: async () => ({ state: 'Pending', verified: false, available: 0, checkout: 'https://external.test', receipt: path.replace(/001$/, '002') + '/receipt' }) }; };
    mountPaymentStatus(root, win); await settle(); assert.equal(timers.size, 1); assert.equal(nodes.checkout.hidden, true); assert.equal(nodes.receipt.hidden, true);
    events.pagehide(); assert.equal(timers.size, 0); events.pageshow({ persisted: true }); await settle(); assert.equal(calls, 2);
    doc.hidden = true; events.visibilitychange(); assert.equal(timers.size, 0);
}));
