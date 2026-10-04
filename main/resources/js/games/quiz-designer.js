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
    questions.forEach((question) => {
        quizFromFields({ quiz_title: title, quiz_question: question.question, quiz_a: question.options?.[0]?.label,
            quiz_b: question.options?.[1]?.label, quiz_answer: 'a', quiz_explanation: question.explanation });
        if (question.options.some((option) => !option.label?.trim() || [...option.label].length > 300)
            || new Set(question.options.map((option) => option.label.trim())).size !== question.options.length) throw new Error('Invalid choices.');
    });
    return validateQuiz(questions.length === 1 ? { ...questions[0], template: 'choice-quiz', version: 1, title }
        : { template: 'choice-quiz', version: 2, title, questions });
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
            const formShell = preview.querySelector('form');
            const button = formShell.querySelector('button');
            formShell.replaceChildren();
            const questions = quiz.version === 1 ? [{ ...quiz, id: 'q1' }] : quiz.questions;
            questions.forEach((question, index) => {
                const fieldset = form.ownerDocument.createElement('fieldset');
                fieldset.dataset.quizQuestion = question.id;
                const legend = form.ownerDocument.createElement('legend');
                legend.textContent = `${questions.length > 1 ? `${index + 1}. ` : ''}${question.question}`;
                fieldset.append(legend);
                question.options.forEach((option) => {
                    const label = form.ownerDocument.createElement('label');
                    const input = form.ownerDocument.createElement('input');
                    input.type = 'radio'; input.name = `practice_answer_${question.id}`; input.value = option.id;
                    label.append(input, form.ownerDocument.createTextNode(option.label)); fieldset.append(label);
                });
                const note = form.ownerDocument.createElement('p');
                note.dataset.questionFeedback = ''; note.setAttribute('role', 'status'); fieldset.append(note);
                formShell.append(fieldset);
            });
            formShell.append(button);
            output.replaceChildren(fragment);
            mountQuiz(preview);
            status.textContent = 'This is your unsaved quiz. Answer it below, or edit a prompt and try again.';
        } catch {
            output.replaceChildren();
            status.textContent = 'Complete the title and each question, with two to six different choices, a correct answer and feedback within their character limits.';
        }
    });
    clear();
}
