import { saveCompletion } from './save-completion.js';

export function validateQuiz(quiz) {
    if (!quiz || quiz.template !== 'choice-quiz' || ![1, 2].includes(quiz.version)) throw new Error('Unsupported quiz template.');
    if (quiz.version === 2) {
        if (typeof quiz.title !== 'string' || [...quiz.title].length > 1000 || !Array.isArray(quiz.questions)
            || quiz.questions.length < 2 || quiz.questions.length > 10) throw new Error('Invalid quiz questions.');
        for (const question of quiz.questions) {
            if (!question || typeof question.id !== 'string' || !/^[a-z0-9_-]{1,30}$/.test(question.id)) throw new Error('Invalid question identifier.');
            validateQuiz({ ...question, template: 'choice-quiz', version: 1, title: quiz.title });
        }
        if (new Set(quiz.questions.map((question) => question.id)).size !== quiz.questions.length) throw new Error('Duplicate questions.');
        return quiz;
    }
    if (!Array.isArray(quiz.options) || quiz.options.length < 2 || quiz.options.length > 6) throw new Error('Invalid quiz options.');
    for (const field of ['title', 'question', 'explanation']) {
        if (typeof quiz[field] !== 'string' || [...quiz[field]].length > 1000) throw new Error('Invalid quiz text.');
    }
    for (const option of quiz.options) {
        if (!option || typeof option.id !== 'string' || !/^[a-z0-9_-]{1,30}$/.test(option.id)
            || typeof option.label !== 'string' || [...option.label].length > 500) throw new Error('Invalid quiz option.');
    }
    if (new Set(quiz.options.map((option) => option.id)).size !== quiz.options.length
        || !quiz.options.some((option) => option.id === quiz.answer)) throw new Error('Invalid quiz answer.');
    return quiz;
}

// Practice answer keys are intentionally public. Graded quizzes need server evaluation.
export function checkPracticeAnswer(quiz, answer) {
    validateQuiz(quiz);
    if (quiz.version === 2) {
        const results = quiz.questions.map((question) => ({ id: question.id, ...checkPracticeAnswer({ ...question, template: 'choice-quiz', version: 1, title: quiz.title }, answer?.[question.id]) }));
        const correct = results.every((result) => result.correct) && answer && Object.keys(answer).length === quiz.questions.length;
        return { correct: Boolean(correct), results, message: correct ? 'Every idea checked! You completed this practice quiz.' : 'Check the feedback below each question and try again.' };
    }
    if (!quiz.options.some((option) => option.id === answer)) return { correct: false, message: 'Choose an answer first.' };
    return { correct: answer === quiz.answer, message: answer === quiz.answer ? `You got it! ${quiz.explanation}` : `Try another idea. ${quiz.explanation}` };
}

export function mountQuiz(root) {
    const feedback = root.querySelector('[data-quiz-feedback]');
    let quiz;
    try { quiz = validateQuiz(JSON.parse(root.dataset.practiceQuiz)); }
    catch { feedback.textContent = 'This quiz is unavailable. Try another adventure.'; root.querySelector('button').disabled = true; return; }
    let busy = false;
    root.querySelector('form').addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;
        const answer = quiz.version === 1 ? root.querySelector('input:checked')?.value
            : Object.fromEntries(quiz.questions.map((question) => [question.id, root.querySelector(`[data-quiz-question="${question.id}"]`).querySelector('input:checked')?.value]));
        const result = checkPracticeAnswer(quiz, answer);
        feedback.textContent = result.message;
        if (result.results) result.results.forEach((item) => { root.querySelector(`[data-quiz-question="${item.id}"] [data-question-feedback]`).textContent = item.message; });
        root.classList.toggle('quiz-correct', result.correct);
        if (result.correct) {
            busy = true;
            const controls = root.querySelectorAll('button, input');
            controls.forEach((control) => { control.disabled = true; });
            root.setAttribute('aria-busy', 'true');
            try {
                const saved = await saveCompletion(root, quiz.version === 1 ? { answer } : { answers: answer });
                feedback.textContent = `${result.message} ${saved}`;
            } finally {
                busy = false;
                controls.forEach((control) => { control.disabled = false; });
                root.setAttribute('aria-busy', 'false');
            }
        }
    });
}
