export function mountTowerScenery(root, win) {
    const viewport = root.querySelector('[data-tower-viewport]');
    const art = root.querySelector('[data-ascent-art]');
    const caption = root.querySelector('[data-tower-altitude]');
    if (!viewport || !art) return;
    const reduced = win.matchMedia('(prefers-reduced-motion: reduce)');
    const zones = ['Meadow', 'Forest canopy', 'Mountain clouds', 'Moon orbit', 'Planet trail'];
    let frame = null;
    const draw = () => {
        frame = null;
        const maximum = Math.max(1, viewport.scrollHeight - viewport.clientHeight);
        const height = Math.max(0, Math.min(1, 1 - viewport.scrollTop / maximum));
        root.style.setProperty('--ascent', String(height));
        const zone = Math.min(4, Math.floor(height * 5));
        if (root.dataset.ascentZone !== String(zone)) root.dataset.ascentZone = String(zone);
        caption.textContent = `${zones[zone]} · floors ${zone * 10 + 1}–${zone * 10 + 10}`;
        // Pan a bounded backdrop, rather than laying out a huge image for every floor.
        art.style.transform = reduced.matches ? 'none' : `translateY(${-Math.max(0, art.clientHeight - root.clientHeight) * (1 - height)}px)`;
    };
    const schedule = () => { if (frame === null) frame = win.requestAnimationFrame(draw); };
    viewport.addEventListener('scroll', schedule, { passive: true });
    art.addEventListener('load', schedule); win.addEventListener('resize', schedule);
    reduced.addEventListener('change', schedule);
    win.addEventListener('pagehide', () => { if (frame !== null) win.cancelAnimationFrame(frame); frame = null; });
    win.addEventListener('pageshow', schedule); schedule();
}
