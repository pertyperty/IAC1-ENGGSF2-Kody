import { editTowerStage, previewTowerStage } from './tower-stage-editor.js';

const KEY = 'kody-guest-tower-v1';

function guestClearance(win) {
    try { return Math.max(0, Math.min(3, Number.parseInt(win.localStorage.getItem(KEY), 10) || 0)); }
    catch { return 0; }
}

export function mountTower(doc, win) {
    const map = doc.querySelector('[data-tower-map]');
    const level = doc.querySelector('[data-tower-level]');
    const guest = map?.hasAttribute('data-guest-tower') || level?.hasAttribute('data-guest-tower');
    const applyMap = () => {
        if (!map || !guest) return;
        const cleared = guestClearance(win);
        const resume = map.querySelector('[data-tower-continue]');
        if (resume) { resume.href = cleared === 3 ? map.dataset.welcomeUrl : map.querySelector(`[data-tower-stop="${cleared + 1}"] a`).href; resume.textContent = cleared === 3 ? 'Join to save your climb →' : `${cleared ? 'Continue' : 'Start'} level ${cleared + 1} →`; }
        map.querySelectorAll('[data-tower-stop]').forEach(stop => {
            const position = Number(stop.dataset.towerStop);
            // Level 4 is always an invitation; later trial nodes stay locked.
            const locked = position <= 3 ? position > cleared + 1 : position > 4;
            stop.classList.toggle('is-locked', locked);
            stop.classList.toggle('is-cleared', position <= cleared);
            const link = stop.querySelector('a');
            link.setAttribute('aria-disabled', String(locked));
            link.tabIndex = locked ? -1 : 0;
            stop.querySelector('i').textContent = position <= cleared ? '✓' : position === 4 ? 'JOIN' : position % 10 === 0 ? 'BOSS' : 'PLAY';
        });
    };
    map?.addEventListener('click', event => {
        if (event.target.closest('[aria-disabled="true"]')) event.preventDefault();
    });
    applyMap();
    win.addEventListener('pageshow', applyMap);
    if (!level) return;
    level.addEventListener('click', event => { if (event.target.closest('[data-tower-stage-link][aria-disabled="true"]')) event.preventDefault(); });
    const position = Number(level.dataset.towerLevel);
    if (guest && position > guestClearance(win) + 1) {
        level.querySelector('[data-tower-stages]').hidden = true;
        level.querySelector('[data-tower-locked]').hidden = false;
        return;
    }
    level.addEventListener('kody:completed', event => {
        if (guest ? !event.detail?.local : !event.detail?.tower) return;
        const stage = event.target.closest('[data-tower-stage]');
        if (!stage) return;
        const index = Number(stage.dataset.towerStage);
        const nextStage = level.querySelector(`[data-tower-stage="${index + 1}"]`);
        if (nextStage) {
            nextStage.hidden = false;
            const tab = level.querySelector(`[data-tower-stage-link="${index + 1}"]`);
            tab?.removeAttribute('aria-disabled'); tab?.removeAttribute('tabindex');
            nextStage.scrollIntoView({ behavior: win.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start' });
            return;
        }
        const victory = level.querySelector('[data-tower-victory]');
        victory.hidden = false;
        const link = victory.querySelector('[data-tower-next]');
        if (guest) {
            const cleared = Math.max(guestClearance(win), position);
            try { win.localStorage.setItem(KEY, String(Math.min(3, cleared))); } catch {}
            victory.querySelector('[data-tower-result]').textContent = position === 3 ? 'Your first three trials are complete. Join Kody to start saving your climb.' : 'Trial complete. Your next level is ready on the map.';
            link.href = position === 3 ? level.dataset.welcomeUrl : level.dataset.mapUrl;
            link.textContent = position === 3 ? 'Discover Kody →' : 'Next level →';
        } else if (event.detail.tower.cleared) {
            victory.querySelector('[data-tower-result]').textContent = event.detail.message;
            const next = new URL(event.detail.tower.next ?? '/', win.location.origin);
            if (next.origin === win.location.origin && (/^\/tower\/\d+$/.test(next.pathname) || next.pathname === '/')) link.href = next.href;
        }
        victory.scrollIntoView({ behavior: win.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'nearest' });
    });
}

export function mountTowerEditor(root) {
    const field = root.querySelector('[name="stages_json"]');
    const summary = root.querySelector('[data-tower-stage-summary]');
    const render = () => {
        summary.replaceChildren();
        try {
            const stages = JSON.parse(field.value);
            if (!Array.isArray(stages) || stages.length > 4) throw new Error();
            stages.forEach((stage, index) => {
                const row = document.createElement('div'); row.className = 'tower-stage-summary';
                const title = document.createElement('b'); title.textContent = `${index + 1}. ${stage.title ?? 'Untitled stage'}`;
                const kind = document.createElement('span'); kind.textContent = stage.template ?? 'Choose a template';
                const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'button button-secondary button-small'; remove.textContent = 'Remove stage';
                remove.addEventListener('click', () => { stages.splice(index, 1); field.value = JSON.stringify(stages, null, 2); render(); });
                const host = document.createElement('div'); host.className = 'tower-draft-preview';
                const preview = document.createElement('button'); preview.type = 'button'; preview.className = 'button button-play button-small'; preview.textContent = 'Try unsaved stage';
                const status = document.createElement('p'); status.setAttribute('role', 'status'); status.className = 'field-hint';
                preview.addEventListener('click', () => {
                    try { previewTowerStage(root, stage, host); status.textContent = 'Preview only. Publication also checks reachability and data limits on the server.'; }
                    catch { status.textContent = 'Check the stage fields before previewing. Your edits are preserved.'; host.replaceChildren(); }
                });
                row.append(title, kind, remove, preview);
                editTowerStage(row, stage, index, () => { field.value = JSON.stringify(stages, null, 2); status.textContent = 'Unsaved changes. Try the stage again before publishing.'; }, host);
                row.append(status, host); summary.append(row);
            });
        } catch { summary.textContent = 'Check the JSON syntax before adding another stage. Your text is preserved.'; }
    };
    root.querySelectorAll('[data-tower-template]').forEach(button => button.addEventListener('click', () => {
        try {
            const stages = JSON.parse(field.value);
            if (!Array.isArray(stages) || stages.length >= 4) { summary.textContent = 'A level supports up to four stages.'; return; }
            stages.push(JSON.parse(button.dataset.towerTemplate));
            field.value = JSON.stringify(stages, null, 2); render();
        } catch { summary.textContent = 'Fix the stage JSON first. Your existing configuration is preserved.'; }
    }));
    field.addEventListener('input', render); render();
}
