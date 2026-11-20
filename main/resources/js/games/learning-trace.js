// Presentation over the same bounded interpreters used by Run. No source is executed here.
export function readableInstruction(template, command) {
    const [operation, ...args] = command.split(' ');
    const rest = args.join(' ');
    if (template === 'command-garden') return `kody.move_${command}()`;
    if (template === 'number-machine') return `value = value ${{ add: '+', subtract: '−', multiply: '×' }[operation] ?? '?'} ${rest}`;
    if (template === 'pixel-studio') return `canvas.paint(${args[0]}, ${args[1]}, ${JSON.stringify(args[2])})`;
    if (template === 'sort-lab') return `swap(items, ${args[0]}, ${args[1]})`;
    if (template === 'logic-switchboard') return `inputs[${rest}] = not inputs[${rest}]`;
    if (template === 'string-forge') return operation === 'append' ? `text = text + ${JSON.stringify(rest)}` : `text = ${operation}(text)`;
    if (template === 'memory-trail') return { push: `stack.push(${rest})`, pop: 'stack.pop()', duplicate: 'stack.duplicate_top()', swap: 'stack.swap_top()' }[operation] ?? command;
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
    const panel = document.createElement('details');
    panel.className = 'learning-trace';
    const heading = document.createElement('summary');
    heading.textContent = 'Step through program';
    panel.append(heading);
    const source = root.querySelector('[data-readable-program]');
    const state = document.createElement('pre'); state.className = 'trace-state'; state.tabIndex = 0; state.setAttribute('aria-label', 'Program state');
    const statePanel = document.createElement('details');
    const stateSummary = document.createElement('summary'); stateSummary.textContent = 'Program state'; statePanel.append(stateSummary, state);

    const status = document.createElement('p'); status.className = 'field-hint'; status.setAttribute('role', 'status'); status.tabIndex = 0;
    const actions = document.createElement('div'); actions.className = 'trace-actions';
    let trace, cursor = 0, replayToken = 0;
    const show = () => {
        const frame = trace.frames[cursor - 1]; const current = frame?.state ?? trace.initial;
        state.textContent = stateText(game, current);
        status.textContent = frame ? `Preview step ${cursor}/${trace.frames.length}. ${frame.explanation}${cursor === trace.frames.length ? ` ${trace.message}` : ''}` : trace.message;
        highlight(frame?.index);
        const label = root.querySelector('[data-execution-label]');
        if (label) label.textContent = frame ? `Preview step ${cursor}` : 'Not run';
        step.disabled = isBusy() || cursor >= trace.frames.length;
        render(current);
    };
    const highlight = index => {
        source.querySelectorAll('[data-instruction-index]').forEach(line => {
            if (Number(line.dataset.instructionIndex) === index) {
                line.setAttribute('aria-current', 'step');
                const container = source.getBoundingClientRect();
                const bounds = line.getBoundingClientRect();
                if (bounds.bottom > container.bottom) source.scrollTop += bounds.bottom - container.bottom;
                if (bounds.top < container.top) source.scrollTop -= container.top - bounds.top;
            } else line.removeAttribute('aria-current');
        });
    };
    const button = (text, action) => { const item = document.createElement('button'); item.type = 'button'; item.className = 'button button-ghost'; item.textContent = text; item.addEventListener('click', () => { if (!isBusy()) action(); }); actions.append(item); return item; };
    const step = button('Step', () => { replayToken++; cursor = Math.min(cursor + 1, trace.frames.length); show(); });
    const replay = button('Replay', async () => {
        const token = ++replayToken; cursor = 0; show();
        const reduced = document.defaultView.matchMedia('(prefers-reduced-motion: reduce)').matches;
        while (cursor < trace.frames.length && token === replayToken && !isBusy()) {
            if (!reduced) await new Promise(resolve => setTimeout(resolve, 500));
            if (token !== replayToken || isBusy() || !root.isConnected) break;
            cursor++; show();
        }
    });
    const restart = button('Restart', () => { replayToken++; cursor = 0; show(); });
    panel.append(actions, status, statePanel);
    const controls = root.querySelector('.game-controls');
    controls.querySelector('.game-extras').append(panel);
    const refresh = (paint = false) => {
        replayToken++;
        const program = getProgram(); trace = buildTrace(program); cursor = 0;
        highlight();
        state.textContent = `Initial state\n${stateText(game, trace.initial)}`;
        status.textContent = program.length ? trace.message : 'Add an instruction to begin.';
        step.disabled = !trace.frames.length || isBusy();
        replay.disabled = !trace.frames.length || isBusy();
        restart.disabled = !trace.frames.length || isBusy();
        if (paint) show();
    };
    // Do not replace the learner's program or initiate a network request.
    return { refresh, highlight, cancel: () => { replayToken++; } };
}
