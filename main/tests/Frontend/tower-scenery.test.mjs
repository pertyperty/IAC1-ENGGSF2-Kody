import test from 'node:test';
import assert from 'node:assert/strict';
import { towerAltitude } from '../../resources/js/tower-scenery.js';

test('ascent captions use the installed floors rather than assuming a fifty-floor tower', () => {
    const floors = count => Array.from({ length: count }, (_, index) => index + 1);
    assert.deepEqual(towerAltitude(1, floors(80)), { zone: 4, caption: 'Planet trail · floors 65–80' });
    assert.deepEqual(towerAltitude(0, floors(80)), { zone: 0, caption: 'Meadow · floors 1–16' });
    assert.equal(towerAltitude(1, floors(50)).caption, 'Planet trail · floors 41–50');
    assert.equal(towerAltitude(1, [1, 2, 26, 71, 80]).caption, 'Planet trail · floors 80–80');
    assert.equal(towerAltitude(0, []).caption, 'Meadow');
});
