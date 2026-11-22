// Read-only coaching over interpreter state. Completion remains owned by Run and the server.
export function missionProgress(game, state) {
    const scenario = game.scenario ?? {};
    switch (game.template) {
        case 'pixel-studio': {
            const target = Object.fromEntries(scenario.pixels.map(pixel => {
                const [x, y, color] = pixel.split(' '); return [`${x} ${y}`, color];
            }));
            const points = Array.from({ length: 9 }, (_, i) => `${i % 3 + 1} ${Math.floor(i / 3) + 1}`);
            const correct = points.filter(point => state.pixels[point] === target[point]).length;
            return { value: correct, max: 9, matched: correct === 9, text: `${correct} of 9 cells match, including blanks` };
        }
        case 'number-machine': {
            const distance = Math.abs(scenario.target - state.number);
            return { matched: distance === 0, text: `Distance to target: ${distance}` };
        }
        case 'sort-lab': {
            const target = [...scenario.items].sort((a, b) => a - b);
            const correct = state.items.filter((value, i) => value === target[i]).length;
            return { value: correct, max: target.length, matched: correct === target.length, text: `${correct} of ${target.length} values in their target position` };
        }
        case 'terminal-quest': return { matched: Boolean(state.verified), text: state.verified ? 'Destination copy verified.' : state.files[scenario.destination] === scenario.content
            ? `${scenario.destination} has the right contents. Verify it with cat.` : `Find the source, copy it to ${scenario.destination}, then verify it.` };
        case 'logic-switchboard': {
            const on = state.inputs.filter(Boolean).length;
            const signal = scenario.gate === 'and' ? on === state.inputs.length : scenario.gate === 'or' ? on > 0 : on % 2 === 1;
            return { matched: signal === Boolean(scenario.target), text: `${on} inputs TRUE · Output ${signal ? 'TRUE' : 'FALSE'} · Target ${scenario.target ? 'TRUE' : 'FALSE'}` };
        }
        case 'string-forge': {
            const correct = [...scenario.target].filter((letter, i) => letter === state.text[i]).length;
            const matched = state.text === scenario.target;
            return { value: correct, max: Math.max(state.text.length, scenario.target.length), matched, text: `${correct} matching characters · Length ${state.text.length} / target ${scenario.target.length}` };
        }
        case 'memory-trail': {
            if (!scenario.target.length && !state.stack.length) return { value: 1, max: 1, matched: true, text: 'Empty stack matches the target · 0 of 6 slots used' };
            const correct = scenario.target.filter((value, i) => value === state.stack[i]).length;
            const matched = state.stack.length === scenario.target.length && correct === scenario.target.length;
            return { value: correct, max: Math.max(1, state.stack.length, scenario.target.length), matched, text: `${correct} values match from the bottom · ${state.stack.length} of 6 slots used` };
        }
        default: return { text: '' };
    }
}

export function mountMission(root, game) {
    const doc = root.ownerDocument;
    const scene = root.querySelector('.arcade-scene, .game-world');
    const heading = scene.querySelector('.scene-heading');
    const status = doc.createElement('div'); status.className = 'mission-status';
    const label = doc.createElement('p'); label.dataset.missionProgress = '';
    const meter = doc.createElement('progress'); meter.setAttribute('aria-label', 'Preview matches the target');
    status.append(label, meter); heading.after(status);
    const flow = doc.createElement('ol'); flow.className = 'mission-flow'; flow.setAttribute('aria-label', 'Learning steps');
    for (const text of ['Build a program', 'Run and inspect', 'Explain the idea']) {
        const item = doc.createElement('li'); item.textContent = text; flow.append(item);
    }
    root.querySelector('.lesson-briefing').after(flow);
    const updatePhase = () => {
        const phase = ['checking', 'success'].includes(root.dataset.playState) ? 2
            : ['running', 'retry'].includes(root.dataset.playState) ? 1 : 0;
        [...flow.children].forEach((item, i) => {
            if (i === phase) item.setAttribute('aria-current', 'step'); else item.removeAttribute('aria-current');
        });
    };
    new MutationObserver(updatePhase).observe(root, { attributes: true, attributeFilter: ['data-play-state'] });
    updatePhase();
    return {
        update(state) {
            const progress = game.template === 'command-garden'
                ? { matched: state.position[0] === game.goal[0] && state.position[1] === game.goal[1] && state.crystals.length === 0, text: `Column ${state.position[0] + 1}, row ${state.position[1] + 1} · Flag ${game.goal[0] + 1}, ${game.goal[1] + 1} · ${state.crystals.length} crystals left` }
                : missionProgress(game, state);
            label.textContent = progress.text;
            status.dataset.targetMatched = String(Boolean(progress.matched));
            meter.hidden = progress.value === undefined;
            if (!meter.hidden) { meter.max = progress.max; meter.value = progress.value; }
        },
    };
}
