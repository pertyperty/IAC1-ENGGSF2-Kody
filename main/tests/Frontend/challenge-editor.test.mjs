import test from 'node:test';
import assert from 'node:assert/strict';
import { mountUnsavedChanges } from '../../resources/js/unsaved-changes.js';
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

test('challenge editor releases Tab focus after Escape and can undo a starter reset', () => {
    const listeners = {};
    let focused = false;
    const textarea = {
        value: 'print(1)\nprint(2)',
        selectionStart: 0,
        selectionEnd: 0,
        scrollTop: 0,
        addEventListener(type, fn) { listeners[type] = fn; },
        focus() { focused = true; },
        dispatchEvent(event) { listeners[event.type]?.(event); },
        setSelectionRange(start, end) { this.selectionStart = start; this.selectionEnd = end; },
        setRangeText(text, start, end) { this.value = this.value.slice(0, start) + text + this.value.slice(end); },
    };
    const gutter = { textContent: '', scrollTop: 0 };
    const bytesLabel = { textContent: '', dataset: {} };
    let resetClick = null;
    const resetButton = {
        hidden: true,
        addEventListener(type, fn) { if (type === 'click') resetClick = fn; },
    };
    let undoClick;
    let undoFocused = false;
    const undoButton = {
        hidden: true,
        focus() { undoFocused = true; },
        addEventListener(type, fn) { if (type === 'click') undoClick = fn; },
    };
    let hintClick;
    const hintButton = { hidden: true, addEventListener(type, fn) { if (type === 'click') hintClick = fn; } };
    const form = {
        querySelector(selector) {
            if (selector === '[data-editor-insert-hint]') return hintButton;
            if (selector === '[data-editor-bytes]') return bytesLabel;
            if (selector === '[data-editor-reset-starter]') return resetButton;
            if (selector === '[data-editor-undo-reset]') return undoButton;
            return null;
        },
    };
    const root = {
        dataset: { starterCode: 'print(1)', hintComment: '# Print only the output.' },
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

    hintClick();
    assert.equal(textarea.value, '# Print only the output.\n    print(1)\nprint(2)');
    assert.equal(hintButton.disabled, true);

    const beforeReset = textarea.value;
    textarea.setSelectionRange(2, 5);
    textarea.scrollTop = 24;
    // Reset restores starter code and offers a keyboard-accessible undo.
    resetClick();
    assert.equal(textarea.value, 'print(1)');
    assert.equal(resetButton.hidden, true);
    assert.equal(undoButton.hidden, false);
    assert.equal(undoFocused, true);
    undoClick();
    assert.equal(textarea.value, beforeReset);
    assert.deepEqual([textarea.selectionStart, textarea.selectionEnd, textarea.scrollTop], [2, 5, 24]);
    assert.equal(undoButton.hidden, true);
    assert.equal(focused, true);
});

test('challenge leave warning protects changed source, including after returning from a submission', () => {
    const source = { name: 'source_code', type: 'textarea', value: 'starter', closest: () => null };
    const formEvents = {};
    const windowEvents = {};
    const form = {
        closest: () => form,
        querySelectorAll(selector) { assert.equal(selector, 'textarea.challenge-source'); return [source]; },
        addEventListener(type, fn) { formEvents[type] = fn; },
    };
    const doc = { querySelectorAll: () => [form] };
    const win = { addEventListener(type, fn) { windowEvents[type] = fn; } };
    mountUnsavedChanges(doc, win, { roots: 'form[data-challenge-solution]', fields: 'textarea.challenge-source' });
    const warns = () => {
        const event = { preventDefault() { this.prevented = true; } };
        windowEvents.beforeunload(event);
        return Boolean(event.prevented);
    };
    assert.equal(warns(), false);
    source.value = 'edited';
    assert.equal(warns(), true);
    formEvents.submit({ target: form, defaultPrevented: true });
    assert.equal(warns(), true);
    formEvents.submit({ target: form, defaultPrevented: false });
    assert.equal(warns(), false);
    windowEvents.pageshow();
    assert.equal(warns(), true);
    source.value = 'starter';
    assert.equal(warns(), false);
});
