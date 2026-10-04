const choices = ['Like', 'Helpful', 'Favorite'];

export function mountContentReactions(root, fetcher = globalThis.fetch) {
    let state;
    try { state = JSON.parse(root.dataset.contentReactions); } catch { return; }
    const forms = [...root.querySelectorAll('[data-reaction-choice]')];
    const status = root.querySelector('[data-reaction-status]');
    let busy = false;
    let blocked = false;
    const render = () => forms.forEach((form) => {
        const choice = form.dataset.reactionChoice;
        const selected = state.reaction === choice;
        form.querySelector('[name="record_version"]').value = state.record_version;
        form.querySelector('[name="reaction"]').value = selected ? '' : choice;
        const button = form.querySelector('button');
        button.disabled = busy || blocked || !state.eligible;
        button.setAttribute('aria-pressed', String(selected));
        button.textContent = `${choice} · ${state.counts[choice]}${selected ? ' · Remove' : ''}`;
    });
    forms.forEach((form) => form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy || blocked || !state.eligible) return;
        const url = new URL(form.action, window.location.origin);
        if (url.origin !== window.location.origin) return;
        const wanted = form.querySelector('[name="reaction"]').value || null;
        busy = true;
        render();
        try {
            const response = await fetcher(url, { method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ _token: form.querySelector('[name="_token"]').value,
                    record_version: state.record_version, reaction: wanted }) });
            if (!response.ok) {
                blocked = [403, 404, 419, 422].includes(response.status);
                status.textContent = blocked ? 'Reload this page to check your access and latest reaction.' : 'Could not save your reaction. Try again.';
                return;
            }
            const next = await response.json();
            if (next.kind !== state.kind || next.content_id !== state.content_id || typeof next.eligible !== 'boolean'
                || !Number.isSafeInteger(next.record_version) || next.record_version < state.record_version
                || (next.reaction !== null && !choices.includes(next.reaction))
                || !choices.every((choice) => Number.isSafeInteger(next.counts?.[choice]) && next.counts[choice] >= 0)) {
                throw new Error('Invalid feedback state');
            }
            state = next;
            status.textContent = state.reaction === null ? 'Reaction removed.' : 'Your reaction is saved.';
        } catch {
            status.textContent = 'Could not confirm your reaction. Try again or reload.';
        } finally {
            busy = false;
            render();
        }
    }));
}
