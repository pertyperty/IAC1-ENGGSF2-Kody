import { mountArcade, validScenario } from './arcade-games.js';

export function mountScenarioEditor(form, selector, prompts) {
    const root = form.querySelector('[data-scenario-editor]');
    if (!root) return;
    const defaults = JSON.parse(root.dataset.arcadeDefaults);
    const choice = form.querySelector(selector);
    const input = root.querySelector('input[type="hidden"]');
    const status = root.querySelector('[data-scenario-status]');
    const fields = Object.fromEntries(['pixels', 'start', 'target', 'items', 'files', 'destination', 'content'].map((name) => [name, root.querySelector(`[data-scenario-${name}]`)]));
    let previous = choice.value;
    let invalid = false;
    const saved = {};
    try {
        if (defaults[choice.value] && input.value) {
            const scenario = JSON.parse(input.value);
            if (!validScenario(choice.value, scenario)) throw new Error('Invalid scenario');
            saved[choice.value] = scenario;
        }
    } catch { invalid = true; }
    function load() {
        const active = Boolean(defaults[choice.value]);
        root.hidden = !active;
        input.disabled = !active;
        if (!active) { Object.values(fields).forEach((field) => { field.disabled = true; }); return; }
        const scenario = saved[choice.value] ?? defaults[choice.value].scenario;
        root.querySelectorAll('[data-scenario-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.scenarioPanel !== choice.value;
            panel.querySelectorAll('input, textarea').forEach((field) => { field.disabled = panel.hidden; });
        });
        fields.pixels.value = (scenario.pixels ?? []).join('\n');
        fields.start.value = scenario.start ?? ''; fields.target.value = scenario.target ?? '';
        fields.items.value = (scenario.items ?? []).join(', ');
        fields.files.value = (scenario.files ?? []).map((file) => `${file.name} | ${file.content}`).join('\n');
        fields.destination.value = scenario.destination ?? ''; fields.content.value = scenario.content ?? '';
        status.textContent = invalid ? 'The saved scenario could not be loaded. Reset to the example or edit the fields before saving.' : 'Customize the objective, then try your scenario.';
        if (!invalid) input.value = JSON.stringify(scenario);
    }
    function serialize() {
        if (!defaults[choice.value]) return;
        const scenario = {
            'pixel-studio': () => ({ pixels: fields.pixels.value.split('\n').filter((line) => line !== '') }),
            'number-machine': () => ({ start: fields.start.value === '' ? null : Number(fields.start.value), target: fields.target.value === '' ? null : Number(fields.target.value) }),
            'sort-lab': () => ({ items: fields.items.value.split(',').map((value) => value.trim() === '' ? null : Number(value.trim())) }),
            'terminal-quest': () => ({ files: fields.files.value.split('\n').filter((line) => line !== '').map((line) => {
                const index = line.indexOf('|');
                if (index < 0) return { name: '', content: '' };
                const content = line.slice(index + 1);
                return { name: line.slice(0, index).trim(), content: content.startsWith(' ') ? content.slice(1) : content };
            }), destination: fields.destination.value, content: fields.content.value }),
        }[choice.value]();
        saved[choice.value] = scenario;
        input.value = JSON.stringify(scenario);
        invalid = false;
    }
    Object.values(fields).forEach((field) => field.addEventListener('input', () => { serialize(); root.querySelector('[data-scenario-preview-host]').replaceChildren(); }));
    choice.addEventListener('change', () => {
        if (previous !== choice.value) { invalid = false; previous = choice.value; }
        root.querySelector('[data-scenario-preview-host]').replaceChildren(); load();
    });
    root.querySelector('[data-scenario-reset]').addEventListener('click', () => { saved[choice.value] = structuredClone(defaults[choice.value].scenario); invalid = false; load(); });
    root.querySelector('[data-scenario-preview]').addEventListener('click', () => {
        try {
            if (invalid) throw new Error('Invalid scenario');
            const game = { ...defaults[choice.value], scenario: JSON.parse(input.value) };
            for (const [property, name] of Object.entries(prompts)) game[property] = form.querySelector(`[name="${name}"]`).value;
            const fragment = root.querySelector('template').content.cloneNode(true);
            const preview = fragment.querySelector('[data-arcade-game]');
            preview.dataset.arcadeGame = JSON.stringify(game);
            preview.setAttribute('aria-label', game.title);
            preview.querySelector('h2').textContent = game.title;
            preview.querySelector('.step-pill').textContent = game.concept;
            preview.querySelector('.game-controls > p').textContent = game.instructions;
            mountArcade(preview);
            root.querySelector('[data-scenario-preview-host]').replaceChildren(fragment);
            status.textContent = 'Preview only. Saving checks scenario types, limits and objective reachability on the server.';
        } catch { status.textContent = 'Check the scenario fields before previewing.'; }
    });
    load();
}
