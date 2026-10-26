// Presentation over the same bounded interpreters used by Run. No source is executed here.
export function readableInstruction(template, command) {
    const [operation, ...args] = command.split(' ');
    const rest = args.join(' ');
    if (template === 'command-garden') return `MOVE ${command.toUpperCase()}`;
    if (template === 'number-machine') return `value = value ${{ add: '+', subtract: '−', multiply: '×' }[operation] ?? '?'} ${rest}`;
    if (template === 'pixel-studio') return `canvas[column ${args[0]}, row ${args[1]}] = ${args[2]}`;
    if (template === 'sort-lab') return `SWAP items[position ${args[0]}], items[position ${args[1]}]`;
    if (template === 'logic-switchboard') return `input[${rest}] = NOT input[${rest}]`;
    if (template === 'string-forge') return operation === 'append' ? `text = text + ${JSON.stringify(rest)}` : `text = ${operation}(text)`;
    if (template === 'memory-trail') return `${operation.toUpperCase()}${rest ? ` ${rest}` : ''} · top is on the right`;
    return command; // Terminal commands retain their real, limited simulator syntax.
}

export function arcadeTrace(game, program, evaluate) {
    const initial = evaluate(game, []).state;
    const frames = [];
    if (!Array.isArray(program) || program.length > 12) return { initial, frames, message: 'Use at most 12 instructions.' };
    for (let index = 0; index < program.length; index++) {
        const result = evaluate(game, program.slice(0, index + 1));
        frames.push({ state: result.state, index, explanation: result.error ?? result.output.at(-1) ?? 'State updated.' });
        if (result.error) break;
    }
    return { initial, frames, message: frames.length ? 'Preview only. Use Run to submit your program.' : 'Add an instruction to begin.' };
}

export function stateText(game, state) {
    if (game.template === 'command-garden') return `Position: column ${state.position[0] + 1}, row ${state.position[1] + 1}\nCrystals remaining: ${state.crystals.length}`;
    if (game.template === 'number-machine') return `value = ${state.number}`;
    if (game.template === 'sort-lab') return `items = [${state.items.join(', ')}]`;
    if (game.template === 'logic-switchboard') {
        const inputs = state.inputs.map(value => value ? 'TRUE' : 'FALSE');
        const signal = game.scenario.gate === 'and' ? state.inputs.every(Boolean) : game.scenario.gate === 'or' ? state.inputs.some(Boolean) : state.inputs.filter(Boolean).length % 2 === 1;
        return `${inputs.join(` ${game.scenario.gate.toUpperCase()} `)} = ${signal ? 'TRUE' : 'FALSE'}`;
    }
    if (game.template === 'string-forge') return `text = ${JSON.stringify(state.text)}`;
    if (game.template === 'memory-trail') return `stack = [${state.stack.join(', ')}]\nTop: ${state.stack.at(-1) ?? 'empty'} · size ${state.stack.length}/6`;
    if (game.template === 'pixel-studio') return Object.entries(state.pixels).map(([point, color]) => `(${point.replace(' ', ', ')}) = ${color}`).join('\n') || 'Canvas is blank.';
    return Object.entries(state.files).map(([name, content]) => `${name}: ${content}`).join('\n');
}

export function mountLearningTrace(root, game, getProgram, buildTrace, render, isBusy) {
    const document = root.ownerDocument;
    const panel = document.createElement('section'); panel.className = 'learning-trace'; panel.setAttribute('aria-label', 'Pseudocode');
    const heading = document.createElement('h3'); heading.textContent = 'Pseudocode'; panel.append(heading);
    const note = document.createElement('p'); note.className = 'field-hint'; note.textContent = 'Read your program. Step through it, or Run to check and save.';
    const source = document.createElement('ol'); source.className = 'trace-source'; source.setAttribute('aria-label', 'Readable program');
    const state = document.createElement('pre'); state.className = 'trace-state'; state.tabIndex = 0; state.setAttribute('aria-label', 'Program state');
    const statePanel = document.createElement('details');
    const stateSummary = document.createElement('summary'); stateSummary.textContent = 'Program state'; statePanel.append(stateSummary, state);
    source.tabIndex = 0;
    const status = document.createElement('p'); status.className = 'field-hint'; status.setAttribute('role', 'status');
    const actions = document.createElement('div'); actions.className = 'trace-actions';
    let trace, cursor = 0, replayToken = 0;
    const show = () => {
        const frame = trace.frames[cursor - 1]; const current = frame?.state ?? trace.initial;
        state.textContent = stateText(game, current);
        status.textContent = frame ? `Preview step ${cursor}/${trace.frames.length}. ${frame.explanation}${cursor === trace.frames.length ? ` ${trace.message}` : ''}` : trace.message;
        source.querySelectorAll('li:not(.trace-rule)').forEach((line, index) => { if (index === frame?.index) line.setAttribute('aria-current', 'step'); else line.removeAttribute('aria-current'); });
        step.disabled = isBusy() || cursor >= trace.frames.length;
        render(current);
    };
    const button = (text, action) => { const item = document.createElement('button'); item.type = 'button'; item.className = 'button button-ghost'; item.textContent = text; item.addEventListener('click', () => { if (!isBusy()) action(); }); actions.append(item); return item; };
    const step = button('Step preview', () => { replayToken++; cursor = Math.min(cursor + 1, trace.frames.length); show(); });
    button('Replay preview', async () => {
        const token = ++replayToken; cursor = 0; show();
        const reduced = document.defaultView.matchMedia('(prefers-reduced-motion: reduce)').matches;
        while (cursor < trace.frames.length && token === replayToken && !isBusy()) {
            if (!reduced) await new Promise(resolve => setTimeout(resolve, 500));
            if (token !== replayToken || isBusy() || !root.isConnected) break;
            cursor++; show();
        }
    });
    button('Restart preview', () => { replayToken++; cursor = 0; show(); });
    panel.append(note, source, actions, status, statePanel);
    const controls = root.querySelector('.game-controls');
    controls.insertBefore(panel, controls.querySelector('.game-toolbar'));
    const refresh = (paint = false) => {
        replayToken++;
        const program = getProgram(); trace = buildTrace(program); cursor = 0;
        source.replaceChildren();
        if (game.template === 'command-garden' && game.mode === 'loop') { const line = document.createElement('li'); line.textContent = root.querySelector('[data-game-repeat]')?.checked ? 'REPEAT the following body 2 TIMES' : 'Repeat is off; this level requires it.'; line.className = 'trace-rule'; line.value = 0; source.append(line); }
        program.slice(0, 12).forEach(command => { const line = document.createElement('li'); line.textContent = readableInstruction(game.template, command); if (game.template === 'command-garden' && game.mode === 'conditional' && root.querySelector('[data-game-conditional]')?.checked) line.textContent += '; IF crystal here THEN COLLECT'; source.append(line); });
        state.textContent = `Initial state\n${stateText(game, trace.initial)}`; status.textContent = trace.message; step.disabled = !trace.frames.length || isBusy();
        if (paint) show();
    };
    // Do not replace the learner's program or initiate a network request.
    return { refresh, cancel: () => { replayToken++; } };
}
