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
    const fail = error => ({ state, output, success: false, error });
    if (game.version !== 1 || !validPuzzle(game.template, scenario) || !Array.isArray(program) || program.length < 1 || program.length > 12) return fail('Use between 1 and 12 instructions in a valid scenario.');
    for (const command of program) {
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

export function renderPuzzle(game, state, world, node, button, add) {
    world.classList.add('puzzle-world');
    if (game.template === 'logic-switchboard') {
        const inputs = node('div', '', 'switch-bank');
        state.inputs.forEach((value, index) => {
            const control = button('', `logic-switch ${value ? 'is-on' : ''}`, `Flip input ${index + 1}`, () => {
                add(`flip ${index + 1}`);
                world.querySelector(`[aria-label="Flip input ${index + 1}"]`)?.focus({ preventScroll: true });
            });
            control.setAttribute('aria-pressed', String(value)); control.append(node('span', `INPUT ${index + 1}`), node('b', value ? 'ON' : 'OFF')); inputs.append(control);
        });
        const on = state.inputs.filter(Boolean).length;
        const signal = game.scenario.gate === 'and' ? on === state.inputs.length : game.scenario.gate === 'or' ? on > 0 : on % 2 === 1;
        const gate = node('div', game.scenario.gate.toUpperCase(), 'logic-gate');
        const lamp = node('div', '', `signal-lamp ${signal ? 'is-on' : ''}`); lamp.append(node('small', 'OUTPUT'), node('b', signal ? 'TRUE' : 'FALSE'), node('small', `Target: ${game.scenario.target ? 'TRUE' : 'FALSE'}`)); world.append(inputs, gate, lamp);
    } else {
        for (const [label, value] of [['YOUR STATE', game.template === 'string-forge' ? state.text : state.stack], ['TARGET', game.scenario.target]]) {
            const panel = node('div', '', 'puzzle-state'); panel.append(node('h3', label));
            const strip = node('div', '', 'value-strip');
            const values = typeof value === 'string' ? [...value] : value;
            if (!values.length) strip.append(node('span', 'Empty stack', 'field-hint'));
            values.forEach(item => strip.append(node('span', item === ' ' ? '␠' : String(item), 'value-cell')));
            panel.append(strip); if (game.template === 'memory-trail') panel.append(node('small', 'Bottom → Top')); world.append(panel);
        }
    }
}
