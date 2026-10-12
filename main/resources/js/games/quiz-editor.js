export function readQuizQuestions(root) {
    return [...root.querySelectorAll('[data-quiz-question-editor]')].map(question => {
        const common = Object.fromEntries(['id', 'question', 'explanation'].map(field => [field, question.querySelector(`[data-question-field="${field}"]`).value]));
        const type = question.querySelector('[data-question-field="type"]').value;
        if (type === 'blank') return { ...common, type, accepted: [...question.querySelectorAll('[data-accepted-answer]')].map(input => input.value), case_sensitive: question.querySelector('[data-case-sensitive]').checked };
        if (type === 'matching') return { ...common, type, pairs: [...question.querySelectorAll('[data-matching-pair]')].map(pair => Object.fromEntries(['id', 'left', 'right'].map(field => [field, pair.querySelector(`[data-pair-field="${field}"]`).value]))) };
        return { ...common, answer: question.querySelector('[data-question-field="answer"]').value,
            options: [...question.querySelectorAll('[data-quiz-option-editor]')].map(option => Object.fromEntries(['id', 'label'].map(field => [field, option.querySelector(`[data-option-field="${field}"]`).value]))) };
    });
}
export function firstUnusedId(ids, prefix = 'q') {
    for (let i = 1; i <= 100; i++) if (!ids.includes(`${prefix}${i}`)) return `${prefix}${i}`;
    throw new Error('No available identifier.');
}

