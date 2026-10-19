import { setIconLabel } from './icons.js';
import { mountGarden, validateInstance } from './games/command-garden.js';
import { mountArcade, validScenario } from './games/arcade-games.js';
import { mountQuiz, validateQuiz } from './games/choice-quiz.js';
import { renderQuizForm } from './games/quiz-types.js';
import { gardenLayout, paintGarden } from './games/garden-designer.js';
import { scenarioCanvas } from './games/scenario-canvas.js';

// Only typed data reaches the existing interpreters. Draft previews have no save endpoint.
export function editTowerStage(root, stage, index, changed, previewHost) {
    const doc = root.ownerDocument;
    const field = (parent, label, value, update, { area = false, max = 1000 } = {}) => {
        const wrapper = doc.createElement('label'); wrapper.textContent = label;
        const input = doc.createElement(area ? 'textarea' : 'input');
        input.value = value ?? ''; input.maxLength = max;
        if (area) input.rows = 3;
        input.addEventListener('input', () => { update(input.value); changed(); previewHost.replaceChildren(); });
        wrapper.append(input); parent.append(wrapper); return input;
    };
    const details = doc.createElement('details'); details.className = 'tower-stage-fields';
    const heading = doc.createElement('summary'); heading.textContent = `Customize stage ${index + 1}`;
    details.append(heading); root.append(details);
    field(details, 'Stage title', stage.title, value => stage.title = value, { max: 100 });
    if (stage.template === 'choice-quiz') {
        const teaching = doc.createElement('div'); details.append(teaching);
        const renderTeaching = () => {
            teaching.replaceChildren();
            const toggle = doc.createElement('button'); toggle.type = 'button'; toggle.className = 'button button-secondary button-small';
            toggle.textContent = stage.briefing ? 'Remove custom teaching notes' : 'Add teaching notes';
            toggle.addEventListener('click', () => {
                if (stage.briefing) delete stage.briefing;
                else stage.briefing = { outcome: '', explanation: '', example: '', task: '', success: '' };
                changed(); previewHost.replaceChildren(); renderTeaching();
                teaching.querySelector('input, textarea, button')?.focus();
            });
            if (stage.briefing) {
                const group = doc.createElement('fieldset');
                const legend = doc.createElement('legend'); legend.textContent = 'Teaching notes'; group.append(legend);
                for (const [key, label] of Object.entries({ outcome: 'Stage learning objective', explanation: 'Explain the rule', example: 'Different worked example', task: 'Learner task', success: 'Success criteria' })) {
                    field(group, label, stage.briefing[key], value => stage.briefing[key] = value, { area: true });
                }
                teaching.append(group);
            }
            teaching.append(toggle);
        };
        renderTeaching();
        const questions = stage.version !== 1 ? stage.questions : [stage];
        if (Array.isArray(questions)) questions.forEach((question, position) => {
            const group = doc.createElement('fieldset');
            const legend = doc.createElement('legend'); legend.textContent = `Question ${position + 1}`; group.append(legend);
            field(group, 'Question', question.question, value => question.question = value, { area: true, max: 500 });
            if (question.type === 'blank') {
                field(group, 'Accepted answers · one per line', question.accepted.join('\n'), value => question.accepted = value.split('\n'), { area: true, max: 605 });
                const label = doc.createElement('label'), checkbox = doc.createElement('input'); checkbox.type = 'checkbox'; checkbox.checked = question.case_sensitive;
                checkbox.addEventListener('change', () => { question.case_sensitive = checkbox.checked; changed(); previewHost.replaceChildren(); }); label.append(checkbox, doc.createTextNode(' Letter case must match')); group.append(label);
            } else if (question.type === 'matching') {
                question.pairs.forEach((pair, index) => { field(group, `Pair ${index + 1} prompt`, pair.left, value => pair.left = value, { max: 300 }); field(group, `Pair ${index + 1} match`, pair.right, value => pair.right = value, { max: 300 }); });
            } else {
            (question.options ?? []).forEach((option, choice) => field(group, `Choice ${choice + 1}`, option.label, value => option.label = value, { max: 300 }));
            const answerLabel = doc.createElement('label'); answerLabel.textContent = 'Correct choice';
            const select = doc.createElement('select');
            (question.options ?? []).forEach((option, choice) => { const item = doc.createElement('option'); item.value = option.id; item.textContent = `Choice ${choice + 1}`; select.append(item); });
            select.value = question.answer;
            select.addEventListener('change', () => { question.answer = select.value; changed(); previewHost.replaceChildren(); });
            answerLabel.append(select); group.append(answerLabel);
            }
            field(group, 'Explanation', question.explanation, value => question.explanation = value, { area: true }); details.append(group);
        });
    } else {
        for (const [key, label] of Object.entries({ concept: 'Topic', instructions: 'Mission instructions', hint: 'Hint', learningIdea: 'What the learner discovers' })) {
            field(details, label, stage[key], value => stage[key] = value, { area: key !== 'concept' });
        }
        if (stage.template === 'command-garden') {
            const tools = doc.createElement('div'); tools.className = 'tower-board-tools';
            let tool = 'path';
            for (const type of ['path', 'start', 'goal', 'crystal']) {
                const button = doc.createElement('button'); button.type = 'button'; button.textContent = `Paint ${type}`; button.className = 'button button-secondary button-small';
                button.setAttribute('aria-pressed', String(type === tool));
                button.addEventListener('click', () => { tool = type; [...tools.children].forEach(item => item.setAttribute('aria-pressed', String(item === button))); }); tools.append(button);
            }
            details.append(tools);
            const note = doc.createElement('p'); note.className = 'field-hint'; note.textContent = 'Choose a brush, then click cells. Path toggles tiles; start and goal stay on the path. Crystal collection requires a conditional template.'; details.append(note);
            const board = doc.createElement('div'); board.className = 'tower-board-editor'; board.style.gridTemplateColumns = `repeat(${Math.min(12, stage.width)}, 1fr)`;
            const same = (a, b) => a?.[0] === b[0] && a?.[1] === b[1];
            const paint = () => [...board.children].forEach(cell => {
                const point = JSON.parse(cell.dataset.point);
                const marker = same(stage.start, point) ? 'profile' : same(stage.goal, point) ? 'flag' : stage.crystals.some(p => same(p, point)) ? 'crystal' : null; cell.replaceChildren(); if (marker) setIconLabel(cell, marker);
                cell.classList.toggle('is-path', stage.path.some(p => same(p, point)));
                cell.setAttribute('aria-label', `Column ${point[0] + 1}, row ${point[1] + 1}: ${marker || (cell.classList.contains('is-path') ? 'path' : 'empty')}`);
            });
            for (let y = 0; y < Math.min(12, stage.height); y++) for (let x = 0; x < Math.min(12, stage.width); x++) {
                const point = [x, y]; const cell = doc.createElement('button'); cell.type = 'button'; cell.dataset.point = JSON.stringify(point);
                cell.addEventListener('click', () => {
                    Object.assign(stage, paintGarden(gardenLayout(stage), point, tool, stage.mode));
                    paint(); changed(); previewHost.replaceChildren();
                }); board.append(cell);
            }
            details.append(board); paint();
        } else if (stage.scenario) {
            const scenario = stage.scenario;
            const visual = doc.createElement('div');
            const advanced = doc.createElement('details'); advanced.className = 'scenario-advanced';
            const summary = doc.createElement('summary'); summary.textContent = 'Advanced scenario fields'; advanced.append(summary);
            const draw = () => {
                visual.inert = !validScenario(stage.template, scenario);
                if (visual.inert) return;
                visual.replaceChildren(scenarioCanvas(doc, stage.template, scenario, (next, redraw = true) => {
                    Object.assign(scenario, next); changed(); previewHost.replaceChildren();
                    const values = {
                        'pixel-studio': () => [scenario.pixels.join('\n')],
                        'number-machine': () => [scenario.start, scenario.target],
                        'sort-lab': () => [scenario.items.join(', ')],
                        'logic-switchboard': () => [scenario.inputs.join(', '), scenario.gate, String(scenario.target)],
                        'string-forge': () => [scenario.start, scenario.target],
                        'memory-trail': () => [scenario.start.join(', '), scenario.target.join(', ')],
                        'terminal-quest': () => [scenario.files.map(file => `${file.name} | ${file.content}`).join('\n'), scenario.destination, scenario.content],
                    };
                    const current = values[stage.template]?.() ?? [];
                    advanced.querySelectorAll('input, textarea').forEach((input, index) => { input.value = current[index] ?? ''; });
                    if (redraw) draw();
                }));
            };
            details.append(visual, advanced); draw();
            advanced.addEventListener('input', draw);
            if (stage.template === 'pixel-studio') field(advanced, 'Target pixels · one column row color per line (1–3; mint, peach, lavender)', scenario.pixels.join('\n'), value => scenario.pixels = value.split('\n').filter(Boolean), { area: true });
            if (stage.template === 'number-machine') for (const key of ['start', 'target']) field(advanced, `${key === 'start' ? 'Starting' : 'Target'} number (−100 to 100)`, scenario[key], value => scenario[key] = value.trim() === '' ? null : Number(value));
            if (stage.template === 'sort-lab') field(advanced, 'Array · 2–6 numbers separated by commas (0–99)', scenario.items.join(', '), value => scenario.items = value.split(',').map(item => item.trim() === '' ? null : Number(item.trim())));
            if (['logic-switchboard', 'string-forge', 'memory-trail'].includes(stage.template)) {
                if (stage.template === 'logic-switchboard') {
                    field(advanced, 'Inputs · true/false separated by commas (2–4)', scenario.inputs.join(', '), value => scenario.inputs = value.split(',').map(item => item.trim() === 'true' ? true : item.trim() === 'false' ? false : null));
                    field(advanced, 'Gate · and, or or xor', scenario.gate, value => scenario.gate = value);
                    field(advanced, 'Target output · true or false', String(scenario.target), value => scenario.target = value === 'true' ? true : value === 'false' ? false : null);
                } else for (const key of ['start', 'target']) {
                    field(advanced, `${key} ${stage.template === 'memory-trail' ? 'stack · 0–9 separated by commas' : 'text · up to 40 characters'}`, Array.isArray(scenario[key]) ? scenario[key].join(', ') : scenario[key], value => scenario[key] = stage.template === 'memory-trail' ? (value.trim() === '' ? [] : value.split(',').map(item => item.trim() === '' ? null : Number(item.trim()))) : value);
                }
            }
            if (stage.template === 'terminal-quest') {
                field(advanced, 'Virtual files · one filename | content per line (up to 8)', scenario.files.map(file => `${file.name} | ${file.content}`).join('\n'), value => scenario.files = value.split('\n').filter(Boolean).map(line => {
                    const divider = line.indexOf('|'); return { name: divider < 0 ? '' : line.slice(0, divider).trim(), content: divider < 0 ? '' : line.slice(divider + 1).replace(/^ /, '') };
                }), { area: true, max: 3000 });
                field(advanced, 'Destination filename', scenario.destination, value => scenario.destination = value);
                field(advanced, 'Required destination content', scenario.content, value => scenario.content = value, { max: 300 });
            }
        }
    }
}

