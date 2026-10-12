import { setIconLabel } from './icons.js';

// Private resource authorization stays on the existing GET; this only changes its viewing surface.
export function mountDocumentStage(root, win) {
    const doc = root.ownerDocument;
    const button = root.querySelector('[data-document-fullscreen]');
    const minimize = root.querySelector('[data-document-minimize]');
    const sync = expanded => {
        root.classList.toggle('is-expanded', expanded);
        button.setAttribute('aria-pressed', String(expanded));
        setIconLabel(button, expanded ? 'close' : 'expand', expanded ? 'Exit fullscreen' : 'Fullscreen preview');
    };
    button.addEventListener('click', async () => {
        if (root.classList.contains('is-expanded')) {
            if (doc.fullscreenElement === root) await doc.exitFullscreen().catch(() => {});
            sync(false);
        } else {
            sync(true);
            doc.dispatchEvent(new win.CustomEvent('kody:minimize-navigation'));
            // The fixed viewing surface remains available if mobile browsers deny native fullscreen.
            await root.requestFullscreen?.().catch(() => {});
        }
    });
    root.addEventListener('fullscreenchange', () => sync(doc.fullscreenElement === root));
    doc.addEventListener('keydown', event => {
        if (event.key === 'Escape' && root.classList.contains('is-expanded') && !doc.fullscreenElement) {
            sync(false); button.focus({ preventScroll: true });
        }
    });
    minimize.addEventListener('click', () => doc.dispatchEvent(new win.CustomEvent('kody:minimize-navigation')));
    win.addEventListener('pagehide', () => sync(false));
}
