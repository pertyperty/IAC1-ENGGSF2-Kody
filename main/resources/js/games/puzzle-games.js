import { buildTruthTable } from './visual-aids.js';

export const puzzleTemplates = ['logic-switchboard', 'string-forge', 'memory-trail'];

export function validPuzzle(template, scenario) {
    if (!scenario || typeof scenario !== 'object' || Array.isArray(scenario)) return false;
    const keys = template === 'logic-switchboard' ? ['inputs', 'gate', 'target'] : ['start', 'target'];
    if (Object.keys(scenario).length !== keys.length || keys.some(key => !Object.hasOwn(scenario, key))) return false;
    if (template === 'logic-switchboard') return Array.isArray(scenario.inputs) && scenario.inputs.length >= 2 && scenario.inputs.length <= 4
        && scenario.inputs.every(value => typeof value === 'boolean') && ['and', 'or', 'xor'].includes(scenario.gate) && typeof scenario.target === 'boolean';
    if (template === 'string-forge') return [scenario.start, scenario.target].every(value => typeof value === 'string' && /^[A-Za-z0-9 _-]{1,40}$/.test(value) && value.trim());
    if (template === 'memory-trail') return [scenario.start, scenario.target].every(values => Array.isArray(values) && values.length <= 6 && values.every(value => Number.isInteger(value) && value >= 0 && value <= 9));
    return false;
}

export function evaluatePuzzle(game, program) {
    const scenario = game.scenario;
    const state = { text: scenario.start, stack: Array.isArray(scenario.start) ? [...scenario.start] : [], inputs: [...(scenario.inputs ?? [])] };
    const output = [];
    let instructionIndex = -1;
    const fail = error => ({ state, output, success: false, error, failedStep: instructionIndex });
    if (game.version !== 1 || !validPuzzle(game.template, scenario) || !Array.isArray(program) || program.length < 1 || program.length > 12) return fail('Use between 1 and 12 instructions in a valid scenario.');
    for (const command of program) {
        instructionIndex++;
        if (typeof command !== 'string' || command.length > 100 || command.trim() !== command) return fail('Check the instruction format.');
        let match;
        if (game.template === 'logic-switchboard') {
            match = /^flip ([1-4])$/.exec(command);
            if (!match || Number(match[1]) > state.inputs.length) return fail('Flip an existing input switch, numbered from 1.');
            state.inputs[match[1] - 1] = !state.inputs[match[1] - 1];
            output.push(`${command} → ${state.inputs.map(value => value ? 'ON' : 'OFF').join(' · ')}`);
        } else if (game.template === 'string-forge') {
            if (command === 'upper') state.text = state.text.toUpperCase();
            else if (command === 'lower') state.text = state.text.toLowerCase();
            else if (command === 'reverse') state.text = [...state.text].reverse().join('');
            else if (command === 'trim') state.text = state.text.trim();
            else if ((match = /^append ([A-Za-z0-9_-]{1,12})$/.exec(command))) state.text += match[1];
            else return fail('Use upper, lower, reverse, trim or append letters/digits (up to 12).');
            if (state.text.length > 100) return fail('Keep the text within 100 characters.');
            output.push(`${command} → ${JSON.stringify(state.text)}`);
        } else {
            if ((match = /^push ([0-9])$/.exec(command)) && state.stack.length < 6) state.stack.push(Number(match[1]));
            else if (command === 'pop' && state.stack.length) state.stack.pop();
            else if (command === 'duplicate' && state.stack.length && state.stack.length < 6) state.stack.push(state.stack.at(-1));
            else if (command === 'swap' && state.stack.length >= 2) { const last = state.stack.length - 1; [state.stack[last], state.stack[last - 1]] = [state.stack[last - 1], state.stack[last]]; }
            else return fail('Use push 0–9, pop, duplicate or swap. A stack holds up to six values; pop needs a value and swap needs two.');
            output.push(`${command} → [${state.stack.join(', ')}]`);
        }
    }
    const on = state.inputs.filter(Boolean).length;
    state.signal = scenario.gate === 'and' ? on === state.inputs.length : scenario.gate === 'or' ? on > 0 : on % 2 === 1;
    const success = game.template === 'logic-switchboard' ? state.signal === scenario.target : game.template === 'string-forge' ? state.text === scenario.target : JSON.stringify(state.stack) === JSON.stringify(scenario.target);
    return { state, output, success, error: null };
}

