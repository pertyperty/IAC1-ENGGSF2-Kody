import { mountGarden, validateInstance } from './command-garden.js';

const same = (a, b) => a[0] === b[0] && a[1] === b[1];
export const gardenLayout = (instance) => Object.fromEntries(['start', 'goal', 'path', 'crystals'].map((key) => [key, structuredClone(instance[key])]));

export function paintGarden(layout, point, tool, mode) {
    const next = structuredClone(layout);
    if (!['path', 'start', 'goal', 'crystal'].includes(tool) || !Array.isArray(point)
        || point.length !== 2 || !point.every(Number.isInteger) || point[0] < 0 || point[0] >= 5 || point[1] < 0 || point[1] >= 4) return next;
    const contains = (points) => points.some((item) => same(item, point));
    if (tool === 'path') {
        if (same(point, next.start) || same(point, next.goal)) return next;
        if (contains(next.path)) {
            next.path = next.path.filter((item) => !same(item, point));
            next.crystals = next.crystals.filter((item) => !same(item, point));
        } else next.path.push([...point]);
    } else if (tool === 'crystal') {
        if (mode !== 'conditional' || same(point, next.start) || !contains(next.path)) return next;
        if (contains(next.crystals)) next.crystals = next.crystals.filter((item) => !same(item, point));
        else if (next.crystals.length < 4) next.crystals.push([...point]);
    } else {
        if (same(point, next[tool === 'start' ? 'goal' : 'start'])) return next;
        next[tool] = [...point];
        if (!contains(next.path)) next.path.push([...point]);
        if (tool === 'start') next.crystals = next.crystals.filter((item) => !same(item, point));
    }
    return next;
}

export function mountGardenDesigner(form, presets) {
    const root = form.querySelector('[data-garden-designer]');
    const input = root.querySelector('[name="game_layout"]');
    const preset = form.querySelector('[name="game_preset"]');
    const status = root.querySelector('[data-designer-status]');
    const host = root.querySelector('[data-designer-preview-host]');
    const board = root.querySelector('[data-designer-board]');
    let layout;
    let tool = 'path';
    let valid = true;
    const instance = () => ({ ...presets[preset.value], ...layout });
    try {
        layout = input.value ? JSON.parse(input.value) : gardenLayout(presets[preset.value]);
        validateInstance(instance());
    } catch {
        layout = gardenLayout(presets[preset.value]);
        valid = false;
        status.textContent = 'The submitted layout could not be loaded. Choose Reset trail to start over.';
    }
    const tiles = [];
    const refresh = () => {
        for (const [button, point] of tiles) {
            const onPath = layout.path.some((p) => same(p, point));
            const start = same(point, layout.start);
            const goal = same(point, layout.goal);
            const crystal = layout.crystals.some((p) => same(p, point));
            button.classList.toggle('designer-path', onPath);
            button.textContent = start ? 'K' : goal ? '⚑' : crystal ? '◇' : onPath ? '·' : '';
            button.setAttribute('aria-label', `Column ${point[0] + 1}, row ${point[1] + 1}: ${start ? 'start' : goal ? 'goal' : crystal ? 'crystal' : onPath ? 'path' : 'grass'}. ${tool} tool.`);
        }
        root.querySelector('[data-designer-tool="crystal"]').disabled = preset.value !== 'conditions';
        root.querySelectorAll('[data-designer-tool]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.designerTool === tool)));
    };
    const save = () => {
        input.value = JSON.stringify(layout);
        valid = true;
        host.replaceChildren();
        status.textContent = 'Layout changed. Try it before saving; the server checks that the objective is reachable.';
        refresh();
    };
    for (let y = 0; y < 4; y++) {
        for (let x = 0; x < 5; x++) {
            const button = document.createElement('button');
            button.type = 'button';
            const point = [x, y];
            button.addEventListener('click', () => {
                layout = paintGarden(layout, point, tool, instance().mode);
                save();
            });
            tiles.push([button, point]);
            board.append(button);
        }
    }
    root.querySelectorAll('[data-designer-tool]').forEach((button) => button.addEventListener('click', () => {
        tool = button.dataset.designerTool;
        refresh();
    }));
    board.addEventListener('keydown', (event) => {
        const index = tiles.findIndex(([button]) => button === event.target);
        const delta = { ArrowUp: -5, ArrowDown: 5, ArrowLeft: -1, ArrowRight: 1 }[event.key];
        if (index >= 0 && delta !== undefined) {
            event.preventDefault();
            const next = index + delta;
            if (next >= 0 && next < tiles.length && (Math.abs(delta) === 5 || Math.floor(index / 5) === Math.floor(next / 5))) tiles[next][0].focus();
        }
    });
    root.querySelector('[data-designer-reset]').addEventListener('click', () => {
        layout = gardenLayout(presets[preset.value]);
        save();
    });
    let previous = preset.value;
    preset.addEventListener('change', () => {
        if (JSON.stringify(layout) === JSON.stringify(gardenLayout(presets[previous]))) layout = gardenLayout(presets[preset.value]);
        if (preset.value !== 'conditions') layout.crystals = [];
        if (tool === 'crystal' && preset.value !== 'conditions') tool = 'path';
        previous = preset.value;
        save();
    });
    root.querySelector('[data-designer-preview]').addEventListener('click', () => {
        if (!valid) return;
        try {
            const game = validateInstance({ ...instance(),
                ...Object.fromEntries(Object.entries({ title: 'game_title', instructions: 'game_instructions', hint: 'game_hint', learningIdea: 'game_learning_idea' })
                    .map(([key, name]) => [key, form.querySelector(`[name="${name}"]`).value])) });
            const fragment = root.querySelector('template').content.cloneNode(true);
            const preview = fragment.querySelector('[data-coding-game]');
            preview.dataset.codingGame = JSON.stringify(game);
            preview.setAttribute('aria-label', `${game.title} coding game`);
            preview.querySelector('h2').textContent = game.title;
            preview.querySelector('.game-instructions p').textContent = game.instructions;
            preview.querySelector('[data-game-success] span').textContent = game.learningIdea;
            preview.querySelector('.step-pill').textContent = game.concept;
            preview.querySelector('[data-game-board]').setAttribute('aria-label', `Garden with ${game.width} columns and ${game.height} rows. Start: column ${game.start[0] + 1}, row ${game.start[1] + 1}. Goal: column ${game.goal[0] + 1}, row ${game.goal[1] + 1}. Path: ${game.path.map(([x, y]) => `${x + 1},${y + 1}`).join('; ')}. Crystals: ${game.crystals.map(([x, y]) => `${x + 1},${y + 1}`).join('; ') || 'none'}.`);
            // The reusable shell contains both mode controls; activate only the selected mechanic.
            preview.querySelector('[data-game-repeat]').closest('label').hidden = game.mode !== 'loop';
            preview.querySelector('[data-game-conditional]').closest('label').hidden = game.mode !== 'conditional';
            host.replaceChildren(fragment);
            mountGarden(preview);
            status.textContent = 'Preview only. Play this level below; no learner progress is saved.';
        } catch {
            status.textContent = 'Check your layout and prompts before trying the level.';
        }
    });
    refresh();
    if (valid) input.value = JSON.stringify(layout);
}
