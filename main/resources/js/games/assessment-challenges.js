export function programRuleError(game, program) {
    const rules = game.assessment;
    if (!rules) return null;
    if (rules.version !== 1 || !Number.isInteger(rules.limit) || rules.limit < 1 || rules.limit > 12
        || !Array.isArray(rules.commands)) return 'This assessment is unavailable.';
    if (program.length > rules.limit) return `Use at most ${rules.limit} instructions. Remove a step and try again.`;
    if (rules.commands.length && program.some(command => !rules.commands.includes(command))) return 'Use only the instructions provided for this task.';
    return null;
}

export function conceptAnswerCorrect(game, answer) {
    return !game.assessment || answer === game.assessment.check.answer;
}

export function mountConceptCheck(root, game) {
    const host = root.querySelector('[data-concept-check]');
    if (!game.assessment || !host) return { verify: () => true, answer: () => undefined, reset: () => {} };
    const doc = root.ownerDocument;
    const check = game.assessment.check;
    const group = doc.createElement('fieldset');
    const legend = doc.createElement('legend'); legend.textContent = check.question; group.append(legend);
    const name = `concept-${globalThis.crypto.randomUUID()}`;
    Object.entries(check.options).forEach(([value, text]) => {
        const label = doc.createElement('label');
        const input = doc.createElement('input'); input.type = 'radio'; input.name = name; input.value = value;
        label.append(input, doc.createTextNode(text)); group.append(label);
    });
    const note = doc.createElement('p'); note.setAttribute('role', 'status');
    host.append(group, note); host.hidden = true;
    const run = root.querySelector('[data-game-run], [data-arcade-run]');
    const originalLabel = run.textContent.trim();
    const answer = () => group.querySelector('input:checked')?.value;
    return {
        answer,
        verify() {
            host.hidden = false;
            if (conceptAnswerCorrect(game, answer())) { note.textContent = check.explanation; return true; }
            note.textContent = answer() ? `Try again. ${check.explanation}` : 'Your program works. Answer this concept check, then check your answer.';
            run.textContent = 'Check answer'; run.setAttribute('aria-label', 'Check answer');
            group.querySelector('input')?.focus({ preventScroll: true });
            return false;
        },
        reset() { host.hidden = true; note.textContent = ''; run.textContent = originalLabel; run.setAttribute('aria-label', originalLabel); },
    };
}

export function objectiveFeedback(game, result) {
    if (result.error) return result.error;
    const { state } = result;
    const target = game.scenario.target;
    switch (game.template) {
        case 'number-machine': return `Your value is ${state.number}; the target is ${target}. Check the order of your operations.`;
        case 'sort-lab': {
            const index = state.items.findIndex((value, i) => i > 0 && state.items[i - 1] > value);
            return `Positions ${index} and ${index + 1} are still out of order. Compare their values before swapping.`;
        }
        case 'pixel-studio': {
            const expected = Object.fromEntries(game.scenario.pixels.map(pixel => { const [x, y, color] = pixel.split(' '); return [`${x} ${y}`, color]; }));
            const point = [...new Set([...Object.keys(expected), ...Object.keys(state.pixels)])].find(point => expected[point] !== state.pixels[point]);
            return `Check column ${point?.split(' ')[0]}, row ${point?.split(' ')[1]}. It should be ${expected[point] ?? 'blank'}.`;
        }
        case 'terminal-quest': return state.files[game.scenario.destination] === game.scenario.content
            ? `The copy is correct. Read ${game.scenario.destination} with cat to verify it.`
            : 'Inspect the source contents, copy the matching file to the destination, then read that destination.';
        case 'logic-switchboard': return `${game.scenario.gate.toUpperCase()} needs ${game.scenario.gate === 'and' ? 'every input TRUE' : game.scenario.gate === 'or' ? 'at least one TRUE input' : 'an odd number of TRUE inputs'} for TRUE. Compare this with the target.`;
        case 'string-forge': return `Your text is ${JSON.stringify(state.text)}. Check spaces, letter case and operation order against the target.`;
        case 'memory-trail': return `Your stack is [${state.stack.join(', ')}]. Only the rightmost value is TOP; check which value each operation changes.`;
        default: return 'The objective is not complete. Review your program and try again.';
    }
}
