import test from 'node:test';
import assert from 'node:assert/strict';
import { mountContentReactions } from '../../resources/js/content-reactions.js';

function page() {
    globalThis.window = { location: { origin: 'https://kody.test' } };
    const status = { textContent: '' };
    const forms = ['Like', 'Helpful', 'Favorite'].map((choice) => {
        const inputs = { record_version: { value: '0' }, reaction: { value: choice }, _token: { value: 'test-token' } };
        const button = { disabled: false, attributes: {}, setAttribute(key, value) { this.attributes[key] = value; } };
        return { dataset: { reactionChoice: choice }, action: '/feedback/module/1', inputs, button,
            querySelector: (selector) => selector === 'button' ? button : inputs[selector.match(/name="([^"]+)"/)[1]],
            addEventListener(name, handler) { this.submit = () => handler({ preventDefault() {} }); } };
    });
    const state = { kind: 'module', content_id: 1, eligible: true, reaction: null, record_version: 0, counts: { Like: 0, Helpful: 0, Favorite: 0 } };
    const root = { dataset: { contentReactions: JSON.stringify(state) }, querySelectorAll: () => forms, querySelector: () => status };
    return { root, forms, status, state };
}

test('reactions add replace and remove in place using server counts and version', async () => {
    const { root, forms, state, status } = page();
    mountContentReactions(root, async (url, options) => {
        assert.equal(url.origin, window.location.origin);
        assert.equal(options.credentials, 'same-origin');
        assert.equal(options.cache, 'no-store');
        assert.equal(options.redirect, 'error');
        assert.ok(options.signal instanceof AbortSignal);
        const data = JSON.parse(options.body);
        assert.equal(data.record_version, state.record_version);
        state.record_version++;
        state.reaction = data.reaction;
        state.counts = { Like: 0, Helpful: 0, Favorite: 0 };
        if (data.reaction) state.counts[data.reaction] = 1;
        return { ok: true, json: async () => ({ ...state }) };
    });
    await forms[0].submit();
    assert.equal(forms[0].button.attributes['aria-pressed'], 'true');
    assert.equal(forms[0].inputs.reaction.value, '');
    await forms[1].submit();
    assert.equal(forms[0].button.attributes['aria-pressed'], 'false');
    assert.equal(forms[1].inputs.record_version.value, 2);
    await forms[1].submit();
    assert.equal(status.textContent, 'Reaction removed.');
    assert.equal(forms[1].button.textContent, 'Helpful · 0');
});

test('revoked access and stale versions stop further writes; external endpoints are refused', async () => {
    for (const code of [403, 404, 419, 422]) {
        const { root, forms, status } = page();
        let calls = 0;
        mountContentReactions(root, async () => { calls++; return { ok: false, status: code }; });
        forms[0].action = 'https://external.test/feedback';
        await forms[0].submit();
        assert.equal(calls, 0);
        forms[0].action = '/feedback/module/1';
        await forms[0].submit();
        await forms[1].submit();
        assert.equal(calls, 1);
        assert.equal(forms[1].button.disabled, true);
        assert.match(status.textContent, /Reload/);
    }
});

test('busy requests are serialized and uncertain network results retry the original version', async () => {
    const { root, forms } = page();
    let calls = 0;
    let finish;
    const bodies = [];
    mountContentReactions(root, async (url, options) => {
        calls++;
        bodies.push(options.body);
        if (calls === 1) await new Promise((resolve) => { finish = resolve; });
        throw new Error('offline');
    });
    const pending = forms[0].submit();
    await forms[1].submit();
    assert.equal(calls, 1);
    finish();
    await pending;
    await forms[0].submit();
    assert.equal(bodies[0], bodies[1]);
    assert.equal(forms[0].button.disabled, false);
});

test('malformed responses never inject labels or spoof counts and target identity', async () => {
    for (const patch of [{ content_id: 2 }, { counts: { Like: -1 } }, { reaction: '<script>bad()</script>' }]) {
        const { root, forms, state, status } = page();
        mountContentReactions(root, async () => ({ ok: true, json: async () => ({ ...state, ...patch }) }));
        await forms[0].submit();
        assert.equal(forms[0].inputs.record_version.value, 0);
        assert.equal(forms[0].button.textContent, 'Like · 0');
        assert.match(status.textContent, /Could not confirm/);
    }
});
