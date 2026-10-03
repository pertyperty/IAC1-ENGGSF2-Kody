import { saveCompletion } from './save-completion.js';

export function validateQuiz(quiz) {
    if (!quiz || quiz.template !== 'choice-quiz' || quiz.version !== 1) throw new Error('Unsupported quiz template.');
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
    if (!quiz.options.some((option) => option.id === answer)) return { correct: false, message: 'Choose an answer first.' };
    return { correct: answer === quiz.answer, message: answer === quiz.answer ? `You got it! ${quiz.explanation}` : `Try another idea. ${quiz.explanation}` };
}

export function mountQuiz(root) {
    const feedback = root.querySelector('[data-quiz-feedback]');
    let quiz;
    try { quiz = validateQuiz(JSON.parse(root.dataset.practiceQuiz)); }
    catch { feedback.textContent = 'This quiz is unavailable. Try another adventure.'; root.querySelector('button').disabled = true; return; }
    root.querySelector('form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const answer = root.querySelector('input:checked')?.value;
        const result = checkPracticeAnswer(quiz, answer);
        feedback.textContent = result.message;
        root.classList.toggle('quiz-correct', result.correct);
        if (result.correct) {
            const button = root.querySelector('button'); button.disabled = true;
            const saved = await saveCompletion(root, { answer });
            feedback.textContent = `${result.message} ${saved}`;
            button.disabled = false;
        }
    });
}
