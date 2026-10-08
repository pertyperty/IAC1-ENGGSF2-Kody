// Navigation stays reachable by pointer, touch and keyboard while freeing the reading area.
export function mountScrollNavigation(doc, win) {
    const shell = doc.querySelector('[data-scroll-navigation]');
    if (!shell) return;
    const header = shell.querySelector('.app-header');
    const reveal = shell.querySelector('[data-navigation-reveal]');
    const strip = shell.querySelector('[data-progress-strip]');
    let previous = Math.max(0, win.scrollY);
    let distance = 0;
    let direction = 0;
    let pending = false;
    let height = header.getBoundingClientRect().height;

    const setHidden = hidden => {
        shell.classList.toggle('navigation-hidden', hidden);
        // A focused reveal control must survive scroll/focus events until activation or Tab.
        reveal.hidden = !hidden && doc.activeElement !== reveal;
        const stripHeight = strip?.getBoundingClientRect().height ?? 0;
        doc.documentElement.style.setProperty('--progress-height', `${stripHeight}px`);
        doc.documentElement.style.setProperty('--navigation-offset', `${(hidden ? 12 : height) + stripHeight}px`);
    };
    const measure = () => {
        height = header.getBoundingClientRect().height;
        doc.documentElement.style.setProperty('--header-height', `${height}px`);
        setHidden(shell.classList.contains('navigation-hidden'));
    };
    const show = () => { distance = 0; setHidden(false); };
    shell.addEventListener('pointerenter', show);
    header.addEventListener('focusin', show);
    header.addEventListener('toggle', event => { if (event.target.open) show(); }, true);
    const activate = () => {
        show();
        header.querySelector('a')?.focus({ preventScroll: true });
    };
    reveal.addEventListener('click', activate);
    reveal.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            activate();
        }
    });
    reveal.addEventListener('blur', () => { reveal.hidden = !shell.classList.contains('navigation-hidden'); });
    win.addEventListener('scroll', () => {
        if (pending) return;
        pending = true;
        win.requestAnimationFrame(() => {
            pending = false;
            const position = Math.max(0, win.scrollY);
            const delta = position - previous;
            previous = position;
            const nextDirection = Math.sign(delta);
            if (nextDirection !== direction) distance = 0;
            direction = nextDirection;
            distance += Math.abs(delta);
            if (position < height || (delta < 0 && distance >= 16)) show();
            else if (delta > 0 && position > height + 48 && distance >= 24
                && !header.matches(':hover, :focus-within')
                && !doc.querySelector('.account-menu[open], .mobile-workspace[open]')) setHidden(true);
        });
    }, { passive: true });
    if (win.ResizeObserver) {
        const observer = new win.ResizeObserver(measure);
        observer.observe(header);
        if (strip) observer.observe(strip);
    }
    else win.addEventListener('resize', measure);
    measure();
}
