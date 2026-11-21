// Keep one live activity and its completion context while changing its presentation.
export function mountGameWorkspace(root) {
    const quiz = root.matches('[data-practice-quiz]');
    const workspace = root.querySelector('.garden-workbench, .arcade-workbench, form');
    if (!workspace || root.dataset.workspaceMounted) return;
    root.dataset.workspaceMounted = 'true';
    const doc = root.ownerDocument;
    const win = doc.defaultView;
    const launch = doc.createElement('button');
    launch.type = 'button';
    launch.className = 'button button-secondary focus-game-launch';
    launch.textContent = quiz ? 'Focus quiz' : 'Focus game';
    let dialog = null;
    let openedAutomatically = false;

    const fit = () => {
        if (!root.isConnected || root.getClientRects().length === 0) return;
        root.classList.toggle('workspace-fluid', root.getBoundingClientRect().width < 850);
        const header = doc.querySelector('.app-header-shell');
        const top = Math.max(workspace.getBoundingClientRect().top, header?.getBoundingClientRect().bottom ?? 0);
        workspace.style.setProperty('--workspace-height', `${Math.max(320, win.innerHeight - top - 16)}px`);
    };
    const open = () => {
        if (dialog?.open || root.getClientRects().length === 0) return;
        const origin = doc.createComment('activity workspace');
        root.before(origin);
        dialog = doc.createElement('dialog');
        dialog.className = `focus-game-shell${quiz ? ' focus-quiz-shell' : ''}`;
        dialog.setAttribute('aria-label', quiz ? 'Focused quiz workspace' : 'Focused game workspace');
        const header = doc.createElement('header');
        const title = doc.createElement('h2');
        title.textContent = root.querySelector('.game-heading h2').textContent;
        const actions = doc.createElement('div');
        actions.className = 'focus-header-actions';
        const menu = doc.querySelector('[data-focus-navigation]');
        if (menu) {
            actions.append(menu.content.cloneNode(true));
            const navigation = actions.querySelector('nav');
            const theme = doc.querySelector('[data-theme-toggle]');
            if (theme) {
                const toggleTheme = doc.createElement('button');
                toggleTheme.type = 'button';
                toggleTheme.textContent = theme.getAttribute('aria-label');
                toggleTheme.addEventListener('click', () => {
                    theme.click();
                    toggleTheme.textContent = theme.getAttribute('aria-label');
                });
                navigation.append(toggleTheme);
                actions.querySelector('.focus-navigation').addEventListener('toggle', () => {
                    toggleTheme.textContent = theme.getAttribute('aria-label');
                });
            }
            const track = doc.querySelector('[data-proficiency-open]');
            if (track) {
                const chooseTrack = doc.createElement('button');
                chooseTrack.type = 'button';
                chooseTrack.textContent = 'Choose track';
                chooseTrack.addEventListener('click', () => track.click());
                navigation.append(chooseTrack);
            }
        }
        const learn = doc.createElement('button');
        learn.type = 'button';
        learn.className = 'button button-secondary';
        learn.textContent = 'Learn';
        learn.addEventListener('click', () => {
            const reference = root.querySelector('.activity-reference');
            if (!reference) return;
            reference.open = !reference.open;
            reference.querySelector('summary').focus();
        });
        const close = doc.createElement('button');
        close.type = 'button';
        close.className = 'button button-secondary';
        close.textContent = 'Exit focus';
        close.addEventListener('click', () => {
            doc.documentElement.dataset.focusDismissed = 'true';
            dialog.close();
        });
        actions.append(learn, close);
        header.append(title, actions);
        origin.after(dialog);
        dialog.append(header, root);
        launch.hidden = true;
        const activeDialog = dialog;
        activeDialog.addEventListener('cancel', () => { doc.documentElement.dataset.focusDismissed = 'true'; });
        activeDialog.addEventListener('close', () => {
            origin.replaceWith(root);
            activeDialog.remove();
            if (dialog === activeDialog) dialog = null;
            launch.hidden = false;
            fit();
            // A result dialog may be above this one; never take its focus.
            if (!doc.querySelector('dialog[open]')) launch.focus({ preventScroll: true });
        }, { once: true });
        activeDialog.showModal();
        fit();
    };
    launch.addEventListener('click', () => {
        doc.documentElement.dataset.focusDismissed = 'false';
        open();
    });
    root.querySelector('.lesson-briefing').append(launch);
    const automatic = () => {
        if (openedAutomatically || doc.documentElement.dataset.focusDismissed === 'true'
            || !root.closest('[data-focus-default]') || root.closest('details:not([open]), [hidden]') || doc.querySelector('dialog[open]')
            || root.getClientRects().length === 0) return;
        const stage = root.closest('[data-tower-stage]');
        const requested = doc.querySelector(/^#tower-stage-\d+$/.test(win.location.hash) ? win.location.hash : '[data-no-stage]');
        if (requested && stage && requested !== stage) return;
        openedAutomatically = true;
        open();
    };
    const schedule = () => win.requestAnimationFrame(automatic);
    // Stage admission and details visibility can change without a page navigation.
    const visibility = new MutationObserver(schedule);
    visibility.observe(doc.querySelector('main'), { subtree: true, attributes: true, attributeFilter: ['hidden', 'open'] });
    doc.addEventListener('close', schedule, true);
    doc.addEventListener('toggle', schedule, true);
    win.addEventListener('hashchange', () => {
        doc.documentElement.dataset.focusDismissed = 'false';
        schedule();
    });
    root.addEventListener('kody:completed', () => {
        // The Tower owns stage continuation and its result dialog.
        if (root.closest('[data-tower-stage]')) dialog?.close();
    });
    fit();
    const observer = new ResizeObserver(fit);
    observer.observe(root);
    win.addEventListener('resize', fit);
    schedule();
}
