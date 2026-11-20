// Size the workspace to the space below its heading, including embedded lessons.
export function mountGameWorkspace(root) {
    const workspace = root.querySelector('.garden-workbench, .arcade-workbench');
    if (!workspace) return;
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
