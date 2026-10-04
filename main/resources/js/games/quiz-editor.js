export function readQuizQuestions(root) {
    return [...root.querySelectorAll('[data-quiz-question-editor]')].map((question) => ({
        ...Object.fromEntries(['id', 'question', 'answer', 'explanation'].map((field) => [field, question.querySelector(`[data-question-field="${field}"]`).value])),
        options: [...question.querySelectorAll('[data-quiz-option-editor]')].map((option) => Object.fromEntries(['id', 'label']
            .map((field) => [field, option.querySelector(`[data-option-field="${field}"]`).value]))),
    }));
}

export function firstUnusedId(ids, prefix = 'q') {
    for (let i = 1; i <= 100; i++) if (!ids.includes(`${prefix}${i}`)) return `${prefix}${i}`;
    throw new Error('No available identifier.');
}

export function mountQuizEditor(root) {
    const questions = root.querySelector('[data-quiz-questions]');
    const status = root.querySelector('[data-quiz-editor-status]');
    const announce = (message) => { status.textContent = message; root.dispatchEvent(new Event('input', { bubbles: true })); };
    const update = () => {
        const rows = [...questions.children];
        rows.forEach((question, index) => {
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
    root.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button || button.disabled) return;
        const question = button.closest('[data-quiz-question-editor]');
        const option = button.closest('[data-quiz-option-editor]');
        if (button.hasAttribute('data-add-question') && questions.children.length < 10) {
            const row = root.querySelector('[data-question-template]').content.firstElementChild.cloneNode(true);
            row.querySelector('[data-question-field="id"]').value = firstUnusedId(readQuizQuestions(root).map((question) => question.id));
            questions.append(row); update(); row.querySelector('textarea').focus(); announce('Question added.');
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
}