function buildGateSvg(doc, gate, active) {
    const svgNS = 'http://www.w3.org/2000/svg';
    const svg = doc.createElementNS(svgNS, 'svg');
    svg.setAttribute('viewBox', '0 0 80 56');
    svg.setAttribute('class', `gate-symbol${active ? ' is-lit' : ''}`);
    svg.setAttribute('aria-hidden', 'true');
    const paths = {
        and: ['M 14 8 H 40 A 20 20 0 0 1 40 48 H 14 Z'],
        or: ['M 12 8 Q 24 28 12 48 H 36 Q 58 48 68 28 Q 58 8 36 8 Z'],
        xor: ['M 6 8 Q 18 28 6 48', 'M 15 8 Q 27 28 15 48 H 38 Q 58 48 68 28 Q 58 8 38 8 Z'],
    }[gate] ?? ['M 14 8 H 40 A 20 20 0 0 1 40 48 H 14 Z'];
    for (const d of paths) {
        const p = doc.createElementNS(svgNS, 'path');
        p.setAttribute('d', d);
        svg.append(p);
    }
    return svg;
}

function buildWireHarness(doc, inputs, signal) {
    const svgNS = 'http://www.w3.org/2000/svg';
    const count = inputs.length;
    const height = Math.max(88, count * 34);
    const svg = doc.createElementNS(svgNS, 'svg');
    svg.setAttribute('viewBox', `0 0 72 ${height}`);
    svg.setAttribute('class', 'circuit-wires');
    svg.setAttribute('aria-hidden', 'true');
    const midY = height / 2;
    inputs.forEach((isOn, idx) => {
        const y = ((idx + 0.5) / count) * height;
        const path = doc.createElementNS(svgNS, 'path');
        path.setAttribute('d', `M 0 ${y} C 36 ${y}, 36 ${midY}, 72 ${midY}`);
        path.setAttribute('class', `circuit-wire${isOn ? ' is-on' : ''}`);
        svg.append(path);
    });
    return svg;
}

