const MAX_BYTES = 65536;
const INDENT = '    ';

export function utf8ByteLength(text) {
    return new TextEncoder().encode(text).length;
}

export function lineNumbersText(text) {
    const count = Math.max(1, text.split('\n').length);
    return Array.from({ length: count }, (_, index) => String(index + 1)).join('\n');
}

export function applyTabIndent(value, start, end, outdent = false) {
    if (!outdent && start === end) {
        return {
            value: value.slice(0, start) + INDENT + value.slice(end),
            start: start + INDENT.length,
            end: start + INDENT.length,
        };
    }

    const lineStart = value.lastIndexOf('\n', Math.max(0, start - 1)) + 1;
    const block = value.slice(lineStart, end);
    const lines = block.split('\n');
    let firstDelta = 0;
    let totalDelta = 0;

    const updated = lines.map((line, index) => {
        if (outdent) {
            const match = line.match(/^( {1,4}|\t)/);
            if (!match) return line;
            const removed = match[0].length;
            if (index === 0) firstDelta = -removed;
            totalDelta -= removed;
            return line.slice(removed);
        }
        if (index === 0) firstDelta = INDENT.length;
        totalDelta += INDENT.length;
        return INDENT + line;
    });

    return {
        value: value.slice(0, lineStart) + updated.join('\n') + value.slice(end),
        start: Math.max(lineStart, start + firstDelta),
        end: Math.max(lineStart, end + totalDelta),
    };
}

export function mountChallengeEditor(root) {
    if (!root || root.dataset.editorMounted === 'true') return;
    const textarea = root.querySelector('textarea.challenge-source');
    const gutter = root.querySelector('[data-editor-gutter]');
    if (!textarea) return;
    root.dataset.editorMounted = 'true';

    const form = root.closest('form');
    const bytesLabel = form?.querySelector('[data-editor-bytes]');
    const resetButton = form?.querySelector('[data-editor-reset-starter]');
    const starterCode = root.dataset.starterCode ?? '';
    const hintButton = form?.querySelector('[data-editor-insert-hint]');
    const hint = root.dataset.hintComment ?? '';
    let escapeTab = false;

    const sync = () => {
        if (gutter) {
            gutter.textContent = lineNumbersText(textarea.value);
            gutter.scrollTop = textarea.scrollTop;
        }
        if (bytesLabel) {
            const bytes = utf8ByteLength(textarea.value);
            bytesLabel.textContent = `${bytes.toLocaleString('en-US')} / ${MAX_BYTES.toLocaleString('en-US')} bytes`;
            bytesLabel.dataset.overLimit = String(bytes > MAX_BYTES);
        }
        if (hintButton && hint) {
            hintButton.hidden = false;
            hintButton.disabled = textarea.value.includes(hint) || utf8ByteLength(`${hint}\n${textarea.value}`) > MAX_BYTES;
        }
        if (resetButton && starterCode) {
            resetButton.hidden = textarea.value === starterCode;
        }
    };

    textarea.addEventListener('input', () => {
        escapeTab = false;
        sync();
    });
    textarea.addEventListener('scroll', () => {
        if (gutter) gutter.scrollTop = textarea.scrollTop;
    });
    textarea.addEventListener('blur', () => {
        escapeTab = false;
    });
    textarea.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            escapeTab = true;
            return;
        }
        if (event.key !== 'Tab' || event.ctrlKey || event.metaKey || event.altKey) {
            escapeTab = false;
            return;
        }
        if (escapeTab) {
            escapeTab = false;
            return;
        }
        event.preventDefault();
        const next = applyTabIndent(textarea.value, textarea.selectionStart, textarea.selectionEnd, event.shiftKey);
        textarea.value = next.value;
        textarea.selectionStart = next.start;
        textarea.selectionEnd = next.end;
        sync();
    });

    if (resetButton && starterCode) {
        resetButton.addEventListener('click', () => {
            textarea.value = starterCode;
            textarea.focus();
            sync();
        });
    }

    if (hintButton && hint) {
        hintButton.addEventListener('click', () => {
            if (hintButton.disabled) return;
            textarea.setRangeText(`${hint}\n`, 0, 0, 'preserve');
            textarea.focus();
            sync();
        });
    }

    sync();
    if (typeof window !== 'undefined' && window.location.hash === '#source-code') {
        const target = new URLSearchParams(window.location.search).get('line');
        const lines = textarea.value.split('\n');
        if (target && /^[1-9]\d{0,5}$/.test(target) && Number(target) <= lines.length) {
            const index = Number(target) - 1;
            const start = lines.slice(0, index).reduce((length, line) => length + line.length + 1, 0);
            textarea.focus();
            textarea.setSelectionRange(start, start + lines[index].length);
            textarea.scrollTop = Math.max(0, (index - 2) * parseFloat(window.getComputedStyle(textarea).lineHeight));
            sync();
        }
    }
}
