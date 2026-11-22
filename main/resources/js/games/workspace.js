// Focus layout IS the live activity workspace: no modal overlay trapping page controls or settings.
export function mountGameWorkspace(root) {
    const quiz = root.matches('[data-practice-quiz]');
    const workspace = root.querySelector('.garden-workbench, .arcade-workbench, form');
    if (!workspace || root.dataset.workspaceMounted) return;
    root.dataset.workspaceMounted = 'true';
    const doc = root.ownerDocument;
    const win = doc.defaultView;
    const isFocusDefault = Boolean(root.closest('[data-focus-default]'));

    const fit = () => {
        if (!root.isConnected || root.getClientRects().length === 0) return;
        root.classList.toggle('workspace-fluid', root.getBoundingClientRect().width < 850);
        const header = doc.querySelector('.app-header-shell');
        const top = Math.max(workspace.getBoundingClientRect().top, header?.getBoundingClientRect().bottom ?? 0);
        const available = Math.max(360, Math.min(760, win.innerHeight - top - 16));
        workspace.style.setProperty('--workspace-height', `${available}px`);
    };

    if (isFocusDefault) {
        root.classList.add('play-focus-surface');
        root.dataset.focusSurface = quiz ? 'quiz' : 'game';

        const bar = doc.createElement('header');
        bar.className = 'focus-workspace-bar';
        bar.setAttribute('aria-label', quiz ? 'Quiz workspace controls' : 'Game workspace controls');

        const titleWrap = doc.createElement('div');
        titleWrap.className = 'focus-workspace-title';
        const headingText = root.querySelector('.game-heading h2')?.textContent?.trim() ?? (quiz ? 'Quiz' : 'Activity');
        const kickerText = root.querySelector('.game-heading .game-kicker, .game-heading .overline')?.textContent?.trim();
        if (kickerText) {
            const badge = doc.createElement('span');
            badge.className = 'focus-kicker-pill';
            badge.textContent = kickerText;
            titleWrap.append(badge);
        }
        const title = doc.createElement('h2');
        title.textContent = headingText;
        titleWrap.append(title);

        const actions = doc.createElement('div');
        actions.className = 'focus-header-actions';

        const reference = root.querySelector('.activity-reference');
        if (reference) {
            const learn = doc.createElement('button');
            learn.type = 'button';
            learn.className = 'button button-secondary button-small';
            learn.textContent = 'Learn';
            learn.setAttribute('aria-expanded', String(reference.open));
            learn.addEventListener('click', () => {
                reference.open = !reference.open;
                learn.setAttribute('aria-expanded', String(reference.open));
                if (reference.open) reference.querySelector('summary')?.focus({ preventScroll: true });
                fit();
            });
            reference.addEventListener('toggle', () => {
                learn.setAttribute('aria-expanded', String(reference.open));
                fit();
            });
            actions.append(learn);
        }

        const menu = doc.querySelector('[data-focus-navigation]');
        if (menu) {
            actions.append(menu.content.cloneNode(true));
            const navigation = actions.querySelector('nav');
            const theme = doc.querySelector('[data-theme-toggle]');
            if (theme && navigation) {
                const toggleTheme = doc.createElement('button');
                toggleTheme.type = 'button';
                toggleTheme.textContent = theme.getAttribute('aria-label');
                toggleTheme.addEventListener('click', () => {
                    theme.click();
                    toggleTheme.textContent = theme.getAttribute('aria-label');
                });
                navigation.append(toggleTheme);
                actions.querySelector('.focus-navigation')?.addEventListener('toggle', () => {
                    toggleTheme.textContent = theme.getAttribute('aria-label');
                });
            }
            const track = doc.querySelector('[data-proficiency-open]');
            if (track && navigation) {
                const chooseTrack = doc.createElement('button');
                chooseTrack.type = 'button';
                chooseTrack.textContent = 'Choose track';
                chooseTrack.addEventListener('click', () => track.click());
                navigation.append(chooseTrack);
            }
        }

        bar.append(titleWrap, actions);
        const heading = root.querySelector('.game-heading');
        if (heading) heading.after(bar);
        else root.prepend(bar);
    }

    fit();
    const observer = new ResizeObserver(fit);
    observer.observe(root);
    win.addEventListener('resize', fit);
}