export function renderPuzzle(game, state, world, node, button, add, steps = []) {
    const doc = world.ownerDocument;
    world.classList.add('puzzle-world');
    world.classList.toggle('stack-world', game.template === 'memory-trail');
    world.classList.toggle('circuit-world', game.template === 'logic-switchboard');
    world.classList.toggle('string-world', game.template === 'string-forge');
    if (game.template === 'logic-switchboard') {
        const stage = node('div', '', 'circuit-stage');
        const inputs = node('div', '', 'switch-bank');
        state.inputs.forEach((value, index) => {
            const control = button('', `logic-switch ${value ? 'is-on' : ''}`, `Flip input ${index + 1}`, () => {
                add(`flip ${index + 1}`);
                world.querySelector(`[aria-label="Flip input ${index + 1}"]`)?.focus({ preventScroll: true });
            });
            control.setAttribute('aria-pressed', String(value));
            const led = node('i', '', 'switch-led'); led.setAttribute('aria-hidden', 'true');
            const rocker = node('span', value ? '1 · ON' : '0 · OFF', 'switch-rocker');
            control.append(led, node('span', `INPUT ${index + 1}`, 'switch-title'), rocker);
            inputs.append(control);
        });
        const on = state.inputs.filter(Boolean).length;
        const signal = game.scenario.gate === 'and' ? on === state.inputs.length : game.scenario.gate === 'or' ? on > 0 : on % 2 === 1;
        const wireIn = buildWireHarness(doc, state.inputs, signal);
        const gate = node('div', '', `logic-gate${signal ? ' is-on' : ''}`);
        gate.append(buildGateSvg(doc, game.scenario.gate, signal), node('b', game.scenario.gate.toUpperCase()), node('small', { and: 'All TRUE', or: 'Any TRUE', xor: 'Odd TRUE' }[game.scenario.gate]));
        const wireOut = node('span', '', `circuit-output-wire${signal ? ' is-on' : ''}`);
        wireOut.setAttribute('aria-hidden', 'true');
        const matched = signal === Boolean(game.scenario.target);
        const lamp = node('div', '', `signal-lamp ${signal ? 'is-on' : ''} ${matched ? 'is-matched' : ''}`);
        const bulb = node('i', '', 'lamp-bulb'); bulb.setAttribute('aria-hidden', 'true');
        lamp.append(bulb, node('small', 'OUTPUT'), node('b', signal ? 'TRUE' : 'FALSE'), node('small', `Target: ${game.scenario.target ? 'TRUE' : 'FALSE'}`, 'lamp-target'));
        stage.append(inputs, wireIn, gate, wireOut, lamp);
        world.append(stage);

        const expression = node('p', `${state.inputs.map(value => value ? 'TRUE' : 'FALSE').join(` ${game.scenario.gate.toUpperCase()} `)} = ${signal ? 'TRUE' : 'FALSE'}`, 'boolean-expression');
        expression.setAttribute('aria-label', 'Boolean expression');
        world.append(expression);

        const tableData = buildTruthTable(game.scenario.gate, state.inputs);
        const tableWrap = node('div', '', 'truth-table-card');
        const table = node('table', '', 'truth-table');
        const caption = node('caption', `${game.scenario.gate.toUpperCase()} gate truth rule`);
        const thead = doc.createElement('thead');
        const headRow = doc.createElement('tr');
        for (const col of tableData.headers) headRow.append(node('th', col));
        thead.append(headRow);
        const tbody = doc.createElement('tbody');
        for (const row of tableData.rows) {
            const tr = doc.createElement('tr');
            if (row.active) tr.className = 'is-active-row';
            for (const cell of row.cells) tr.append(node('td', cell));
            const outTd = node('td', row.output ? 'TRUE' : 'FALSE', row.output ? 'truth-true' : 'truth-false');
            tr.append(outTd);
            tbody.append(tr);
        }
        table.append(caption, thead, tbody);
        tableWrap.append(table);
        world.append(tableWrap);
    } else if (game.template === 'string-forge') {
        const targetChars = [...game.scenario.target];
        for (const [label, value] of [['Your state', state.text], ['Target', game.scenario.target]]) {
            const panel = node('div', '', 'puzzle-state string-tape-panel');
            const head = node('div', '', 'tape-heading');
            head.append(node('h3', label), node('small', `${[...value].length} chars`, 'tape-length'));
            panel.append(head);
            const strip = node('div', '', 'value-strip string-tape');
            const values = [...value];
            if (!values.length) strip.append(node('span', 'Empty text', 'field-hint'));
            values.forEach((item, index) => {
                const isSpace = item === ' ';
                const slot = node('div', '', 'tape-slot');
                const cell = node('span', isSpace ? '␣' : String(item), `value-cell${isSpace ? ' space-cell' : ''}`);
                cell.setAttribute('aria-label', `Character ${index + 1}: ${isSpace ? 'space' : item}`);
                if (label === 'Your state') cell.dataset.match = item === targetChars[index] ? 'same' : 'different';
                const idxTag = node('small', String(index), 'char-index');
                idxTag.setAttribute('aria-hidden', 'true');
                slot.append(cell, idxTag);
                strip.append(slot);
            });
            panel.append(strip);
            world.append(panel);
        }
        if (steps.length) {
            const pipeline = node('ol', '', 'string-pipeline');
            pipeline.setAttribute('aria-label', 'String transformation pipeline');
            const startChip = node('li', '', 'pipeline-chip pipeline-start');
            startChip.append(node('small', 'start'), node('code', JSON.stringify(game.scenario.start)));
            pipeline.append(startChip);
            for (const step of steps) {
                const chip = node('li', '', step.error ? 'pipeline-chip pipeline-error' : 'pipeline-chip');
                chip.append(node('small', step.label), node('code', step.error ? 'error' : JSON.stringify(step.text)));
                pipeline.append(chip);
            }
            world.append(pipeline);
        }
    } else {
        const capacity = 6;
        for (const [label, value] of [['Your state', state.stack], ['Target', game.scenario.target]]) {
            const panel = node('div', '', 'puzzle-state stack-vessel-panel');
            const head = node('div', '', 'stack-vessel-head');
            head.append(node('h3', label), node('small', `${value.length}/${capacity}`, 'stack-cap-pill'));
            panel.append(head);
            const vessel = node('div', '', 'stack-vessel');
            const strip = node('div', '', 'value-strip stack-column');
            if (!value.length) strip.append(node('span', 'Empty stack', 'field-hint stack-empty-label'));
            value.forEach((item, index) => {
                const cell = node('span', '', 'value-cell stack-item-cell');
                const slotIdx = node('em', String(index), 'stack-slot-idx');
                slotIdx.setAttribute('aria-hidden', 'true');
                const valText = node('b', String(item));
                cell.append(slotIdx, valText);
                cell.setAttribute('aria-label', `Slot ${index + 1}: ${item}`);
                if (label === 'Your state') cell.dataset.match = item === game.scenario.target[index] ? 'same' : 'different';
                if (index === value.length - 1) {
                    cell.classList.add('stack-top');
                    cell.append(node('small', 'TOP', 'top-pointer-tag'));
                }
                strip.append(cell);
            });
            for (let empty = value.length; empty < capacity; empty++) {
                const ghost = node('span', '', 'stack-empty-slot');
                ghost.setAttribute('aria-hidden', 'true');
                strip.append(ghost);
            }
            vessel.append(strip, node('p', 'BOTTOM', 'stack-base'));
            panel.append(vessel);
            world.append(panel);
        }
    }
}
