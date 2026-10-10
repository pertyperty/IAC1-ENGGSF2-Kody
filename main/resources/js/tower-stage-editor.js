import { mountGarden, validateInstance } from './games/command-garden.js';
import { mountArcade, validScenario } from './games/arcade-games.js';
import { mountQuiz, validateQuiz } from './games/choice-quiz.js';
import { gardenLayout, paintGarden } from './games/garden-designer.js';

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
        const questions = stage.version === 2 ? stage.questions : [stage];
        if (Array.isArray(questions)) questions.forEach((question, position) => {
            const group = doc.createElement('fieldset');
            const legend = doc.createElement('legend'); legend.textContent = `Question ${position + 1}`; group.append(legend);
            field(group, 'Question', question.question, value => question.question = value, { area: true, max: 500 });
            (question.options ?? []).forEach((option, choice) => field(group, `Choice ${choice + 1}`, option.label, value => option.label = value, { max: 300 }));
            const answerLabel = doc.createElement('label'); answerLabel.textContent = 'Correct choice';
            const select = doc.createElement('select');
            (question.options ?? []).forEach((option, choice) => { const item = doc.createElement('option'); item.value = option.id; item.textContent = `Choice ${choice + 1}`; select.append(item); });
            select.value = question.answer;
            select.addEventListener('change', () => { question.answer = select.value; changed(); previewHost.replaceChildren(); });
            answerLabel.append(select); group.append(answerLabel);
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
                cell.textContent = same(stage.start, point) ? 'S' : same(stage.goal, point) ? '⚑' : stage.crystals.some(p => same(p, point)) ? '◇' : '';
                cell.classList.toggle('is-path', stage.path.some(p => same(p, point)));
                cell.setAttribute('aria-label', `Column ${point[0] + 1}, row ${point[1] + 1}: ${cell.textContent || (cell.classList.contains('is-path') ? 'path' : 'empty')}`);
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
            if (stage.template === 'pixel-studio') field(details, 'Target pixels · one column row color per line (1–3; mint, peach, lavender)', scenario.pixels.join('\n'), value => scenario.pixels = value.split('\n').filter(Boolean), { area: true });
            if (stage.template === 'number-machine') for (const key of ['start', 'target']) field(details, `${key === 'start' ? 'Starting' : 'Target'} number (−100 to 100)`, scenario[key], value => scenario[key] = value.trim() === '' ? null : Number(value));
            if (stage.template === 'sort-lab') field(details, 'Array · 2–6 numbers separated by commas (0–99)', scenario.items.join(', '), value => scenario.items = value.split(',').map(item => item.trim() === '' ? null : Number(item.trim())));
            if (stage.template === 'terminal-quest') {
                field(details, 'Virtual files · one filename | content per line (up to 8)', scenario.files.map(file => `${file.name} | ${file.content}`).join('\n'), value => scenario.files = value.split('\n').filter(Boolean).map(line => {
                    const divider = line.indexOf('|'); return { name: divider < 0 ? '' : line.slice(0, divider).trim(), content: divider < 0 ? '' : line.slice(divider + 1).replace(/^ /, '') };
                }), { area: true, max: 3000 });
                field(details, 'Destination filename', scenario.destination, value => scenario.destination = value);
                field(details, 'Required destination content', scenario.content, value => scenario.content = value, { max: 300 });
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
        const form = doc.createElement('form');
        const questions = stage.version === 2 ? stage.questions : [{ ...stage, id: 'q1' }];
        questions.forEach(question => {
            const group = doc.createElement('fieldset'); group.dataset.quizQuestion = question.id;
            const legend = doc.createElement('legend'); legend.textContent = question.question; group.append(legend);
            question.options.forEach(option => { const label = doc.createElement('label'); const radio = doc.createElement('input'); radio.type = 'radio'; radio.name = `practice_answer_${question.id}`; radio.value = option.id; label.append(radio, doc.createTextNode(option.label)); group.append(label); });
            const feedback = doc.createElement('p'); feedback.dataset.questionFeedback = ''; feedback.setAttribute('role', 'status'); group.append(feedback); form.append(group);
        });
        const button = doc.createElement('button'); button.type = 'submit'; button.className = 'button button-play'; button.textContent = 'Check my idea'; form.append(button);
        const feedback = doc.createElement('p'); feedback.dataset.quizFeedback = ''; feedback.setAttribute('role', 'status'); feedback.textContent = 'Unsaved preview. No progress or rewards.';
        root.append(form, feedback); host.replaceChildren(root); mountQuiz(root); return;
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
