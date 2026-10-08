export function progressValues(result) {
    const { progress, achievements } = result ?? {};
    if (!progress || !achievements || !Number.isInteger(progress.current_streak) || progress.current_streak < 0
        || !Number.isInteger(progress.completed_count) || progress.completed_count < 0
        || !progress.levels || typeof progress.levels !== 'object' || typeof progress.active_today !== 'boolean'
        || !Number.isInteger(achievements.xp) || achievements.xp < 0 || typeof achievements.rank !== 'string') return null;
    const total = Object.keys(progress.levels).length;
    if (progress.completed_count > total) return null;
    return { streak: String(progress.current_streak), today: progress.active_today ? 'Today saved' : 'Play today',
        xp: achievements.xp.toLocaleString('en-US'), rank: achievements.rank, level: `${progress.completed_count} / ${total}` };
}

export function mountProgressStrip(doc, win, fetcher = globalThis.fetch) {
    const strip = doc.querySelector('[data-progress-strip]');
    if (!strip) return;
    let busy = false;
    let retry = false;
    const refresh = async () => {
        if (busy) { retry = true; return; }
        busy = true; strip.setAttribute('aria-busy', 'true');
        try {
            const response = await fetcher(strip.dataset.progressUrl, { credentials: 'same-origin', cache: 'no-store',
                headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(10000) });
            const values = response.ok ? progressValues(await response.json()) : null;
            if (values) Object.entries(values).forEach(([key, value]) => { strip.querySelector(`[data-progress-${key}]`).textContent = value; });
            else throw new Error('Progress unavailable');
            strip.querySelector('[data-progress-retry]').hidden = true;
        } catch {
            // Retain the last server snapshot rather than inventing progress on a network failure.
            strip.querySelector('[data-progress-retry]').hidden = false;
        } finally {
            busy = false; strip.removeAttribute('aria-busy');
            if (retry) { retry = false; refresh(); }
        }
    };
    strip.querySelector('[data-progress-retry]').addEventListener('click', refresh);
    doc.addEventListener('kody:feedback', event => { if (event.detail?.progressSaved) refresh(); });
    doc.addEventListener('visibilitychange', () => { if (doc.visibilityState === 'visible') refresh(); });
    win.addEventListener('pageshow', event => { if (event.persisted) refresh(); });
}
