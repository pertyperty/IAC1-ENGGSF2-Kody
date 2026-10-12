import { createIcon } from '../icons.js';

// Visual authoring writes the same bounded scenario data as the advanced fields.
export function scenarioCanvas(doc, template, scenario, commit) {
    scenario = structuredClone(scenario);
    const root = doc.createElement('div'); root.className = `scenario-canvas scenario-canvas-${template}`;
    root.setAttribute('aria-label', 'Visual scenario designer');
    const node = (tag, text = '', className = '') => {
        const element = doc.createElement(tag); element.textContent = text; element.className = className; return element;
    };
    const change = (action, mutate, redraw = true) => {
        const scope = root.parentElement;
        const next = structuredClone(scenario); mutate(next); Object.assign(scenario, next); commit(next, redraw);
        if (redraw) (scope?.querySelector(`[data-design-action="${action}"]`) ?? scope?.querySelector('button:not(:disabled)'))?.focus({ preventScroll: true });
    };
    const button = (text, action, mutate, icon = null) => {
        const element = node('button', text, 'button button-secondary button-small'); element.type = 'button';
        element.dataset.designAction = action;
        if (icon) { element.prepend(createIcon(doc, icon)); }
        element.addEventListener('click', () => change(action, mutate)); return element;
    };
    const heading = text => root.append(node('h4', text));
    if (template === 'pixel-studio') {
        heading('Paint the target picture');
        const palette = node('div', '', 'designer-tools'); let color = 'mint';
        for (const value of ['mint', 'peach', 'lavender', 'erase']) {
            const brush = node('button', value, `designer-brush pixel-${value}`); brush.type = 'button'; brush.setAttribute('aria-pressed', String(value === color));
            brush.addEventListener('click', () => { color = value; [...palette.children].forEach(item => item.setAttribute('aria-pressed', String(item === brush))); }); palette.append(brush);
        }
        const grid = node('div', '', 'designer-pixel-grid');
        for (let y = 1; y <= 3; y++) for (let x = 1; x <= 3; x++) {
            const pixel = scenario.pixels.find(value => value.startsWith(`${x} ${y} `))?.split(' ')[2] ?? 'blank';
            const cell = node('button', `${x},${y}`, `pixel-tile pixel-${pixel}`); cell.type = 'button'; cell.dataset.designAction = `pixel-${x}-${y}`;
            cell.setAttribute('aria-label', `Target column ${x}, row ${y}: ${pixel}`);
            cell.addEventListener('click', () => {
                const next = structuredClone(scenario); next.pixels = next.pixels.filter(value => !value.startsWith(`${x} ${y} `));
                if (color !== 'erase') next.pixels.push(`${x} ${y} ${color}`);
                // Keep a nonempty objective; an empty canvas is not a playable scenario.
                if (next.pixels.length) {
                    const scope = root.parentElement; commit(next);
                    const nextRoot = scope?.querySelector('.scenario-canvas-pixel-studio');
                    const nextBrush = [...(nextRoot?.querySelectorAll('.designer-brush') ?? [])].find(item => item.textContent === color);
                    nextBrush?.click(); scope?.querySelector(`[data-design-action="pixel-${x}-${y}"]`)?.focus({ preventScroll: true });
                }
            }); grid.append(cell);
        }
        root.append(palette, grid, node('p', 'Choose a color, then select cells. Erase removes a target cell.', 'field-hint'));
    } else if (template === 'logic-switchboard') {
        heading('Set the starting switches'); const bank = node('div', '', 'designer-tools');
        scenario.inputs.forEach((value, index) => {
            const control = button(`Input ${index + 1}: ${value ? 'ON' : 'OFF'}`, `switch-${index}`, next => next.inputs[index] = !next.inputs[index]);
            control.setAttribute('aria-pressed', String(value)); bank.append(control);
        });
        const add = button('Add input', 'switch-add', next => next.inputs.push(false), 'plus'); add.disabled = scenario.inputs.length >= 4;
        const remove = button('Remove last input', 'switch-remove', next => next.inputs.pop(), 'close'); remove.disabled = scenario.inputs.length <= 2;
        const gates = node('div', '', 'designer-tools');
        for (const gate of ['and', 'or', 'xor']) {
            const control = button(gate.toUpperCase(), `gate-${gate}`, next => next.gate = gate); control.setAttribute('aria-pressed', String(gate === scenario.gate)); gates.append(control);
        }
        root.append(bank, add, remove, gates, button(`Goal: ${scenario.target ? 'TRUE' : 'FALSE'}`, 'switch-goal', next => next.target = !next.target, 'target'));
    } else if (template === 'sort-lab') {
        heading('Arrange the starting list'); const bank = node('div', '', 'designer-list');
        scenario.items.forEach((value, index) => {
            const tile = node('div', '', 'designer-value'); const input = node('input'); input.type = 'number'; input.min = '0'; input.max = '99'; input.value = value;
            input.setAttribute('aria-label', `Value at position ${index + 1}`);
            input.addEventListener('input', () => change(`sort-value-${index}`, next => next.items[index] = input.value === '' ? null : Number(input.value), false)); input.dataset.designAction = `sort-value-${index}`;
            const earlier = button('', `sort-left-${index}`, next => [next.items[index - 1], next.items[index]] = [next.items[index], next.items[index - 1]], 'left'); earlier.disabled = index === 0; earlier.setAttribute('aria-label', `Move value ${index + 1} earlier`);
            const later = button('', `sort-right-${index}`, next => [next.items[index + 1], next.items[index]] = [next.items[index], next.items[index + 1]], 'right'); later.disabled = index === scenario.items.length - 1; later.setAttribute('aria-label', `Move value ${index + 1} later`);
            const remove = button('', `sort-remove-${index}`, next => next.items.splice(index, 1), 'close'); remove.disabled = scenario.items.length <= 2; remove.setAttribute('aria-label', `Remove value ${index + 1}`);
            tile.append(input, earlier, later, remove); bank.append(tile);
        });
        const add = button('Add value', 'sort-add', next => next.items.push(0), 'plus'); add.disabled = scenario.items.length >= 6; root.append(bank, add);
    } else if (template === 'memory-trail') {
        heading('Build the starting and target stacks'); let selected = 0;
        const palette = node('div', '', 'designer-tools');
        for (let value = 0; value <= 9; value++) {
            const pick = node('button', String(value), 'button button-secondary button-small'); pick.type = 'button'; pick.setAttribute('aria-pressed', String(value === selected));
            pick.addEventListener('click', () => { selected = value; [...palette.children].forEach(item => item.setAttribute('aria-pressed', String(item === pick))); }); palette.append(pick);
        }
        root.append(palette);
        for (const key of ['start', 'target']) {
            const bank = node('div', '', 'designer-stack'); bank.append(node('b', key === 'start' ? 'Starting stack' : 'Target stack'));
            const values = node('div', '', 'value-strip'); scenario[key].forEach(value => values.append(node('span', String(value), 'value-chip')));
            const push = node('button', 'Push selected value', 'button button-secondary button-small'); push.type = 'button'; push.disabled = scenario[key].length >= 6; push.dataset.designAction = `stack-push-${key}`;
            push.addEventListener('click', () => {
                const scope = root.parentElement; const next = structuredClone(scenario); next[key].push(selected); commit(next);
                const nextPalette = scope?.querySelector('.scenario-canvas-memory-trail .designer-tools'); nextPalette?.children[selected]?.click();
                scope?.querySelector(`[data-design-action="stack-push-${key}"]`)?.focus({ preventScroll: true });
            });
            const pop = button('Pop top', `stack-pop-${key}`, next => next[key].pop(), 'up'); pop.disabled = scenario[key].length === 0;
            bank.append(values, push, pop); root.append(bank);
        }
        root.append(node('p', 'Choose a value, then push it onto either stack. The rightmost value is the top.', 'field-hint'));
    } else if (template === 'number-machine') {
        heading('Drag to set the numbers');
        for (const key of ['start', 'target']) {
            const label = node('label', key === 'start' ? 'Starting number' : 'Target number'); const value = node('output', String(scenario[key]));
            const slider = node('input'); slider.type = 'range'; slider.min = '-100'; slider.max = '100'; slider.step = '1'; slider.value = scenario[key];
            slider.addEventListener('input', () => { value.textContent = slider.value; change(`number-${key}`, next => next[key] = Number(slider.value), false); }); slider.dataset.designAction = `number-${key}`;
            label.append(value, slider); root.append(label);
        }
    } else if (template === 'string-forge') {
        heading('Build the target transformation');
        const label = node('label', 'Starting text'); const input = node('input'); input.value = scenario.start; input.maxLength = 40; input.dataset.designAction = 'string-start';
        input.addEventListener('input', () => change('string-start', next => next.start = input.value, false)); label.append(input);
        root.append(label, node('div', scenario.target, 'designer-string designer-string-target'));
        const tools = node('div', '', 'designer-tools');
        for (const [label, transform] of [['UPPERCASE', value => value.toUpperCase()], ['lowercase', value => value.toLowerCase()], ['Reverse', value => [...value].reverse().join('')], ['Trim spaces', value => value.trim()]]) {
            tools.append(button(label, `string-${label.replaceAll(' ', '-')}`, next => next.target = transform(next.target)));
        }
        tools.append(button('Start again', 'string-reset', next => next.target = next.start, 'refresh'));
        const appendLabel = node('label', 'Text to append'); const append = node('input'); append.maxLength = 40; appendLabel.append(append);
        const add = button('Append to target', 'string-append', next => next.target += append.value, 'plus'); root.append(tools, appendLabel, add);
    } else if (template === 'terminal-quest') {
        heading('Build the virtual filesystem');
        for (const [index, file] of scenario.files.entries()) {
            const card = node('div', '', 'designer-file'); const name = node('input'); name.value = file.name; name.maxLength = 26; name.setAttribute('aria-label', `File ${index + 1} name`);
            const content = node('textarea'); content.value = file.content; content.maxLength = 300; content.rows = 2; content.setAttribute('aria-label', `File ${index + 1} contents`);
            name.addEventListener('input', () => change(`file-name-${index}`, next => next.files[index].name = name.value, false)); name.dataset.designAction = `file-name-${index}`;
            content.addEventListener('input', () => change(`file-content-${index}`, next => next.files[index].content = content.value, false)); content.dataset.designAction = `file-content-${index}`;
            const target = button('Use contents as goal', `file-target-${index}`, next => next.content = next.files[index].content, 'target');
            const remove = button('Remove file', `file-remove-${index}`, next => next.files.splice(index, 1), 'close'); remove.disabled = scenario.files.length <= 1;
            card.append(name, content, target, remove); root.append(card);
        }
        const add = button('Add file', 'file-add', next => {
            let index = 1; while (next.files.some(file => file.name === `file${index}.txt`)) index++;
            next.files.push({ name: `file${index}.txt`, content: 'Hello Kody!' });
        }, 'plus'); add.disabled = scenario.files.length >= 8;
        const label = node('label', 'Goal destination filename'); const destination = node('input'); destination.value = scenario.destination; destination.maxLength = 26;
        destination.dataset.designAction = 'file-destination'; destination.addEventListener('input', () => change('file-destination', next => next.destination = destination.value, false)); label.append(destination);
        root.append(add, label, node('p', `Goal contents: ${scenario.content}`, 'field-hint'));
    }
    return root;
}
