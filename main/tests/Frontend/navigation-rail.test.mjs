import test from 'node:test';
import assert from 'node:assert/strict';
import { mountNavigationRail } from '../../resources/js/navigation-rail.js';

function fixture() {
    const frames = [];
    const storage = new Map([['kody-navigation-scroll:42', '746']]);
    let finishFonts;
    const ready = new Promise(resolve => { finishFonts = resolve; });
    const scroller = new EventTarget();
    scroller.clientHeight = 400;
    scroller.maximum = 730;
    let top = 0;
    Object.defineProperty(scroller, 'scrollTop', {
        get: () => top,
        set: value => { top = Math.min(value, scroller.maximum); },
    });
    const toggle = Object.assign(new EventTarget(), { setAttribute() {} });
    const rail = Object.assign(new EventTarget(), {
        dataset: { navigationUser: '42' },
        querySelector: selector => selector === '.workspace-nav' ? scroller : toggle,
        querySelectorAll: () => [],
        contains: () => false,
    });
    const doc = {
        querySelector: () => rail,
        documentElement: { dataset: { rail: 'expanded' } },
        fonts: { ready },
    };
    const win = Object.assign(new EventTarget(), {
        requestAnimationFrame: callback => frames.push(callback),
        sessionStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value) },
    });
    const frame = () => frames.splice(0).forEach(callback => callback());
    mountNavigationRail(doc, win);
    return { scroller, frame, finishFonts, storage };
}

test('sidebar restores the saved offset after font layout expands a temporarily clamped range', async () => {
    const state = fixture();
    state.frame();
    assert.equal(state.scroller.scrollTop, 730);
    state.scroller.dispatchEvent(new Event('scroll'));
    state.scroller.maximum = 746;
    state.finishFonts();
    await Promise.resolve();
    state.frame();
    state.frame();
    assert.equal(state.scroller.scrollTop, 746);
});

for (const event of ['pointerdown', 'wheel', 'keydown']) {
    test(`late sidebar restoration respects user ${event} input`, async () => {
        const state = fixture();
        state.frame();
        state.scroller.dispatchEvent(new Event(event));
        state.scroller.scrollTop = 300;
        state.scroller.dispatchEvent(new Event('scroll'));
        state.scroller.maximum = 746;
        state.finishFonts();
        await Promise.resolve();
        state.frame();
        state.frame();
        assert.equal(state.scroller.scrollTop, 300);
        assert.equal(state.storage.get('kody-navigation-scroll:42'), '300');
    });
}