export function mountQuizEditor(root) {
    const openQuestion = (question, open) => {
        question.classList.toggle('is-collapsed', !open);
        const toggle = question.querySelector('[data-question-toggle]');
        toggle.hidden = false; toggle.setAttribute('aria-expanded', String(open));
        toggle.querySelector('.sr-only').textContent = open ? 'Collapse question' : 'Expand question';
    };
    const questions = root.querySelector('[data-quiz-questions]');
    const status = root.querySelector('[data-quiz-editor-status]');
    const announce = (message) => { status.textContent = message; root.dispatchEvent(new Event('input', { bubbles: true })); };
    const update = () => {
        const rows = [...questions.children];
        rows.forEach((question, index) => {
            const type = question.querySelector('[data-question-field="type"]').value;
            question.querySelectorAll('[data-question-type]').forEach(group => { group.hidden = group.dataset.questionType !== type; group.disabled = group.hidden; });
            question.querySelectorAll('[data-accepted-answer]').forEach((input, answerIndex) => { input.name = `quiz_questions[${index}][accepted][${answerIndex}]`; });
            question.querySelectorAll('[data-case-default], [data-case-sensitive]').forEach(input => { input.name = `quiz_questions[${index}][case_sensitive]`; });
            question.querySelectorAll('[data-remove-accepted]').forEach(button => { button.disabled = question.querySelectorAll('[data-accepted-answer]').length <= 1; });
            question.querySelector('[data-add-accepted]').disabled = question.querySelectorAll('[data-accepted-answer]').length >= 6;
            question.querySelectorAll('[data-matching-pair]').forEach((pair, pairIndex) => {
                pair.querySelectorAll('[data-pair-field]').forEach(input => { input.name = `quiz_questions[${index}][pairs][${pairIndex}][${input.dataset.pairField}]`; });
                pair.querySelector('[data-remove-pair]').disabled = question.querySelectorAll('[data-matching-pair]').length <= 2;
            });
            question.querySelector('[data-add-pair]').disabled = question.querySelectorAll('[data-matching-pair]').length >= 6;
            question.querySelector('[data-question-heading]').textContent = `Question ${index + 1}`;
            question.querySelectorAll('[data-question-field]').forEach((field) => { field.name = `quiz_questions[${index}][${field.dataset.questionField}]`; });
            const options = [...question.querySelector('[data-quiz-options]').children];
            const answer = question.querySelector('[data-question-field="answer"]');
            const selected = answer.value;
            const placeholder = root.ownerDocument.createElement('option');
            placeholder.value = ''; placeholder.textContent = 'Choose the correct answer';
            answer.replaceChildren(placeholder);
            options.forEach((option, position) => {
                option.querySelector('[data-option-heading]').textContent = `Choice ${position + 1}`;
                option.querySelectorAll('[data-option-field]').forEach((field) => { field.name = `quiz_questions[${index}][options][${position}][${field.dataset.optionField}]`; });
                const item = root.ownerDocument.createElement('option');
                item.value = option.querySelector('[data-option-field="id"]').value;
                item.textContent = `Choice ${position + 1}`;
                answer.append(item);
                option.querySelector('[data-remove-option]').disabled = options.length <= 2;
                option.querySelector('[data-move-option="up"]').disabled = position === 0;
                option.querySelector('[data-move-option="down"]').disabled = position === options.length - 1;
            });
            answer.value = options.some((option) => option.querySelector('[data-option-field="id"]').value === selected) ? selected : '';
            question.querySelector('[data-add-option]').disabled = options.length >= 6;
            question.querySelector('[data-remove-question]').disabled = rows.length <= 1;
            question.querySelector('[data-move-question="up"]').disabled = index === 0;
            question.querySelector('[data-move-question="down"]').disabled = index === rows.length - 1;
        });
        root.querySelector('[data-add-question]').disabled = rows.length >= 10;
    };
    root.addEventListener('change', event => { if (event.target.matches('[data-question-field="type"]')) { update(); announce('Question type changed. Complete its answer settings before saving.'); } });
    root.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button || button.disabled) return;
        const question = button.closest('[data-quiz-question-editor]');
        const option = button.closest('[data-quiz-option-editor]');
        if (button.hasAttribute('data-question-toggle')) {
            openQuestion(question, question.classList.contains('is-collapsed'));
        } else if (button.hasAttribute('data-add-accepted') && question.querySelectorAll('[data-accepted-answer]').length < 6) {
            const row = question.querySelector('[data-accepted-answer]').closest('label').cloneNode(true); row.querySelector('input').value = ''; question.querySelector('[data-accepted-list]').append(row); update(); row.querySelector('input').focus(); announce('Accepted answer added.');
        } else if (button.hasAttribute('data-remove-accepted') && question.querySelectorAll('[data-accepted-answer]').length > 1) {
            button.closest('label').remove(); update(); question.querySelector('[data-add-accepted]').focus(); announce('Accepted answer removed.');
        } else if (button.hasAttribute('data-add-pair') && question.querySelectorAll('[data-matching-pair]').length < 6) {
            const row = root.querySelector('[data-pair-template]').content.firstElementChild.cloneNode(true); row.querySelector('[data-pair-field="id"]').value = firstUnusedId([...question.querySelectorAll('[data-pair-field="id"]')].map(input => input.value), 'p'); question.querySelector('[data-matching-pairs]').append(row); update(); row.querySelector('[data-pair-field="left"]').focus(); announce('Matching pair added.');
        } else if (button.hasAttribute('data-remove-pair') && question.querySelectorAll('[data-matching-pair]').length > 2) {
            button.closest('[data-matching-pair]').remove(); update(); question.querySelector('[data-add-pair]').focus(); announce('Matching pair removed.');
        } else
        if (button.hasAttribute('data-add-question') && questions.children.length < 10) {
            const row = root.querySelector('[data-question-template]').content.firstElementChild.cloneNode(true);
            row.querySelector('[data-question-field="id"]').value = firstUnusedId(readQuizQuestions(root).map((question) => question.id));
            questions.querySelectorAll('[data-quiz-question-editor]').forEach(item => openQuestion(item, false));
            questions.append(row); update(); openQuestion(row, true); row.querySelector('textarea').focus({ preventScroll: true }); row.scrollIntoView({ block: 'nearest' }); announce('Question added.');
        } else if (button.hasAttribute('data-add-option') && question.querySelectorAll('[data-quiz-option-editor]').length < 6) {
            const row = root.querySelector('[data-option-template]').content.firstElementChild.cloneNode(true);
            const ids = [...question.querySelectorAll('[data-option-field="id"]')].map((input) => input.value);
            row.querySelector('[data-option-field="id"]').value = firstUnusedId(ids, 'o');
            question.querySelector('[data-quiz-options]').append(row); update(); row.querySelector('textarea').focus(); announce('Choice added. Select the correct answer before saving.');
        } else if (button.hasAttribute('data-remove-question') && questions.children.length > 1) {
            question.remove(); update(); root.querySelector('[data-add-question]').focus(); announce('Question removed.');
        } else if (button.hasAttribute('data-remove-option') && question.querySelectorAll('[data-quiz-option-editor]').length > 2) {
            option.remove(); update(); question.querySelector('[data-add-option]').focus(); announce('Choice removed. Check the correct answer.');
        } else if (button.dataset.moveQuestion || button.dataset.moveOption) {
            const row = button.dataset.moveOption ? option : question;
            const direction = button.dataset.moveOption ?? button.dataset.moveQuestion;
            const sibling = direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
            if (sibling) {
                if (direction === 'up') row.parentElement.insertBefore(row, sibling);
                else row.parentElement.insertBefore(sibling, row);
                update(); button.focus(); announce('Order updated. Correct answers stay attached to their choices.');
            }
        }
    });
    update();
    questions.querySelectorAll('[data-quiz-question-editor]').forEach((question, index) => openQuestion(question, index === 0 || Boolean(root.closest('form')?.querySelector('.form-errors'))));
}
