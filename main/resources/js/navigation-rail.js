export function mountNavigationRail(doc, win) {
    const rail = doc.querySelector('[data-navigation-rail]');
    if (!rail) return;
    const scroller = rail.querySelector('.workspace-nav');
    const scrollKey = `kody-navigation-scroll:${rail.dataset.navigationUser}`;
    const saveScroll = () => {
        if (!scroller || scroller.clientHeight === 0) return;
        try { win.sessionStorage.setItem(scrollKey, String(scroller.scrollTop)); } catch {}
    };
    let savedScroll = 0;
    try { savedScroll = Number(win.sessionStorage.getItem(scrollKey)) || 0; } catch {}
    if (scroller && Number.isFinite(savedScroll) && savedScroll >= 0) {
        win.requestAnimationFrame(() => { scroller.scrollTop = savedScroll; });
        scroller.addEventListener('scroll', saveScroll, { passive: true });
        scroller.addEventListener('click', saveScroll, { capture: true });
        win.addEventListener('pagehide', saveScroll);
    }
    const toggle = rail.querySelector('[data-rail-toggle]');
    const links = [...rail.querySelectorAll('.workspace-nav a')];
    const expanded = () => doc.documentElement.dataset.rail === 'expanded';
    const clear = () => links.forEach(link => link.style.removeProperty('--pull'));
    const update = () => { toggle.setAttribute('aria-expanded', String(expanded())); toggle.setAttribute('aria-label', expanded() ? 'Collapse navigation' : 'Keep navigation expanded'); clear(); };
    toggle.addEventListener('click', () => {
        doc.documentElement.dataset.rail = expanded() ? 'collapsed' : 'expanded';
        try { win.localStorage.setItem('kody-rail', doc.documentElement.dataset.rail); } catch {}
        update();
    });
    const pull = index => {
        if (expanded()) return;
        links.forEach((link, item) => link.style.setProperty('--pull', Math.abs(item - index) === 0 ? '1' : Math.abs(item - index) === 1 ? '.46' : Math.abs(item - index) === 2 ? '.16' : '0'));
    };
    links.forEach((link, index) => { link.addEventListener('pointerenter', () => pull(index)); link.addEventListener('focus', () => pull(index)); });
    rail.addEventListener('pointerleave', () => { const index = links.indexOf(doc.activeElement); if (index >= 0) pull(index); else clear(); });
    rail.addEventListener('focusout', event => { if (!rail.contains(event.relatedTarget)) clear(); });
    win.addEventListener('pageshow', update); update();
}
