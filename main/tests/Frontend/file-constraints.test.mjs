import test from 'node:test';
import assert from 'node:assert/strict';
import { validateFileSelection, mountCredentialPreviews } from '../../resources/js/file-constraints.js';

test('validateFileSelection enforces extension, MIME, size, and retained-plus-new limits', () => {
    const options = {
        maxBytes: 5 * 1024 * 1024,
        maxFiles: 1,
        allowedTypes: ['application/pdf', 'image/jpeg', 'image/png'],
        allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
    };

    const ok = validateFileSelection([{ name: 'diploma.pdf', type: 'application/pdf', size: 120000 }], options);
    assert.equal(ok.valid, true);
    assert.match(ok.message, /diploma\.pdf/);

    const badExt = validateFileSelection([{ name: 'script.svg', type: 'image/svg+xml', size: 1000 }], options);
    assert.equal(badExt.valid, false);
    assert.match(badExt.message, /unsupported file type/i);

    const badMime = validateFileSelection([{ name: 'spoof.pdf', type: 'text/html', size: 1000 }], options);
    assert.equal(badMime.valid, false);
    assert.match(badMime.message, /unsupported MIME type/i);

    const tooLarge = validateFileSelection([{ name: 'huge.pdf', type: 'application/pdf', size: 6 * 1024 * 1024 }], options);
    assert.equal(tooLarge.valid, false);
    assert.match(tooLarge.message, /exceeds/i);

    const overCombined = validateFileSelection(
        [
            { name: 'a.pdf', type: 'application/pdf', size: 1000 },
            { name: 'b.pdf', type: 'application/pdf', size: 1000 },
        ],
        {
            maxBytes: 20 * 1024 * 1024,
            maxFiles: 5,
            retainedCount: 4,
            allowedTypes: ['application/pdf'],
            allowedExtensions: ['pdf'],
        },
    );
    assert.equal(overCombined.valid, false);
    assert.match(overCombined.message, /1 slot remaining/);
});

test('mountCredentialPreviews lazily loads sandboxed preview only when details is toggled open', () => {
    const appended = [];
    const host = {
        dataset: {},
        replaceChildren() { appended.length = 0; },
        append(...nodes) { appended.push(...nodes); },
    };
    const listeners = {};
    const details = {
        open: false,
        dataset: {
            credentialPreviewSrc: '/manage/instructors/1/credential?inline=1',
            credentialPreviewKind: 'pdf',
            credentialPreviewTitle: 'Teaching credentials',
        },
        querySelector: () => host,
        addEventListener: (name, fn) => { listeners[name] = fn; },
    };
    const doc = {
        querySelectorAll: () => [details],
        createElement: (tag) => ({ tag, append() {} }),
    };

    mountCredentialPreviews(doc);
    assert.equal(appended.length, 0);

    details.open = true;
    listeners.toggle();
    assert.equal(host.dataset.loaded, '1');
    assert.equal(appended[0].tag, 'iframe');
    assert.equal(appended[0].src, '/manage/instructors/1/credential?inline=1');

    listeners.toggle();
    assert.equal(appended.length, 2);
});
