import { validateQuestion, renderQuizForm } from './quiz-types.js';
import { mountQuiz, validateQuiz } from './choice-quiz.js';
import { readQuizQuestions } from './quiz-editor.js';

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

export function quizFromQuestions(title, questions) {
    if (!title?.trim() || [...title].length > 100 || !Array.isArray(questions) || questions.length < 1 || questions.length > 10) throw new Error('Invalid quiz.');
    questions.forEach(validateQuestion);
    const mixed = questions.some(question => (question.type ?? 'choice') !== 'choice' || question.retry_explanation?.trim());
    return validateQuiz(!mixed && questions.length === 1 ? { ...questions[0], template: 'choice-quiz', version: 1, title }
        : { template: 'choice-quiz', version: mixed ? 3 : 2, title, questions });
}

export function mountQuizDesigner(form) {
    const host = form.parentElement.querySelector('[data-quiz-preview]');
    if (!host) return;
    const kind = form.querySelector('[name="assessment_kind"]');
    const editor = form.querySelector('[data-quiz-editor]');
    const title = form.querySelector('[name="quiz_title"]');
    const output = host.querySelector('[data-quiz-preview-host]');
    const status = host.querySelector('[data-quiz-preview-status]');
    const clear = () => {
        output.replaceChildren();
        status.textContent = 'Try your current choices and feedback. Nothing is saved by this preview.';
        host.hidden = kind.value !== 'quiz';
    };
    editor.addEventListener('input', clear);
    title.addEventListener('input', clear);
    kind.addEventListener('change', clear);
    host.querySelector('[data-quiz-preview-button]').addEventListener('click', () => {
        try {
            const quiz = quizFromQuestions(title.value, readQuizQuestions(editor));
            const fragment = host.querySelector('template').content.cloneNode(true);
            const preview = fragment.querySelector('[data-practice-quiz]');
            preview.dataset.practiceQuiz = JSON.stringify(quiz);
            preview.querySelector('h2').textContent = quiz.title;
            renderQuizForm(preview, quiz);
            output.replaceChildren(fragment);
            mountQuiz(preview);
            status.textContent = 'This is your unsaved quiz. Answer it below, or edit a prompt and try again.';
        } catch {
            output.replaceChildren();
            status.textContent = 'Complete the title and each question, with valid choices, accepted short answers or matching pairs, and feedback within their limits.';
        }
    });
    clear();
}
