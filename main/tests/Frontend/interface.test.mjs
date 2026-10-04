import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { mountTheme } from '../../resources/js/theme.js';
import { mountAccountLinks } from '../../resources/js/account-links.js';
import { quizFromFields } from '../../resources/js/games/quiz-designer.js';
import { mountQuiz } from '../../resources/js/games/choice-quiz.js';

test('semantic text and action palettes meet 4.5 to 1 contrast in both themes', () => {
    const css = readFileSync('resources/css/theme.css', 'utf8');
    const colors = (body) => Object.fromEntries([...body.matchAll(/--([\w-]+):\s*(#[\da-f]{6})/g)].map((match) => [match[1], match[2]]));
    const light = colors(css.match(/:root \{([^}]+)\}/)[1]);
    const dark = { ...light, ...colors(css.match(/:root\[data-theme="dark"\] \{([^}]+)\}/)[1]) };
    const luminance = (hex) => {
        const channels = [1, 3, 5].map((offset) => parseInt(hex.slice(offset, offset + 2), 16) / 255)
            .map((value) => value <= .04045 ? value / 12.92 : ((value + .055) / 1.055) ** 2.4);
        return channels[0] * .2126 + channels[1] * .7152 + channels[2] * .0722;
    };
    for (const palette of [light, dark]) {
        const pairs = ['page', 'surface', 'surface-soft', 'accent-soft'].flatMap((background) => [['text', background], ['muted', background]]);
        pairs.push(['accent-text', 'accent-soft'], ['on-accent', 'accent'], ['on-accent', 'accent-hover'],
            ['warm-text', 'warm-soft'], ['violet-text', 'violet-soft'], ['error-text', 'error-soft']);
        for (const [foreground, background] of pairs) {
            const a = luminance(palette[foreground]), b = luminance(palette[background]);
            assert.ok((Math.max(a, b) + .05) / (Math.min(a, b) + .05) >= 4.5, `${foreground} on ${background}`);
        }
    }
});

function themePage(value, matches = false, storageFails = false) {
    const events = {};
    const attributes = {};
    const button = { hidden: true, setAttribute: (key, value) => { attributes[key] = value; },
        addEventListener: (name, fn) => { events[name] = fn; } };
    const system = { matches, addEventListener: (name, fn) => { events.systemChange = fn; } };
    const storage = { getItem: () => { if (storageFails) throw new Error('private mode'); return value; },
        setItem: (key, selected) => { if (storageFails) throw new Error('private mode'); assert.equal(key, 'kody-theme'); value = selected; } };
    const document = { documentElement: { dataset: {} }, querySelectorAll: () => [button] };
    const window = { localStorage: storage, matchMedia: () => system,
        addEventListener: (name, fn) => { events[name] = fn; } };
    return { document, window, system, events, attributes, button };
}

test('theme respects system changes until a persisted explicit choice is made', () => {
    const page = themePage('invalid');
    mountTheme(page.document, page.window);
    assert.equal(page.document.documentElement.dataset.theme, 'light');
    assert.equal(page.button.hidden, false);
    page.system.matches = true; page.events.systemChange();
    assert.equal(page.attributes['aria-pressed'], 'true');
    page.events.click();
    assert.equal(page.document.documentElement.dataset.theme, 'light');
    page.events.systemChange();
    assert.equal(page.attributes['aria-pressed'], 'false');
    const nextPage = themePage('light', true);
    mountTheme(nextPage.document, nextPage.window);
    assert.equal(nextPage.document.documentElement.dataset.theme, 'light');
});

test('theme storage restrictions and cross-tab invalid or cleared preferences degrade safely', () => {
    const page = themePage(null, false, true);
    mountTheme(page.document, page.window);
    page.events.click();
    assert.equal(page.document.documentElement.dataset.theme, 'dark');
    page.events.storage({ key: 'unrelated', newValue: 'light' });
    assert.equal(page.document.documentElement.dataset.theme, 'dark');
    page.events.storage({ key: 'kody-theme', newValue: '<script>' });
    assert.equal(page.document.documentElement.dataset.theme, 'light');
    page.system.matches = true;
    page.events.storage({ key: null, newValue: null });
    assert.equal(page.attributes['aria-pressed'], 'true');
});

test('prepaint theme selection agrees with the interactive controller without trusted storage', () => {
    const script = readFileSync('resources/views/layouts/theme-head.blade.php', 'utf8').match(/<script>([\s\S]*)<\/script>/)[1];
    for (const [value, matches, fails] of [['light', true, false], ['dark', false, false], ['malformed', true, false], [null, false, true]]) {
        const page = themePage(value, matches, fails);
        runInNewContext(script, { document: page.document, window: page.window, localStorage: page.window.localStorage });
        const initial = page.document.documentElement.dataset.theme;
        mountTheme(page.document, page.window);
        assert.equal(page.document.documentElement.dataset.theme, initial);
    }
});

function accountPage(key, hash = '') {
    let calls = 0;
    const events = {};
    const input = { value: '' };
    const window = { location: { hash, pathname: '/account/link', search: '?from=email' },
        history: { replaceState(state, title, path) { assert.equal(path, '/account/link?from=email'); window.location.hash = ''; } },
        addEventListener: (name, fn) => { events[name] = fn; } };
    const form = { submit() { assert.equal(window.location.hash, ''); calls++; } };
    const nodes = key === 'token' ? { '#verify-link-form': form, '#verification-token': input }
        : { '#recovery-token-form': form, '#recovery-token': input };
    const document = { querySelector: (selector) => nodes[selector] };
    return { window, document, events, input, calls: () => calls };
}

test('initial and same-page verification and recovery links erase fragments before one POST', () => {
    for (const key of ['token', 'recovery']) {
        for (const initial of [true, false]) {
            const page = accountPage(key, initial ? `#${key}=${'a'.repeat(64)}` : '');
            mountAccountLinks(page.document, page.window);
            if (!initial) { page.window.location.hash = `#${key}=${'a'.repeat(64)}`; page.events.hashchange(); }
            assert.equal(page.calls(), 1);
            assert.equal(page.input.value, 'a'.repeat(64));
            page.window.location.hash = `#${key}=${'b'.repeat(64)}`; page.events.hashchange();
            assert.equal(page.calls(), 1);
            assert.equal(page.window.location.hash, '');
        }
    }
});

test('malformed account links never submit and leave valid same-page retries available', () => {
    const page = accountPage('token', '#token=invalid');
    mountAccountLinks(page.document, page.window);
    assert.equal(page.calls(), 0);
    assert.equal(page.window.location.hash, '');
    page.window.location.hash = `#recovery=${'a'.repeat(64)}`; page.events.hashchange();
    assert.equal(page.calls(), 0);
    page.window.location.hash = `#token=${'a'.repeat(64)}`; page.events.hashchange();
    assert.equal(page.calls(), 1);
});

test('unsaved quiz scenarios enforce creator limits while treating markup as text', () => {
    const fields = { quiz_title: '<script>title</script>', quiz_question: 'Pick one', quiz_a: 'A', quiz_b: 'B', quiz_answer: 'b', quiz_explanation: 'Why B' };
    assert.equal(quizFromFields(fields).title, fields.quiz_title);
    for (const changes of [{ quiz_explanation: '' }, { quiz_b: 'A' }, { quiz_title: '😀'.repeat(101) }, { quiz_question: 'x'.repeat(501) }, { quiz_a: 'x'.repeat(301) }, { quiz_answer: 'c' }]) {
        assert.throws(() => quizFromFields({ ...fields, ...changes }));
    }
    assert.equal(quizFromFields({ ...fields, quiz_title: '😀'.repeat(100) }).title.length, 200);
});

test('quiz completion serializes repeated submissions and restores controls after network failure', async () => {
    const saved = { fetch: globalThis.fetch, document: globalThis.document };
    const fields = { quiz_title: 'Idea', quiz_question: 'Pick one', quiz_a: 'A', quiz_b: 'B', quiz_answer: 'a', quiz_explanation: 'Why A' };
    const feedback = { textContent: '' };
    const controls = [{ disabled: false }, { disabled: false }];
    let submit, finish, calls = 0;
    const root = { dataset: { practiceQuiz: JSON.stringify(quizFromFields(fields)), completionUrl: '/complete' },
        classList: { toggle() {} }, setAttribute() {}, querySelectorAll: () => controls,
        querySelector: (selector) => selector === '[data-quiz-feedback]' ? feedback : selector === 'form'
            ? { addEventListener: (name, callback) => { submit = callback; } } : { value: 'a' } };
    try {
        globalThis.document = { querySelector: () => ({ content: 'test-csrf' }) };
        globalThis.fetch = async () => { calls++; await new Promise((resolve) => { finish = resolve; }); throw new Error('offline'); };
        mountQuiz(root);
        const pending = submit({ preventDefault() {} });
        await submit({ preventDefault() {} });
        assert.equal(calls, 1);
        assert.equal(controls.every((control) => control.disabled), true);
        finish(); await pending;
        assert.equal(controls.every((control) => !control.disabled), true);
        assert.match(feedback.textContent, /couldn’t confirm/);
        globalThis.fetch = async () => { calls++; return { ok: true, json: async () => ({ message: 'Saved by server.' }) }; };
        await submit({ preventDefault() {} });
        assert.equal(calls, 2);
        assert.match(feedback.textContent, /Saved by server/);
    } finally { Object.assign(globalThis, saved); }
});
