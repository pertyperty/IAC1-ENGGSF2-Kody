// Decorative only: passive touch listeners preserve native tower scrolling.
export function mountTowerGrid(root, win) {
    const canvas = root.querySelector('[data-tower-grid]');
    const context = canvas?.getContext('2d');
    if (!context || !win.ResizeObserver) return;
    const doc = root.ownerDocument;
    const reduced = win.matchMedia('(prefers-reduced-motion: reduce)');
    let points = [], width = 0, height = 0, pointer = null, frame = null, touchRelease = null;

    const draw = () => {
        frame = null;
        if (reduced.matches || doc.hidden) return;
        context.clearRect(0, 0, width, height);
        context.fillStyle = win.getComputedStyle(canvas).color;
        context.beginPath();
        let moving = false;
        for (const point of points) {
            let targetX = 0, targetY = 0;
            if (pointer) {
                const dx = point.x - pointer.x, dy = point.y - pointer.y;
                const distance = Math.hypot(dx, dy);
                if (distance < 100) {
                    const force = 36 * (1 - distance / 100) ** 2;
                    targetX = distance > 0 ? dx / distance * force : force;
                    targetY = distance > 0 ? dy / distance * force : 0;
                }
            }
            point.dx += (targetX - point.dx) * .22;
            point.dy += (targetY - point.dy) * .22;
            moving ||= Math.abs(targetX - point.dx) + Math.abs(targetY - point.dy) > .05;
            const x = point.x + point.dx, y = point.y + point.dy;
            context.moveTo(x + 2, y);
            context.arc(x, y, 2, 0, Math.PI * 2);
        }
        context.fill();
        // Stop at rest; no perpetual animation loop on a static page.
        if (moving) frame = win.requestAnimationFrame(draw);
    };
    const schedule = () => {
        if (frame === null && !reduced.matches && !doc.hidden) frame = win.requestAnimationFrame(draw);
    };
    const release = () => {
        if (touchRelease !== null) win.clearTimeout(touchRelease);
        touchRelease = null; pointer = null; schedule();
    };
    const move = event => {
        const box = canvas.getBoundingClientRect();
        pointer = { x: event.clientX - box.left, y: event.clientY - box.top };
        schedule();
    };
    const resize = () => {
        const box = canvas.getBoundingClientRect();
        width = box.width; height = box.height;
        // Bound pixel memory and point count, including large/high-DPI monitors.
        const scale = Math.min(win.devicePixelRatio || 1, 2, Math.sqrt(4_000_000 / Math.max(1, width * height)));
        canvas.width = Math.round(width * scale); canvas.height = Math.round(height * scale);
        context.setTransform(scale, 0, 0, scale, 0, 0);
        const spacing = Math.max(36, Math.sqrt(width * height / 4000));
        points = [];
        for (let y = 12; y < height && points.length < 4000; y += spacing) for (let x = 12; x < width && points.length < 4000; x += spacing) points.push({ x, y, dx: 0, dy: 0 });
        root.classList.toggle('has-interactive-grid', !reduced.matches);
        schedule();
    };
    root.addEventListener('pointermove', event => { if (event.pointerType !== 'touch') move(event); }, { passive: true });
    root.addEventListener('pointerleave', event => { if (event.pointerType !== 'touch') release(); }, { passive: true });
    for (const type of ['touchstart', 'touchmove']) root.addEventListener(type, event => {
        if (touchRelease !== null) win.clearTimeout(touchRelease);
        touchRelease = null;
        if (event.touches[0]) move(event.touches[0]);
    }, { passive: true });
    // Give short taps a visible pulse even if touchend arrives before the next frame.
    root.addEventListener('touchend', () => { touchRelease = win.setTimeout(release, 120); }, { passive: true });
    root.addEventListener('touchcancel', release, { passive: true });
    reduced.addEventListener('change', () => {
        if (frame !== null) win.cancelAnimationFrame(frame);
        frame = null; release(); resize();
    });
    doc.addEventListener('visibilitychange', () => {
        if (doc.hidden && frame !== null) { win.cancelAnimationFrame(frame); frame = null; }
        else release();
    });
    win.addEventListener('pagehide', () => { release(); if (frame !== null) win.cancelAnimationFrame(frame); frame = null; });
    win.addEventListener('pageshow', release);
    new win.ResizeObserver(resize).observe(canvas);
    new win.MutationObserver(schedule).observe(doc.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    new win.MutationObserver(schedule).observe(root, { attributes: true, attributeFilter: ['data-ascent-zone'] });
    resize();
}
