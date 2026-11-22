import test from 'node:test';
import assert from 'node:assert/strict';
import { applyTabIndent, lineNumbersText, mountChallengeEditor, utf8ByteLength } from '../../resources/js/challenge-editor.js';

test('challenge editor line numbers and utf-8 byte lengths are accurate', () => {
    assert.equal(lineNumbersText(''), '1');
    assert.equal(lineNumbersText('a\nb\nc'), '1\n2\n3');
    assert.equal(utf8ByteLength('print("hi")'), 11);
    assert.equal(utf8ByteLength('✓'), 3);
});

test('challenge editor Tab and Shift+Tab indent and outdent single and multi-line selections', () => {
    const inserted = applyTabIndent('x = 1', 0, 0, false);
    assert.equal(inserted.value, '    x = 1');
    assert.equal(inserted.start, 4);

    const multi = applyTabIndent('a = 1\nb = 2', 0, 11, false);
    assert.equal(multi.value, '    a = 1\n    b = 2');

    const outdented = applyTabIndent(multi.value, 0, multi.value.length, true);
    assert.equal(outdented.value, 'a = 1\nb = 2');
});

test('challenge editor releases Tab focus after Escape and supports resetting starter code', () => {
    const listeners = {};
    let focused = false;
    const textarea = {
        value: 'print(1)\nprint(2)',
        selectionStart: 0,
        selectionEnd: 0,
        scrollTop: 0,
        addEventListener(type, fn) { listeners[type] = fn; },
        focus() { focused = true; },
    };
    const gutter = { textContent: '', scrollTop: 0 };
    const bytesLabel = { textContent: '', dataset: {} };
    let resetClick = null;
    const resetButton = {
        hidden: true,
        addEventListener(type, fn) { if (type === 'click') resetClick = fn; },
    };
    const form = {
        querySelector(selector) {
            if (selector === '[data-editor-bytes]') return bytesLabel;
            if (selector === '[data-editor-reset-starter]') return resetButton;
            return null;
        },
    };
    const root = {
        dataset: { starterCode: 'print(1)' },
        closest() { return form; },
        querySelector(selector) {
            if (selector === 'textarea.challenge-source') return textarea;
            if (selector === '[data-editor-gutter]') return gutter;
            return null;
        },
    };

    mountChallengeEditor(root);
    assert.equal(gutter.textContent, '1\n2');
    assert.equal(bytesLabel.textContent, '17 / 65,536 bytes');
    assert.equal(resetButton.hidden, false);

    // Tab indents by default
    let prevented = false;
    listeners.keydown({ key: 'Tab', shiftKey: false, preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(textarea.value.startsWith('    print(1)'), true);

    // Escape releases Tab so keyboard users are never trapped (WCAG 2.1.2)
    prevented = false;
    listeners.keydown({ key: 'Escape', preventDefault() { prevented = true; } });
    listeners.keydown({ key: 'Tab', shiftKey: false, preventDefault() { prevented = true; } });
    assert.equal(prevented, false);

    // Reset restores starter code and hides button
    resetClick();
    assert.equal(textarea.value, 'print(1)');
    assert.equal(resetButton.hidden, true);
    assert.equal(focused, true);
});
