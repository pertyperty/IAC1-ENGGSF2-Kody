import { saveCompletion } from './save-completion.js';

export const arcadeTemplates = ['pixel-studio', 'number-machine', 'sort-lab', 'terminal-quest'];

export function validScenario(template, scenario) {
    if (!scenario || typeof scenario !== 'object' || Array.isArray(scenario)) return false;
    const keys = { 'pixel-studio': ['pixels'], 'number-machine': ['start', 'target'], 'sort-lab': ['items'], 'terminal-quest': ['files', 'destination', 'content'] }[template];
    if (!keys || Object.keys(scenario).length !== keys.length || keys.some((key) => !Object.hasOwn(scenario, key))) return false;
    const integer = (value, min, max) => Number.isInteger(value) && value >= min && value <= max;
    const text = (value) => typeof value === 'string' && value.trim().length > 0 && [...value].length <= 300 && !/[\r\n]/.test(value);
    const filename = (value) => typeof value === 'string' && /^[a-z][a-z0-9_-]{0,19}\.[a-z]{1,5}$/.test(value);
    switch (template) {
        case 'pixel-studio': return Array.isArray(scenario.pixels) && scenario.pixels.length >= 1 && scenario.pixels.length <= 9
            && scenario.pixels.every((pixel) => typeof pixel === 'string' && /^[1-3] [1-3] (mint|peach|lavender)$/.test(pixel))
            && new Set(scenario.pixels.map((pixel) => pixel.slice(0, 3))).size === scenario.pixels.length;
        case 'number-machine': return integer(scenario.start, -100, 100) && integer(scenario.target, -100, 100);
        case 'sort-lab': return Array.isArray(scenario.items) && scenario.items.length >= 2 && scenario.items.length <= 6 && scenario.items.every((item) => integer(item, 0, 99));
        case 'terminal-quest': return Array.isArray(scenario.files) && scenario.files.length >= 1 && scenario.files.length <= 8
            && scenario.files.every((file) => file && Object.keys(file).length === 2 && filename(file.name) && text(file.content))
            && new Set(scenario.files.map((file) => file.name)).size === scenario.files.length && filename(scenario.destination) && text(scenario.content)
            && !scenario.files.some((file) => file.name === scenario.destination) && scenario.files.some((file) => file.content === scenario.content);
        default: return false;
    }
}

