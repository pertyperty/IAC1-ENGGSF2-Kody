export function mountRoleUpdate(dialog, doc, win) {
    let update = null, watch = dialog.dataset.roleWatch === '1', timer = null, request = null, acknowledging = false, delay = 15000;
    const status = dialog.querySelector('[data-role-status]');
    const originUrl = value => { const url = new URL(value, win.location.href); if (url.origin !== win.location.origin) throw new Error('Invalid destination'); return url.href; };
    const stop = () => { if (timer !== null) win.clearTimeout(timer); timer = null; request?.abort(); request = null; };
    const schedule = () => { if (watch && !doc.hidden && !dialog.open) timer = win.setTimeout(check, delay); };
    const reveal = () => {
        if (!update || dialog.open || doc.hidden || doc.querySelector('dialog[open]')) return;
        for (const [key, field] of Object.entries({ previous: 'previous', current: 'role', title: 'title', message: 'message', action: 'action' })) dialog.querySelector(`[data-role-${key}]`).textContent = update[field];
        const list = dialog.querySelector('[data-role-capabilities]'); list.replaceChildren();
        for (const text of update.capabilities) { const item = doc.createElement('li'); item.textContent = text; list.append(item); }
        status.textContent = ''; dialog.showModal(); dialog.querySelector('[data-role-workspace]').focus({ preventScroll: true });
    };
    async function check() {
        if (doc.hidden || acknowledging) return;
        stop(); request = new win.AbortController();
        try {
            const response = await win.fetch(originUrl(dialog.dataset.roleUrl), { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: request.signal });
            if ([401,403].includes(response.status)) { watch = false; return; }
            if (!response.ok) throw new Error('Retry');
            const payload = await response.json();
            if (typeof payload.watch !== 'boolean' || (payload.update !== null && (!payload.update?.id || !Array.isArray(payload.update.capabilities)))) throw new Error('Invalid response');
            update = payload.update; watch = payload.watch; delay = 15000; reveal();
        } catch (error) { if (error.name !== 'AbortError') delay = Math.min(delay * 2, 60000); }
        finally { request = null; schedule(); }
    }
    async function acknowledge(workspace) {
        if (!update || acknowledging) return;
        acknowledging = true; stop(); status.textContent = 'Getting your workspace ready…';
        dialog.querySelectorAll('button').forEach(button => button.disabled = true);
        try {
            const response = await win.fetch(originUrl(update.acknowledge), { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': doc.querySelector('meta[name="csrf-token"]').content } });
            if (!response.ok) throw new Error('Your update could not be confirmed. Reload or try again.');
            const payload = await response.json(); const destination = originUrl(workspace ? payload.redirect : payload.overview);
            update = null; watch = false; dialog.close();
            win.location.assign(destination);
        } catch (error) { status.textContent = error.message; dialog.querySelector('[data-role-reload]').hidden = false; }
        finally { acknowledging = false; dialog.querySelectorAll('button').forEach(button => button.disabled = false); }
    }
    dialog.querySelector('[data-role-workspace]').addEventListener('click', () => acknowledge(true));
    dialog.querySelector('[data-role-reload]').addEventListener('click', () => win.location.assign(originUrl(dialog.dataset.roleOverview)));
    dialog.querySelectorAll('[data-role-dismiss]').forEach(button => button.addEventListener('click', () => acknowledge(false)));
    dialog.addEventListener('cancel', event => { event.preventDefault(); acknowledge(false); });
    doc.addEventListener('close', event => { if (event.target !== dialog) reveal(); }, true);
    doc.addEventListener('visibilitychange', () => { if (doc.hidden) stop(); else if (watch) check(); });
    win.addEventListener('pagehide', stop);
    win.addEventListener('pageshow', event => { if (event.persisted) { dialog.close(); update = null; check(); } });
    try { update = JSON.parse(dialog.dataset.roleInitial || 'null'); } catch { update = null; }
    reveal(); schedule();
}
