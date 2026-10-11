import { journeyValues } from './course-journey.js';

export function mountGameResult(doc, win) {
    const dialog = doc.querySelector('[data-game-result]');
    if (!dialog?.showModal) return;
    const reactionHost = dialog.querySelector('[data-result-reactions]');
    const next = dialog.querySelector('[data-result-next]');
    let reactions = null, placeholder = null, focus = null;
    const restore = () => {
        if (reactions && placeholder) placeholder.replaceWith(reactions);
        reactions = placeholder = null;
        focus?.focus?.({ preventScroll: true });
    };
    dialog.addEventListener('close', restore);
    dialog.querySelector('[data-result-return]').addEventListener('click', () => dialog.close());
    doc.addEventListener('kody:feedback', event => {
        const detail = event.detail;
        if (!detail?.result || typeof detail.message !== 'string' || !detail.message.trim()) return;
        if (!dialog.open) focus = detail.source?.querySelector?.('[data-game-run], [data-arcade-run], form button') ?? doc.activeElement;
        dialog.querySelector('[data-result-title]').textContent = detail.kind === 'success' ? 'Nicely done!' : 'Give it another try';
        dialog.querySelector('[data-result-message]').textContent = detail.message;
        dialog.querySelector('[data-result-saved]').textContent = detail.progressSaved ? 'Saved to your account' : detail.kind === 'success' ? 'Practice result' : 'Your program is still here';
        dialog.classList.toggle('result-success', detail.kind === 'success');
        next.hidden = true; next.removeAttribute('href');
        const trail = detail.journey && journeyValues(detail.journey, Number(detail.journey.course_id), win.location.origin);
        let action = trail?.next ? { href: trail.next.url, label: 'Next lesson →' } : detail.action;
        if (action && typeof action.href === 'string' && typeof action.label === 'string') {
            try {
                const url = new URL(action.href, win.location.origin);
                if (url.origin === win.location.origin && url.protocol === win.location.protocol && !url.username && !url.password) {
                    next.href = url.href; next.textContent = action.label.slice(0, 100); next.hidden = false;
                }
            } catch {}
        }
        if (!reactions) {
            reactions = detail.source?.closest?.('main')?.querySelector('[data-content-reactions]') ?? null;
            if (reactions) {
                placeholder = doc.createElement('div');
                reactions.replaceWith(placeholder); reactionHost.append(reactions);
            }
        }
        reactionHost.hidden = !reactions;
        if (!dialog.open) dialog.showModal();
        dialog.querySelector('[data-result-return]').focus({ preventScroll: true });
    });
}