export function evaluateArcade(game, program) {
    const scenario = game.scenario;
    const state = { number: scenario.start ?? 0, items: [...(scenario.items ?? [])], pixels: {}, files: Object.fromEntries((scenario.files ?? []).map((file) => [file.name, file.content])) };
    const output = [];
    let readDestination = false;
    const failure = (message) => ({ state, output, success: false, error: message });
    if (!arcadeTemplates.includes(game.template) || game.version !== 1 || !Array.isArray(program) || program.length < 1 || program.length > 12) return failure('Use between 1 and 12 instructions.');
    for (const command of program) {
        if (typeof command !== 'string' || command.length > 100 || command.trim() !== command) return failure('Check the instruction format.');
        let match;
        switch (game.template) {
            case 'pixel-studio':
                match = /^paint ([1-3]) ([1-3]) (mint|peach|lavender)$/.exec(command);
                if (!match) return failure('Use paint column row color. Colors: mint, peach, lavender. Coordinates: 1–3.');
                state.pixels[`${match[1]} ${match[2]}`] = match[3];
                output.push(command);
                break;
            case 'number-machine': {
                match = /^(add|subtract|multiply) (-?\d{1,3})$/.exec(command);
                if (!match || Math.abs(Number(match[2])) > 100) return failure('Use add, subtract or multiply with an integer from -100 to 100.');
                const value = Number(match[2]);
                state.number = match[1] === 'add' ? state.number + value : match[1] === 'subtract' ? state.number - value : state.number * value;
                if (Math.abs(state.number) > 10000) return failure('Keep the stored value between -10000 and 10000.');
                output.push(`${command} → value = ${state.number}`);
                break;
            }
            case 'sort-lab':
                match = /^swap ([1-6]) ([1-6])$/.exec(command);
                if (!match || Math.max(Number(match[1]), Number(match[2])) > state.items.length) return failure('Swap two existing positions, numbered from 1.');
                [state.items[match[1] - 1], state.items[match[2] - 1]] = [state.items[match[2] - 1], state.items[match[1] - 1]];
                output.push(`${command} → [${state.items.join(', ')}]`);
                break;
            case 'terminal-quest':
                output.push(`kody@workspace $ ${command}`);
                if (command === 'ls') output.push(Object.keys(state.files).join('  '));
                else if ((match = /^cat ([a-z][a-z0-9_-]{0,19}\.[a-z]{1,5})$/.exec(command)) && Object.hasOwn(state.files, match[1])) {
                    output.push(state.files[match[1]]);
                    readDestination = match[1] === scenario.destination && state.files[match[1]] === scenario.content;
                } else if ((match = /^cp ([a-z][a-z0-9_-]{0,19}\.[a-z]{1,5}) ([a-z][a-z0-9_-]{0,19}\.[a-z]{1,5})$/.exec(command)) && Object.hasOwn(state.files, match[1])) {
                    state.files[match[2]] = state.files[match[1]];
                    readDestination = false;
                    output.push('Copied.');
                } else return failure('Unknown command or file. Use ls, cat filename or cp source destination.');
                break;
        }
    }
    const expectedPixels = Object.fromEntries((scenario.pixels ?? []).map((pixel) => { const [x, y, color] = pixel.split(' '); return [`${x} ${y}`, color]; }));
    const pixelsMatch = Object.keys(state.pixels).length === Object.keys(expectedPixels).length && Object.entries(expectedPixels).every(([point, color]) => state.pixels[point] === color);
    const success = { 'pixel-studio': pixelsMatch, 'number-machine': state.number === scenario.target,
        'sort-lab': state.items.every((item, index) => item === [...(scenario.items ?? [])].sort((a, b) => a - b)[index]),
        'terminal-quest': readDestination && state.files[scenario.destination] === scenario.content };
    return { state, output, success: success[game.template], error: null };
}

