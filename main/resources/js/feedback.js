import { createIcon } from './icons.js';

export function publishFeedback(root, message, kind = 'info', progressSaved = false, journey = null, action = null, result = false) {
    const doc = root.ownerDocument ?? globalThis.document;
    if (!doc?.dispatchEvent) return;
    const Event = doc.defaultView?.CustomEvent ?? globalThis.CustomEvent;
    doc.dispatchEvent(new Event('kody:feedback', { detail: { message, kind, progressSaved, journey, action, result, source: root } }));
}

export function mountFeedback(doc, win) {
    const region = doc.querySelector('[data-toast-region]');
    if (!region) return;
    doc.documentElement.classList.add('feedback-ready');
    const prepare = toast => {
        const dismiss = toast.querySelector('[data-toast-dismiss]');
        dismiss.hidden = false;
        dismiss.addEventListener('click', () => toast.remove());
        // Messages remain until dismissed; no timing barrier for reading or assistive technology.
    };
    region.querySelectorAll('[data-flash-toast]').forEach(prepare);
    doc.addEventListener('kody:feedback', event => {
        if (event.detail?.result && doc.querySelector('[data-game-result]')?.showModal) return;
        const { message, kind = 'info', action } = event.detail ?? {};
        if (typeof message !== 'string' || !message.trim()) return;
        if ([...region.children].some(toast => toast.querySelector('span')?.textContent === message)) return;
        // Bound the stack without removing a notification whose controls hold keyboard focus.
        while (region.children.length >= 3) {
            const removable = [...region.children].find(toast => !toast.contains(doc.activeElement));
            if (!removable) break;
            removable.remove();
        }
        const toast = doc.createElement('div');
        toast.className = `toast toast-${['error', 'success', 'info'].includes(kind) ? kind : 'info'}`;
        toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');
        const text = doc.createElement('span');
        text.textContent = message;
        const button = doc.createElement('button');
        button.type = 'button'; button.append(createIcon(doc, 'close'));
        button.setAttribute('data-toast-dismiss', '');
        button.setAttribute('aria-label', 'Dismiss notification');
        toast.append(text);
        if (action && typeof action.href === 'string' && typeof action.label === 'string') {
            try {
                const url = new URL(action.href, win.location.origin);
                if (url.origin === win.location.origin && url.protocol === win.location.protocol) {
                    const link = doc.createElement('a'); link.className = 'toast-action button button-secondary button-small'; link.href = url.href; link.append(createIcon(doc, 'right'), doc.createTextNode(action.label.slice(0, 100))); toast.append(link);
                }
            } catch {}
        }
        toast.append(button); region.append(toast); prepare(toast);
    });
    // Keep field-specific errors near their inputs; announce async network/action results as toasts.
    const selectors = '[data-reaction-status], [data-attempt-status]';
    if (win.MutationObserver) doc.querySelectorAll(selectors).forEach(source => {
        let previous = source.textContent.trim();
        new win.MutationObserver(() => {
            const value = source.textContent.trim();
            if (source.matches('[data-attempt-status]')) {
                if (value === previous) return;
                previous = value;
                publishFeedback(source, `Coding evaluation: ${value}.`, value === 'Passed' ? 'success' : value === 'Unavailable' ? 'error' : 'info', value === 'Passed');
            } else publishFeedback(source, value);
        }).observe(source, { childList: true, characterData: true, subtree: true });
    });
}