export function previewTowerStage(editor, stage, host) {
    const doc = editor.ownerDocument;
    if (stage.template === 'choice-quiz') {
        validateQuiz(stage);
        const root = doc.createElement('section'); root.className = 'practice-quiz'; root.dataset.practiceQuiz = JSON.stringify(stage);
        const title = doc.createElement('h2'); title.textContent = stage.title; root.append(title);
        if (stage.briefing) {
            const notes = doc.createElement('aside'); notes.className = 'lesson-briefing';
            for (const key of ['outcome', 'explanation', 'example', 'task', 'success']) {
                const text = doc.createElement('p'); text.textContent = stage.briefing[key]; notes.append(text);
            }
            root.append(notes);
        }
        const form = doc.createElement('form');
        root.append(form);
        const button = doc.createElement('button'); button.type = 'submit'; button.className = 'button button-play'; button.textContent = 'Check my idea'; form.append(button);
        const feedback = doc.createElement('p'); feedback.dataset.quizFeedback = ''; feedback.setAttribute('role', 'status'); feedback.textContent = 'Unsaved preview. No progress or rewards.';
        root.append(feedback); renderQuizForm(root, stage); host.replaceChildren(root); mountQuiz(root); return;
    }
    const garden = stage.template === 'command-garden';
    if (garden) validateInstance(stage);
    else if (!validScenario(stage.template, stage.scenario)) throw new Error('Invalid scenario');
    const fragment = editor.querySelector(garden ? '[data-tower-garden-shell]' : '[data-tower-arcade-shell]').content.cloneNode(true);
    const root = fragment.querySelector(garden ? '[data-coding-game]' : '[data-arcade-game]');
    root.removeAttribute('data-completion-url');
    root.dataset[garden ? 'codingGame' : 'arcadeGame'] = JSON.stringify(stage);
    root.setAttribute('aria-label', `Preview: ${stage.title}`); root.querySelector('h2').textContent = stage.title;
    root.querySelector(garden ? '.game-instructions > p' : '.arcade-mission').textContent = stage.instructions;
    if (garden) {
        root.querySelector('.step-pill').textContent = stage.concept;
        root.querySelector('[data-game-board]').setAttribute('aria-label', `Preview garden, ${stage.width} columns and ${stage.height} rows. Use the visual designer above to inspect the path.`);
        root.querySelector('[data-game-success] span').textContent = stage.learningIdea;
        for (const [key, mode] of [['repeat', 'loop'], ['conditional', 'conditional']]) root.querySelector(`[data-game-${key}]`).closest('label').hidden = stage.mode !== mode;
    }
    host.replaceChildren(fragment); (garden ? mountGarden : mountArcade)(root);
}
