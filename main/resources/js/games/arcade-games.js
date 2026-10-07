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
    const previewStatus = root.querySelector('[data-arcade-preview-status]');
    const count = root.querySelector('[data-arcade-count]');
    const programList = root.querySelector('[data-arcade-program]');
    let busy = false;
    let selectedColor = 'mint';
    let selectedPosition = null;
    let colorSelect;
    let terminalInput;
    let historyIndex = 0;
    const history = [];
    const lines = () => code.value.trim() ? code.value.trim().split('\n').map(line => line.trim()) : [];
    function node(tag, text, className = '') {
        const element = document.createElement(tag);
        element.textContent = text;
        element.className = className;
        return element;
    }
    function button(text, className, label, action) {
        const element = node('button', text, className);
        element.type = 'button';
        if (label) element.setAttribute('aria-label', label);
        element.disabled = busy;
        element.addEventListener('click', () => { if (!busy) action(); });
        return element;
    }
    function render(state) {
        world.replaceChildren();
        if (game.template === 'pixel-studio') {
            const target = Object.fromEntries(game.scenario.pixels.map((pixel) => { const [x, y, color] = pixel.split(' '); return [`${x} ${y}`, color]; }));
            for (const [label, pixels] of [['Target picture', target], ['Your canvas', state.pixels]]) {
                const panel = node('div', '', 'pixel-panel');
                panel.append(node('h3', label));
                const grid = node('div', '', 'pixel-grid');
                for (let y = 1; y <= 3; y++) for (let x = 1; x <= 3; x++) {
                    const color = pixels[`${x} ${y}`] ?? 'blank';
                    const tileLabel = `Column ${x}, row ${y}: ${color}`;
                    const tile = label === 'Your canvas'
                        ? button(`${x},${y}`, `pixel-tile pixel-${color}`, `Paint column ${x}, row ${y}; currently ${color}`, () => paint(x, y))
                        : node('span', `${x},${y}`, `pixel-tile pixel-${color}`);
                    if (label === 'Target picture') { tile.setAttribute('role', 'img'); tile.setAttribute('aria-label', tileLabel); }
                    grid.append(tile);
                }
                panel.append(grid);
                world.append(panel);
            }
        } else if (game.template === 'number-machine') {
            const start = node('div', '', 'machine-node'); start.append(node('small', 'START'), node('b', String(game.scenario.start)));
            const current = node('div', '', 'machine-node number-display'); current.append(node('small', 'YOUR VALUE'), node('b', String(state.number)));
            const target = node('div', '', 'machine-node'); target.append(node('small', 'TARGET'), node('b', String(game.scenario.target)));
            world.append(start, node('span', '→', 'machine-arrow'), current, node('span', '→', 'machine-arrow'), target);
        } else if (game.template === 'sort-lab') {
            const stack = node('div', '', 'sort-stack');
            state.items.forEach((value, index) => {
                const tile = button('', 'sort-tile', `Select position ${index + 1}, value ${value}`, () => {
                    if (selectedPosition === null) { selectedPosition = index; render(state); world.querySelector(`[aria-label^="Select position ${index + 1},"]`)?.focus({ preventScroll: true }); previewStatus.textContent = 'Now choose a second tile to swap.'; }
                    else {
                        const first = selectedPosition; selectedPosition = null;
                        if (first !== index) add(`swap ${first + 1} ${index + 1}`); else preview();
                        world.querySelector(`[aria-label^="Select position ${index + 1},"]`)?.focus({ preventScroll: true });
                    }
                });
                tile.setAttribute('aria-pressed', String(selectedPosition === index));
                tile.append(node('b', String(value)), node('small', `position ${index + 1}`));
                const bar = node('span', '', 'sort-bar'); bar.style.setProperty('--bar-height', `${30 + value / 99 * 60}%`); tile.append(bar);
                stack.append(tile);
            });
            world.append(stack, node('p', 'Tap two tiles to swap them. Put the smallest value first.', 'scene-instruction'));
        } else {
            const mission = node('div', '', 'terminal-mission');
            mission.append(node('span', 'VIRTUAL WORKSPACE', 'game-kicker'), node('h3', `Make ${game.scenario.destination}`), node('p', `Copy a file containing “${game.scenario.content}”, then inspect the new file with cat.`));
            const files = node('ul', '', 'terminal-files');
            Object.keys(state.files).forEach(name => files.append(node('li', `▤  ${name}`)));
            world.append(mission, files, node('p', 'A safe simulator. Commands never access your computer.', 'scene-instruction'));
        }
    }
    function preview() {
        selectedPosition = null;
        const program = lines();
        const result = evaluateArcade(game, program);
        render(result.state);
        count.textContent = `${program.length} / 12 steps`;
        programList.replaceChildren();
        program.slice(0, 12).forEach((command, index) => {
            const row = node('li', '');
            row.append(node('small', String(index + 1)), node('code', command));
            row.append(button('×', 'instruction-remove', `Remove step ${index + 1}`, () => {
                const updated = lines(); updated.splice(index, 1); code.value = updated.join('\n'); preview();
                programList.querySelectorAll('button')[Math.min(index, updated.length - 1)]?.focus({ preventScroll: true });
                if (!updated.length) root.querySelector('[data-arcade-run]').focus();
            }));
            programList.append(row);
        });
        root.dataset.playState = 'ready';
        previewStatus.textContent = program.length ? 'Preview only. Run your program to check and save an eligible win.' : 'Build your idea, then run it to check the objective.';
    }
    function select(label, values) {
        const wrapper = node('label', label);
        const input = document.createElement('select');
        for (const value of values) { const option = node('option', String(value)); option.value = value; input.append(option); }
        wrapper.append(input); controls.append(wrapper);
        return input;
    }
    function add(command) {
        if (busy || !command) return false;
        const program = lines();
        if (program.length >= 12) { output.textContent = 'Your program already has 12 instructions. Remove one or reset.'; return false; }
        code.value = [...program, command].join('\n'); preview(); return true;
    }
    function paint(x, y) {
        if (busy) return;
        const program = lines();
        const command = `paint ${x} ${y} ${selectedColor}`;
        const index = program.findLastIndex(line => line.startsWith(`paint ${x} ${y} `));
        if (index >= 0) { program[index] = command; code.value = program.join('\n'); preview(); }
        else add(command);
        world.querySelector(`[aria-label^="Paint column ${x}, row ${y};"]`)?.focus({ preventScroll: true });
    }
    let build;
    if (game.template === 'pixel-studio') {
        const palette = node('div', '', 'paint-palette'); palette.setAttribute('role', 'group'); palette.setAttribute('aria-label', 'Paint color');
        ['mint', 'peach', 'lavender'].forEach(color => {
            const swatch = button(color, `paint-swatch pixel-${color}`, `Paint with ${color}`, () => {
                selectedColor = color; colorSelect.value = color;
                palette.querySelectorAll('button').forEach(option => option.setAttribute('aria-pressed', String(option === swatch)));
            });
            swatch.setAttribute('aria-pressed', String(color === selectedColor)); palette.append(swatch);
        });
        controls.append(palette, node('p', 'Choose a color, then tap your canvas. Or add an instruction below.', 'field-hint'));
        const x = select('Column', [1, 2, 3]); const y = select('Row', [1, 2, 3]); colorSelect = select('Color', ['mint', 'peach', 'lavender']);
        colorSelect.addEventListener('change', () => {
            selectedColor = colorSelect.value;
            palette.querySelectorAll('button').forEach(option => option.setAttribute('aria-pressed', String(option.textContent === selectedColor)));
        });
        build = () => `paint ${x.value} ${y.value} ${colorSelect.value}`;
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
        const wrapper = node('label', 'kody@workspace $', 'terminal-prompt'); terminalInput = document.createElement('input'); terminalInput.type = 'text'; terminalInput.maxLength = 100; terminalInput.placeholder = 'ls'; terminalInput.autocomplete = 'off'; terminalInput.spellcheck = false; wrapper.append(terminalInput); controls.append(wrapper);
        build = () => terminalInput.value.trim();
        terminalInput.addEventListener('keydown', async (event) => {
            if (busy) return;
            if (event.key === 'Enter') { event.preventDefault(); await enterCommand(); }
            else if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                event.preventDefault(); historyIndex = Math.max(0, Math.min(history.length, historyIndex + (event.key === 'ArrowUp' ? -1 : 1)));
                terminalInput.value = history[historyIndex] ?? '';
            }
        });
    }
    async function enterCommand() {
        const command = build();
        if (busy || !add(command)) return;
        history.push(command); historyIndex = history.length; terminalInput.value = ''; await run(); terminalInput.focus();
    }
    controls.append(button(game.template === 'terminal-quest' ? 'Enter command' : 'Add instruction', 'button button-dark', null, async () => {
        if (game.template === 'terminal-quest') await enterCommand(); else add(build());
    }));
    controls.append(button('Remove last instruction', 'game-reset', null, () => {
        const program = lines(); program.pop(); code.value = program.join('\n'); preview(); output.textContent = 'Instruction removed. Run the program to see the updated state.';
    }));
    code.addEventListener('input', () => { if (!busy) preview(); });
    root.querySelector('[data-arcade-help]').textContent = { 'pixel-studio': 'Syntax: paint 1 1 mint. Blank cells must stay blank.', 'number-machine': 'Syntax: add 3, subtract 2, multiply 2. One instruction per line.', 'sort-lab': 'Syntax: swap 1 2. Sort smallest to largest.', 'terminal-quest': 'Commands: ls · cat filename · cp source destination. Up/Down recalls commands. No pipes, scripts or host shell access.' }[game.template];
    async function run() {
        if (busy) return;
        busy = true; selectedPosition = null; root.dataset.playState = 'running'; root.setAttribute('aria-busy', 'true');
        root.querySelectorAll('button, input, select, textarea').forEach(control => { control.disabled = true; });
        try {
            const program = lines();
            const delay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 220;
            if (game.template !== 'terminal-quest') for (let step = 1; step <= Math.min(program.length, 12); step++) {
                const frame = evaluateArcade(game, program.slice(0, step));
                render(frame.state); previewStatus.textContent = `Running step ${step} of ${program.length}…`;
                if (delay) await new Promise(resolve => setTimeout(resolve, delay));
                if (frame.error) break;
            }
            const result = evaluateArcade(game, program); render(result.state);
            output.textContent = [...result.output, result.error ?? (result.success ? `Objective complete! ${game.learningIdea}` : 'Keep experimenting. The objective is not complete yet.')].join('\n');
            root.dataset.playState = result.success ? 'success' : 'retry';
            previewStatus.textContent = result.success ? 'Objective complete. You made that happen!' : 'Try changing a step, then run it again.';
            if (result.success) {
                const message = await saveCompletion(root, { program });
                if (message) output.textContent += `\n${message}`;
            }
        } finally {
            busy = false; root.setAttribute('aria-busy', 'false');
            root.querySelectorAll('button, input, select, textarea').forEach(control => { control.disabled = false; });
        }
    }
    root.querySelector('[data-arcade-run]').addEventListener('click', run);
    root.querySelector('[data-arcade-hint]').addEventListener('click', () => { output.textContent = game.hint; });
    root.querySelector('[data-arcade-reset]').addEventListener('click', () => {
        if (!busy) { code.value = ''; history.length = 0; historyIndex = 0; if (terminalInput) terminalInput.value = ''; preview(); output.textContent = 'Ready when you are.'; }
    });
    preview();
}
