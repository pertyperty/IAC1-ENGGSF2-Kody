// Size the workspace to the space below its heading, including embedded lessons.
export function mountGameWorkspace(root) {
    const workspace = root.querySelector('.garden-workbench, .arcade-workbench');
    if (!workspace) return;
    const doc = root.ownerDocument;
    const launch = doc.createElement('button');
    launch.type = 'button'; launch.className = 'button button-secondary focus-game-launch'; launch.textContent = 'Focus game';
    launch.addEventListener('click', () => {
        const origin = doc.createComment('game workspace'); root.before(origin);
        const dialog = doc.createElement('dialog'); dialog.className = 'focus-game-shell';
        dialog.setAttribute('aria-label', 'Focused game workspace');
        const header = doc.createElement('header');
        const title = doc.createElement('h2'); title.textContent = root.querySelector('.game-heading h2').textContent;
        const close = doc.createElement('button'); close.type = 'button'; close.className = 'button button-secondary'; close.textContent = 'Exit focus';
        close.addEventListener('click', () => dialog.close());
        header.append(title, close); origin.after(dialog); dialog.append(header, root);
        launch.hidden = true;
        dialog.addEventListener('close', () => {
            origin.replaceWith(root); dialog.remove(); launch.hidden = false; fit(); launch.focus({ preventScroll: true });
        }, { once: true });
        dialog.showModal(); fit();
    });
    root.querySelector('.lesson-briefing').append(launch);
    const fit = () => {
        if (!root.isConnected || root.getClientRects().length === 0) return;
        root.classList.toggle('workspace-fluid', root.getBoundingClientRect().width < 850);
        const header = root.ownerDocument.querySelector('.app-header-shell');
        const top = Math.max(workspace.getBoundingClientRect().top, header?.getBoundingClientRect().bottom ?? 0);
        workspace.style.setProperty('--workspace-height', `${Math.max(320, window.innerHeight - top - 16)}px`);
    };
    fit();
    const observer = new ResizeObserver(fit);
    observer.observe(root);
    window.addEventListener('resize', fit);
}
