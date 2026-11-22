import { revealGameFeedback } from './workspace.js';
import { mountLearningTrace, arcadeTrace, readableInstruction as formatInstruction } from './learning-trace.js';
import { createIcon } from '../icons.js';
import { puzzleTemplates, validPuzzle, evaluatePuzzle, renderPuzzle } from './puzzle-games.js';
import { saveCompletion } from './save-completion.js';
import { programRuleError, mountConceptCheck, objectiveFeedback } from './assessment-challenges.js';
import { mountMission } from './mission.js';
import { normalizeArcadeCommand } from './arcade-pseudocode.js';

const readableInstruction = (template, command) => formatInstruction(template, normalizeArcadeCommand(template, command));

export const arcadeTemplates = ['pixel-studio', 'number-machine', 'sort-lab', 'terminal-quest', ...puzzleTemplates];

export function validScenario(template, scenario) {
    if (puzzleTemplates.includes(template)) return validPuzzle(template, scenario);
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
    if (Array.isArray(program) && program.length <= 12) {
        program = program.map(command => normalizeArcadeCommand(game.template, command));
    }
    const ruleError = Array.isArray(program) ? programRuleError(game, program) : null;
    if (ruleError) return { ...evaluateArcade({ ...game, assessment: undefined }, []), success: false, error: ruleError };
    if (puzzleTemplates.includes(game.template)) return evaluatePuzzle(game, program);
    const scenario = game.scenario;
    const state = { number: scenario.start ?? 0, items: [...(scenario.items ?? [])], pixels: {}, files: Object.fromEntries((scenario.files ?? []).map((file) => [file.name, file.content])) };
    const output = [];
    let readDestination = false;
    let instructionIndex = -1;
    const failure = (message) => ({ state, output, success: false, error: message, failedStep: instructionIndex });
    if (!arcadeTemplates.includes(game.template) || game.version !== 1 || !Array.isArray(program) || program.length < 1 || program.length > 12) return failure('Use between 1 and 12 instructions.');
    for (const command of program) {
        instructionIndex++;
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

const mountedArcades = new WeakSet();
export function mountArcade(root) {
    if (mountedArcades.has(root)) return;
    mountedArcades.add(root);
    const game = JSON.parse(root.dataset.arcadeGame);
    if (game.template === 'terminal-quest') root.classList.add('terminal-game');
    if (!arcadeTemplates.includes(game.template) || game.version !== 1 || !validScenario(game.template, game.scenario)) throw new Error('Invalid activity scenario.');
    const world = root.querySelector('[data-arcade-world]');
    const code = root.querySelector('[data-arcade-code]');
    const output = root.querySelector('[data-arcade-output]');
    const controls = root.querySelector('[data-arcade-controls]');
    const previewStatus = root.querySelector('[data-arcade-preview-status]');
    const count = root.querySelector('[data-arcade-count]');
    const programList = root.querySelector('[data-arcade-program]');
    const firstPlayCue = root.querySelector('[data-first-play-cue]');
    const conceptCheck = mountConceptCheck(root, game);
    const limit = game.assessment?.limit ?? 12;
    const mission = mountMission(root, game);
    let busy = false;
    let inspector;
    let selectedColor = 'mint';
    const availablePaintColors = game.template === 'pixel-studio' && game.assessment?.commands.length
        ? [...new Set(game.assessment.commands.map(command => command.split(' ')[3]))]
        : ['mint', 'peach', 'lavender'];
    if (game.template === 'pixel-studio') selectedColor = availablePaintColors[0];
    let selectedPosition = null;
    let terminalInput;
    let syncOperand = () => {};
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
        mission.update(state);
        world.replaceChildren();
        if (puzzleTemplates.includes(game.template)) {
            renderPuzzle(game, state, world, node, button, add);
        } else if (game.template === 'pixel-studio') {
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
                    if (label === 'Your canvas') tile.dataset.match = color === (target[`${x} ${y}`] ?? 'blank') ? 'same' : 'different';
                    grid.append(tile);
                }
                panel.append(grid);
                world.append(panel);
            }
        } else if (game.template === 'number-machine') {
            const start = node('div', '', 'machine-node'); start.append(node('small', 'START'), node('b', String(game.scenario.start)));
            const current = node('div', '', 'machine-node number-display'); current.append(node('small', 'YOUR VALUE'), node('b', String(state.number)));
            const target = node('div', '', 'machine-node'); target.append(node('small', 'TARGET'), node('b', String(game.scenario.target)));
            const arrow = () => {
                const element = node('span', '', 'machine-arrow');
                element.append(createIcon(root.ownerDocument, 'right'));
                return element;
            };
            world.append(start, arrow(), current, arrow(), target);
            const trail = node('ol', '', 'value-journey'); trail.setAttribute('aria-label', 'Value after each instruction');
            lines().slice(0, 12).forEach((command, index) => {
                const frame = evaluateArcade(game, lines().slice(0, index + 1));
                const item = node('li', '', frame.error ? 'journey-error' : '');
                item.append(node('small', `Step ${index + 1}`), node('code', readableInstruction(game.template, command)), node('b', frame.error ? 'Check instruction' : String(frame.state.number)));
                trail.append(item);
            });
            const history = node('details', '', 'value-history'); history.append(node('summary', 'Trace changes'), trail); world.append(history);
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
                tile.dataset.match = value === [...game.scenario.items].sort((a, b) => a - b)[index] ? 'same' : 'different';
                tile.append(node('b', String(value)), node('small', `position ${index + 1}`));
                const bar = node('span', '', 'sort-bar'); bar.style.setProperty('--bar-height', `${30 + value / 99 * 60}%`); tile.append(bar);
                stack.append(tile);
            });
            const goal = node('p', `Target: [${[...game.scenario.items].sort((a, b) => a - b).join(', ')}]`, 'array-target');
            world.append(goal, stack, node('p', 'Select two positions to add a swap.', 'scene-instruction'));
        } else {
            const mission = node('div', '', 'terminal-mission');
            mission.append(node('span', 'VIRTUAL WORKSPACE', 'game-kicker'), node('h3', `Make ${game.scenario.destination}`), node('p', `Copy a file containing “${game.scenario.content}”, then inspect the new file with cat.`));
            const files = node('ul', '', 'terminal-files');
            Object.keys(state.files).forEach(name => {
                const item = node('li', '');
                item.append(button(name, 'file-inspect', `Read ${name}`, () => add(`cat ${name}`)));
                files.append(item);
            });
            world.append(mission, files, node('p', 'A safe simulator. Commands never access your computer.', 'scene-instruction'));
        }
    }
    function preview() {
        conceptCheck.reset();
        selectedPosition = null;
        const program = lines();
        const result = evaluateArcade(game, program);
        render(result.state);
        count.textContent = `${program.length} / ${limit} instructions`;
        root.querySelector('[data-arcade-undo]').disabled = busy || !program.length;
        root.querySelectorAll('[data-add-command]').forEach(control => { control.disabled = busy || program.length >= limit; });
        root.querySelector('[data-arcade-run]').disabled = busy || program.length === 0;
        programList.replaceChildren();
        if (!program.length) programList.append(node('li', 'Add an instruction to start.', 'trace-placeholder'));
        program.slice(0, 12).forEach((command, index) => {
            const row = node('li', '');
            row.dataset.instructionIndex = String(index);
            row.append(node('small', String(index + 1)), node('code', readableInstruction(game.template, command)));
            const remove = button('', 'instruction-remove', `Remove step ${index + 1}`, () => {
                const updated = lines(); updated.splice(index, 1); code.value = updated.join('\n'); preview();
                programList.querySelectorAll('button')[Math.min(index, updated.length - 1)]?.focus({ preventScroll: true });
                if (!updated.length) controls.querySelector('button:not(:disabled), input:not(:disabled)')?.focus();
            }); remove.append(createIcon(root.ownerDocument, 'close')); row.append(remove);
            row.tabIndex = 0;
            row.setAttribute('aria-label', `Step ${index + 1}: ${readableInstruction(game.template, command)}. Alt and arrow keys reorder; Delete removes.`);
            row.addEventListener('keydown', event => {
                if (busy || event.target !== row) return;
                const updated = lines(); let destination = index;
                if (event.key === 'Delete' || event.key === 'Backspace') { event.preventDefault(); updated.splice(index, 1); }
                else if (event.altKey && ['ArrowUp', 'ArrowDown'].includes(event.key)) {
                    event.preventDefault(); destination = index + (event.key === 'ArrowUp' ? -1 : 1);
                    if (destination < 0 || destination >= updated.length) return;
                    [updated[index], updated[destination]] = [updated[destination], updated[index]];
                } else return;
                code.value = updated.join('\n'); preview();
                if (updated.length) programList.querySelectorAll('li')[Math.min(destination, updated.length - 1)]?.focus({ preventScroll: true });
                else controls.querySelector('button:not(:disabled), input:not(:disabled)')?.focus();
            });
            programList.append(row);
        });
        inspector?.refresh();
        root.dataset.playState = 'ready';
        output.textContent = program.length >= limit ? 'Instruction limit reached. Undo or remove a step to make changes.' : program.length ? 'Program changed. Run to check the goal.' : 'Add instructions, then run your program.';
        root.querySelector('[data-execution-label]').textContent = program.length ? 'Preview' : 'Not run';
        previewStatus.textContent = program.length ? 'Preview of your instructions. Run to check the goal.'
            : ['pixel-studio', 'sort-lab', 'logic-switchboard'].includes(game.template) ? firstSteps[game.template] : 'Your starting state. Build a program below.';
    }
    function select(label, values) {
        const wrapper = node('label', label);
        const input = document.createElement('select');
        for (const value of values) {
            const option = node('option', label === 'Instruction' ? readableInstruction(game.template, value) : String(value));
            option.value = value; input.append(option);
        }
        wrapper.append(input); controls.append(wrapper);
        return input;
    }
    function commandAvailable(command) {
        if (game.assessment?.commands.length && !game.assessment.commands.includes(command)) {
            output.textContent = 'That instruction is not available in this task. Use the available instruction buttons.';
            revealGameFeedback(output);
            return false;
        }
        return true;
    }
    function add(command) {
        if (busy || !command || !commandAvailable(command)) return false;
        const program = lines();
        if (program.length >= limit) { output.textContent = `Use at most ${limit} instructions. Remove a step to change your program.`; return false; }
        code.value = [...program, command].join('\n');
        preview();
        programList.scrollTop = programList.scrollHeight;
        return true;
    }
    function paint(x, y) {
        if (busy) return;
        const program = lines();
        const command = `paint ${x} ${y} ${selectedColor}`;
        if (!commandAvailable(command)) return;
        const index = program.findLastIndex(line => line.startsWith(`paint ${x} ${y} `));
        if (index >= 0) { program[index] = command; code.value = program.join('\n'); preview(); }
        else add(command);
        world.querySelector(`[aria-label^="Paint column ${x}, row ${y};"]`)?.focus({ preventScroll: true });
    }
    let build;
    const firstSteps = {
        'pixel-studio': 'Choose a color and tap Your canvas. Run to check the picture.',
        'sort-lab': 'Tap two numbered tiles to add a swap. Run to check the order.',
        'logic-switchboard': 'Tap an input switch to add a flip. Run to check the output.',
        'terminal-quest': 'Add a command to your program, then select Run.',
        'number-machine': 'Tap an instruction to add it. Run tests the complete program.',
        'string-forge': 'Tap a transformation to add it. Order changes the result.',
        'memory-trail': 'Tap an operation to add it. Only the top of the stack changes.',
    };
    if (firstPlayCue) {
        if (['pixel-studio', 'sort-lab', 'logic-switchboard'].includes(game.template)) firstPlayCue.textContent = 'Your board actions become instructions below. Run to check the goal.';
        else if (game.template === 'terminal-quest' || game.assessment?.commands.length) firstPlayCue.textContent = firstSteps[game.template];
        else firstPlayCue.textContent = 'Choose an operation, then select Add instruction.';
    }
    if (game.template === 'pixel-studio') {
        const palette = node('div', '', 'paint-palette');
        palette.setAttribute('role', 'group'); palette.setAttribute('aria-label', 'Paint color');
        availablePaintColors.forEach(color => {
            const swatch = button(color, `paint-swatch pixel-${color}`, `Paint with ${color}`, () => {
                selectedColor = color;
                palette.querySelectorAll('button').forEach(option => option.setAttribute('aria-pressed', String(option === swatch)));
            });
            swatch.setAttribute('aria-pressed', String(color === selectedColor)); palette.append(swatch);
        });
        // Keep colors next to the canvas, outside the bounded state scroller.
        previewStatus.before(palette);
    }
    if (game.assessment?.commands.length) {
        const bank = node('div', '', 'instruction-bank'); bank.setAttribute('role', 'group'); bank.setAttribute('aria-label', 'Available instructions');
        const meanings = { add: 'Increase the value', subtract: 'Decrease the value', multiply: 'Scale the value', trim: 'Remove outer spaces', upper: 'Uppercase all letters', lower: 'Lowercase all letters', reverse: 'Reverse the characters', append: 'Add text at the end', pop: 'Remove TOP', swap: 'Exchange the top two', duplicate: 'Copy TOP' };
        game.assessment.commands.forEach(command => {
            const control = button('', 'instruction-choice', `Add ${readableInstruction(game.template, command)}`, () => add(command));
            control.dataset.addCommand = command;
            control.append(node('code', readableInstruction(game.template, command)), node('small', meanings[command.split(' ')[0]] ?? 'Add this instruction'));
            bank.append(control);
        });
        controls.append(bank);
    } else if (game.template === 'pixel-studio') {
        // Canvas taps add paint instructions using the palette above.
    } else if (game.template === 'number-machine') {
        const operation = select('Operation', ['add', 'subtract', 'multiply']);
        const wrapper = node('label', 'Number'); const value = document.createElement('input'); value.type = 'number'; value.min = '-100'; value.max = '100'; value.value = '3'; wrapper.append(value); controls.append(wrapper);
        build = () => `${operation.value} ${value.value}`;
    } else if (['sort-lab', 'logic-switchboard'].includes(game.template)) {
        // The board buttons add these instructions, including for keyboard users.
    } else if (puzzleTemplates.includes(game.template)) {
        const operations = game.template === 'string-forge' ? ['upper', 'lower', 'reverse', 'trim', 'append'] : ['push', 'pop', 'duplicate', 'swap'];
        const operation = select('Operation', operations);
        const wrapper = node('label', game.template === 'memory-trail' ? 'Value (0–9 for push)' : 'Text (for append)');
        const value = document.createElement('input'); value.type = 'text'; value.maxLength = 12; value.value = game.template === 'memory-trail' ? '2' : 'Code'; wrapper.append(value);
        controls.append(wrapper);
        syncOperand = () => {
            const needsValue = ['push', 'append'].includes(operation.value);
            wrapper.hidden = !needsValue;
            value.disabled = busy || !needsValue;
        };
        operation.addEventListener('change', syncOperand);
        syncOperand();
        build = () => `${operation.value}${['push', 'append'].includes(operation.value) ? ` ${value.value}` : ''}`;
    } else {
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
        history.push(command); historyIndex = history.length; terminalInput.value = ''; terminalInput.focus();
    }
    if (build) controls.append(button('Add instruction', 'button button-secondary', null, async () => {
        if (terminalInput) await enterCommand(); else add(build());
    }));
    code.addEventListener('input', () => { if (!busy) preview(); });
    const commandHelp = {
        'pixel-studio': 'Syntax: paint 1 1 mint. Blank cells must stay blank.',
        'number-machine': 'Syntax: add 3, subtract 2, multiply 2. One instruction per line.',
        'sort-lab': 'Syntax: swap 1 2. Sort smallest to largest.',
        'logic-switchboard': 'Tap the switches or use flip 1. AND: all on. OR: any on. XOR: odd number on.',
        'string-forge': 'Commands: upper, lower, reverse, trim, append Code. One transformation per line.',
        'memory-trail': 'Commands: push 2, pop, duplicate, swap. TOP marks the value that pop removes.',
        'terminal-quest': 'Commands: ls, cat filename, cp source destination. Up/Down recalls commands. No pipes, scripts or host shell access.',
    };
    root.querySelector('[data-arcade-help]').textContent = game.assessment?.commands.length
        ? `For this task: ${game.assessment.commands.join(', ')}. One instruction per line.`
        : commandHelp[game.template];
    async function run(showResult = true) {
        if (busy) return;
        const checkingAnswer = showResult && root.dataset.playState === 'checking';
        inspector.cancel();
        busy = true; selectedPosition = null; root.dataset.playState = 'running'; root.setAttribute('aria-busy', 'true');
        root.querySelectorAll('button, input, select, textarea').forEach(control => { control.disabled = true; });
        root.querySelector('[data-execution-label]').textContent = 'Running';
        try {
            const program = lines();
            const delay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 220;
            if (!checkingAnswer && game.template !== 'terminal-quest') for (let step = 1; step <= Math.min(program.length, 12); step++) {
                const frame = evaluateArcade(game, program.slice(0, step));
                inspector.highlight(step - 1);
                render(frame.state); previewStatus.textContent = `Step ${step} of ${program.length}: ${readableInstruction(game.template, program[step - 1])}`;
                if (delay) await new Promise(resolve => setTimeout(resolve, delay));
                if (frame.error) break;
            }
            const result = evaluateArcade(game, program); render(result.state);
            if (result.success && showResult) mission.update({ ...result.state, verified: true });
            if (result.error && result.failedStep >= 0) programList.querySelectorAll('li')[result.failedStep]?.classList.add('instruction-error');
            output.textContent = [...(game.template === 'terminal-quest' ? result.output : []), result.success ? `Program complete. ${game.learningIdea}` : objectiveFeedback(game, result)].join('\n');
            root.dataset.playState = result.success ? 'success' : 'retry';
            root.querySelector('[data-execution-label]').textContent = result.success ? 'Goal reached' : 'Needs changes';
            previewStatus.textContent = result.success ? 'The program reached the goal.' : 'Edit your program, then run again.';
            if (!showResult) {
                root.querySelector('[data-execution-label]').textContent = 'Preview';
                previewStatus.textContent = 'Preview of your commands. Run to check the goal.';
            }
            if (!result.success && showResult) revealGameFeedback(output);
            if (result.success && showResult && conceptCheck.verify()) {
                const canonical = program.map(command => normalizeArcadeCommand(game.template, command));
                const message = await saveCompletion(root, { program: canonical, assessment_answer: conceptCheck.answer() });
                if (message) output.textContent += `\n${message}`;
            } else if (result.success && showResult && game.assessment) {
                root.dataset.playState = 'checking';
                previewStatus.textContent = 'Program complete. Finish the concept check.';
            }
        } finally {
            busy = false; root.setAttribute('aria-busy', 'false');
            root.querySelectorAll('button, input, select, textarea').forEach(control => { control.disabled = false; });
            root.querySelector('[data-arcade-run]').disabled = lines().length === 0;
            syncOperand();
            root.querySelector('[data-arcade-undo]').disabled = !lines().length;
            root.querySelectorAll('[data-add-command]').forEach(control => { control.disabled = lines().length >= limit; });
            inspector.refresh();
            if (root.dataset.playState === 'checking') {
                const check = root.querySelector('[data-concept-check]');
                (check.querySelector('input:checked') ?? check.querySelector('input'))?.focus({ preventScroll: true });
            }
        }
    }
    root.querySelector('[data-arcade-run]').addEventListener('click', () => run(true));
    root.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && !busy && lines().length > 0) {
            event.preventDefault();
            run(true);
        }
    });
    root.querySelector('[data-arcade-undo]').addEventListener('click', () => {
        if (!busy) { const program = lines(); program.pop(); code.value = program.join('\n'); preview(); }
    });
    root.querySelector('[data-arcade-hint]').addEventListener('click', () => { output.textContent = game.hint; revealGameFeedback(output); });
    root.querySelector('[data-arcade-reset]').addEventListener('click', () => {
        if (!busy) { code.value = ''; history.length = 0; historyIndex = 0; if (terminalInput) terminalInput.value = ''; preview(); output.textContent = 'Ready when you are.'; }
    });
    inspector = mountLearningTrace(root, game, lines, program => arcadeTrace(game, program, evaluateArcade), render, () => busy);
    preview();
}