export function mountArcade(root) {
    const game = JSON.parse(root.dataset.arcadeGame);
    if (!arcadeTemplates.includes(game.template) || game.version !== 1 || !validScenario(game.template, game.scenario)) throw new Error('Invalid activity scenario.');
    const world = root.querySelector('[data-arcade-world]');
    const code = root.querySelector('[data-arcade-code]');
    const output = root.querySelector('[data-arcade-output]');
    const controls = root.querySelector('[data-arcade-controls]');
    let busy = false;
    function node(tag, text, className = '') {
        const element = document.createElement(tag);
        element.textContent = text;
        element.className = className;
        return element;
    }
    function render(state) {
        world.replaceChildren();
        if (game.template === 'pixel-studio') {
            const target = Object.fromEntries(game.scenario.pixels.map((pixel) => { const [x, y, color] = pixel.split(' '); return [`${x} ${y}`, color]; }));
            for (const [label, pixels] of [['Target picture', target], ['Your canvas', state.pixels]]) {
                const panel = node('div', '');
                panel.append(node('h3', label));
                const grid = node('div', '', 'pixel-grid');
                for (let y = 1; y <= 3; y++) for (let x = 1; x <= 3; x++) {
                    const color = pixels[`${x} ${y}`] ?? 'blank';
                    const tile = node('span', `${x},${y}`, `pixel-tile pixel-${color}`);
                    tile.setAttribute('aria-label', `Column ${x}, row ${y}: ${color}`);
                    grid.append(tile);
                }
                panel.append(grid);
                world.append(panel);
            }
        } else if (game.template === 'number-machine') {
            world.append(node('div', `value = ${state.number}`, 'number-display'), node('div', `Target: ${game.scenario.target}`, 'step-pill'));
        } else if (game.template === 'sort-lab') {
            state.items.forEach((value, index) => world.append(node('div', `${value}\nposition ${index + 1}`, 'sort-tile')));
        } else {
            world.append(node('p', `Mission: copy a file containing “${game.scenario.content}” to ${game.scenario.destination}, then cat it.`), node('p', 'Virtual workspace only. These commands do not access your computer or run Python, Java or C++.'));
        }
    }
    function select(label, values) {
        const wrapper = node('label', label);
        const input = document.createElement('select');
        for (const value of values) { const option = node('option', String(value)); option.value = value; input.append(option); }
        wrapper.append(input);
        controls.append(wrapper);
        return input;
    }
    function add(command) {
        const lines = code.value.trim() ? code.value.trim().split('\n') : [];
        if (lines.length >= 12) { output.textContent = 'Your program already has 12 instructions. Remove one or reset.'; return false; }
        code.value = [...lines, command].join('\n');
        return true;
    }
    let build;
    if (game.template === 'pixel-studio') {
        const x = select('Column', [1, 2, 3]); const y = select('Row', [1, 2, 3]); const color = select('Color', ['mint', 'peach', 'lavender']);
        build = () => `paint ${x.value} ${y.value} ${color.value}`;
    } else if (game.template === 'number-machine') {
        const operation = select('Operation', ['add', 'subtract', 'multiply']);
        const wrapper = node('label', 'Operand'); const value = document.createElement('input'); value.type = 'number'; value.min = '-100'; value.max = '100'; value.value = '3'; wrapper.append(value); controls.append(wrapper);
        build = () => `${operation.value} ${value.value}`;
    } else if (game.template === 'sort-lab') {
        const indexes = game.scenario.items.map((_, index) => index + 1);
        const a = select('First position', indexes); const b = select('Second position', indexes);
        build = () => `swap ${a.value} ${b.value}`;
    } else {
        root.classList.add('terminal-game');
        const wrapper = node('label', 'kody@workspace $'); const input = document.createElement('input'); input.type = 'text'; input.maxLength = 100; input.placeholder = 'ls'; input.autocomplete = 'off'; input.spellcheck = false; wrapper.append(input); controls.append(wrapper);
        build = () => input.value.trim();
        input.addEventListener('keydown', async (event) => { if (event.key === 'Enter') { event.preventDefault(); if (!busy && add(build())) { input.value = ''; await run(); } } });
    }
    const append = node('button', game.template === 'terminal-quest' ? 'Enter command' : 'Add instruction', 'button button-dark'); append.type = 'button';
    append.addEventListener('click', async () => { if (!busy && add(build()) && game.template === 'terminal-quest') await run(); }); controls.append(append);
    const undo = node('button', 'Remove last instruction', 'game-reset'); undo.type = 'button';
    undo.addEventListener('click', () => { if (!busy) { const lines = code.value.trim().split('\n'); lines.pop(); code.value = lines.join('\n'); output.textContent = 'Instruction removed. Run the program to see the updated state.'; } }); controls.append(undo);
    root.querySelector('[data-arcade-help]').textContent = { 'pixel-studio': 'Syntax: paint 1 1 mint. Blank cells must stay blank.', 'number-machine': 'Syntax: add 3, subtract 2, multiply 2. One instruction per line.', 'sort-lab': 'Syntax: swap 1 2. Sort smallest to largest.', 'terminal-quest': 'Commands: ls · cat filename · cp source destination. Enter commands above or edit the program below. No pipes, scripts or host shell access.' }[game.template];
    async function run() {
        if (busy) return;
        busy = true;
        root.querySelector('[data-arcade-run]').disabled = true;
        try {
            const program = code.value.trim().split('\n').map((line) => line.trim());
            const result = evaluateArcade(game, program);
            render(result.state);
            output.textContent = [...result.output, result.error ?? (result.success ? `Objective complete! ${game.learningIdea}` : 'Keep experimenting. The objective is not complete yet.')].join('\n');
            if (result.success) {
                const message = await saveCompletion(root, { program });
                if (message) output.textContent += `\n${message}`;
            }
        } finally { busy = false; root.querySelector('[data-arcade-run]').disabled = false; }
    }
    root.querySelector('[data-arcade-run]').addEventListener('click', run);
    root.querySelector('[data-arcade-hint]').addEventListener('click', () => { output.textContent = game.hint; });
    root.querySelector('[data-arcade-reset]').addEventListener('click', () => { if (!busy) { code.value = ''; render(evaluateArcade(game, []).state); output.textContent = 'Ready when you are.'; } });
    render(evaluateArcade(game, []).state);
}
