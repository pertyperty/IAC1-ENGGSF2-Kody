import { mountQuiz, validateQuiz } from './choice-quiz.js';

export function quizFromFields(fields) {
    const quiz = {
        template: 'choice-quiz', version: 1, title: fields.quiz_title,
        question: fields.quiz_question, explanation: fields.quiz_explanation,
        options: [{ id: 'a', label: fields.quiz_a }, { id: 'b', label: fields.quiz_b }], answer: fields.quiz_answer,
    };
    if ([quiz.title, quiz.question, quiz.explanation, ...quiz.options.map((option) => option.label)].some((text) => !text?.trim())
        || [...quiz.title].length > 100 || [...quiz.question].length > 500
        || quiz.options.some((option) => [...option.label].length > 300)
        || fields.quiz_a === fields.quiz_b) throw new Error('Complete your quiz prompts first.');
    return validateQuiz(quiz);
}

export function mountQuizDesigner(form) {
    const host = form.parentElement.querySelector('[data-quiz-preview]');
    if (!host) return;
    const kind = form.querySelector('[name="assessment_kind"]');
    const fields = Object.fromEntries(['quiz_title', 'quiz_question', 'quiz_a', 'quiz_b', 'quiz_explanation', 'quiz_answer']
        .map((name) => [name, form.querySelector(`[name="${name}"]`)]));
    const output = host.querySelector('[data-quiz-preview-host]');
    const status = host.querySelector('[data-quiz-preview-status]');
    const clear = () => {
        output.replaceChildren();
        status.textContent = 'Try your current choices and feedback. Nothing is saved by this preview.';
        host.hidden = kind.value !== 'quiz';
    };
    Object.values(fields).forEach((field) => field.addEventListener('input', clear));
    kind.addEventListener('change', clear);
    host.querySelector('[data-quiz-preview-button]').addEventListener('click', () => {
        try {
            const quiz = quizFromFields(Object.fromEntries(Object.entries(fields).map(([name, field]) => [name, field.value])));
            const fragment = host.querySelector('template').content.cloneNode(true);
            const preview = fragment.querySelector('[data-practice-quiz]');
            preview.dataset.practiceQuiz = JSON.stringify(quiz);
            preview.querySelector('h2').textContent = quiz.title;
            preview.querySelector('legend').textContent = quiz.question;
            preview.querySelectorAll('label').forEach((label, index) => {
                // Keep the radio control; replace the text with text nodes only.
                label.replaceChildren(label.querySelector('input'), form.ownerDocument.createTextNode(quiz.options[index].label));
            });
            output.replaceChildren(fragment);
            mountQuiz(preview);
            status.textContent = 'This is your unsaved quiz. Answer it below, or edit a prompt and try again.';
        } catch {
            output.replaceChildren();
            status.textContent = 'Complete the title, question, two different choices and feedback within their character limits, then choose the correct answer.';
        }
    });
    clear();
}
