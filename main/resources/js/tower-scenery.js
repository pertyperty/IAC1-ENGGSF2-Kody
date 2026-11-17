export function towerAltitude(height, positions) {
    const zones = ['Meadow', 'Forest canopy', 'Mountain clouds', 'Moon orbit', 'Planet trail'];
    const zone = Math.min(4, Math.max(0, Math.floor(height * 5)));
    if (!positions.length) return { zone, caption: zones[zone] };
    const first = Math.min(positions.length - 1, Math.floor(zone * positions.length / 5));
    const last = Math.max(first, Math.ceil((zone + 1) * positions.length / 5) - 1);
    return { zone, caption: `${zones[zone]} · floors ${positions[first]}–${positions[last]}` };
}

export function mountTowerScenery(root, win) {
    const viewport = root.querySelector('[data-tower-viewport]');
    const art = root.querySelector('[data-ascent-art]');
    const caption = root.querySelector('[data-tower-altitude]');
    if (!viewport || !art) return;
    const reduced = win.matchMedia('(prefers-reduced-motion: reduce)');
    const positions = [...root.querySelectorAll('[data-tower-stop]')].map(stop => Number(stop.dataset.towerStop));
    let frame = null;
    const draw = () => {
        frame = null;
        const maximum = Math.max(1, viewport.scrollHeight - viewport.clientHeight);
        const height = Math.max(0, Math.min(1, 1 - viewport.scrollTop / maximum));
        root.style.setProperty('--ascent', String(height));
        const { zone, caption: label } = towerAltitude(height, positions);
        if (root.dataset.ascentZone !== String(zone)) root.dataset.ascentZone = String(zone);
        if (caption) caption.textContent = label;
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
