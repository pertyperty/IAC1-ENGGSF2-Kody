import { setIconLabel } from './icons.js';
export function mountResourceViewer(root, win) {
    const viewer = root.querySelector('[data-resource-viewer]');
    if (!viewer?.showModal) return; // Links retain the authorized resource page without JS/dialog support.
    const frame = viewer.querySelector('[data-resource-frame]');
    const title = viewer.querySelector('[data-resource-title]');
    const status = viewer.querySelector('[data-resource-status]');
    const enlarge = viewer.querySelector('[data-resource-enlarge]');
    const wide = win.matchMedia('(min-width: 1200px)');
    let trigger = null, changingMode = false, expanded = false;
    const mode = modal => {
        changingMode = true;
        if (viewer.open) viewer.close();
        expanded = modal;
        if (modal) viewer.showModal(); else viewer.show();
        changingMode = false;
        enlarge.hidden = !wide.matches;
        setIconLabel(enlarge, modal ? 'collapse' : 'expand', modal ? 'Return to lesson' : 'Enlarge preview');
        root.classList.toggle('has-resource', !modal);
    };
    root.querySelectorAll('[data-resource-open]').forEach(link => link.addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        const url = new URL(link.href, win.location.origin);
        if (url.origin !== win.location.origin || !/^\/learn\/modules\/\d+\/revisions\/\d+\/resources\/\d+$/.test(url.pathname)) return;
        event.preventDefault(); trigger = link;
        title.textContent = link.dataset.resourceName;
        viewer.setAttribute('aria-label', `${link.dataset.resourceName} resource preview`);
        viewer.querySelector('[data-resource-original]').href = url.href;
        const download = new URL(url); download.searchParams.set('download', '1');
        viewer.querySelector('[data-resource-download]').href = download.href;
        url.searchParams.set(link.dataset.resourceType === 'pdf' ? 'inline' : 'pane', '1');
        status.textContent = 'Opening your resource…';
        frame.setAttribute('aria-busy', 'true');
        frame.title = `${link.dataset.resourceName} preview`;
        frame.src = url.href;
        mode(!wide.matches);
        viewer.querySelector('[data-resource-close]').focus({ preventScroll: true });
    }));
    frame.addEventListener('load', () => {
        if (!viewer.open) return;
        frame.removeAttribute('aria-busy');
        status.textContent = trigger?.dataset.resourceType === 'pdf' ? 'PDF preview. Use Download original if your browser cannot display it.' : 'Text preview. Download the original for its complete layout, images and diagrams.';
    });
    viewer.querySelector('[data-resource-close]').addEventListener('click', () => viewer.close());
    enlarge.addEventListener('click', () => mode(!expanded));
    viewer.addEventListener('close', () => {
        // Native close events are queued; reopening during a mode switch must preserve the pane.
        if (changingMode || viewer.open) return;
        root.classList.remove('has-resource');
        frame.removeAttribute('src');
        trigger?.focus({ preventScroll: true });
    });
    viewer.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !expanded) { event.preventDefault(); viewer.close(); }
    });
    wide.addEventListener('change', () => { if (viewer.open) mode(!wide.matches); });
}
