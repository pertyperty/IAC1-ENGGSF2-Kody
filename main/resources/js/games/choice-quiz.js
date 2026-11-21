import { validateQuestion, checkQuestion, readQuestionAnswer } from './quiz-types.js';
import { saveCompletion } from './save-completion.js';

export function validateQuiz(quiz) {
    if (!quiz || quiz.template !== 'choice-quiz' || ![1, 2, 3].includes(quiz.version)) throw new Error('Unsupported quiz template.');
    if (quiz.version !== 1) {
        if (typeof quiz.title !== 'string' || [...quiz.title].length > 1000 || !Array.isArray(quiz.questions)
            || quiz.questions.length < (quiz.version === 3 ? 1 : 2) || quiz.questions.length > 10) throw new Error('Invalid quiz questions.');
        for (const question of quiz.questions) {
            if (!question || typeof question.id !== 'string' || !/^[a-z0-9_-]{1,30}$/.test(question.id)) throw new Error('Invalid question identifier.');
            if (quiz.version === 3) validateQuestion(question); else validateQuiz({ ...question, template: 'choice-quiz', version: 1, title: quiz.title });
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
    if (quiz.version !== 1) {
        const results = quiz.questions.map((question) => ({ id: question.id, ...(quiz.version === 3 ? checkQuestion(question, answer?.[question.id]) : checkPracticeAnswer({ ...question, template: 'choice-quiz', version: 1, title: quiz.title }, answer?.[question.id])) }));
        const correct = results.every((result) => result.correct) && answer && Object.keys(answer).length === quiz.questions.length;
        return { correct: Boolean(correct), results, message: correct ? 'Every idea checked! You completed this practice quiz.' : 'Check the feedback below each question and try again.' };
    }
    if (!quiz.options.some((option) => option.id === answer)) return { correct: false, message: 'Choose an answer first.' };
    return { correct: answer === quiz.answer, message: answer === quiz.answer ? `You got it! ${quiz.explanation}` : `Try another idea. ${quiz.explanation}` };
}

const mountedQuizzes = new WeakSet();
export function mountQuiz(root) {
    if (mountedQuizzes.has(root)) return;
    mountedQuizzes.add(root);
    const feedback = root.querySelector('[data-quiz-feedback]');
    let quiz;
    try { quiz = validateQuiz(JSON.parse(root.dataset.practiceQuiz)); }
    catch { feedback.textContent = 'This quiz is unavailable. Try another adventure.'; root.querySelector('button').disabled = true; return; }
    let busy = false;
    const navigation = root.querySelector('[data-quiz-navigation]');
    const groups = navigation ? [...root.querySelectorAll('[data-quiz-question]')] : [];
    let updateNavigation = () => {};
    let showQuestion = () => {};
    if (navigation && root.ownerDocument) {
        const doc = root.ownerDocument;
        const form = root.querySelector('form');
        let current = 0;
        navigation.replaceChildren();
        const progress = doc.createElement('span');
        progress.setAttribute('role', 'status');
        navigation.append(progress);
        const step = doc.createElement('p');
        step.className = 'quiz-current-question';
        step.setAttribute('aria-live', 'polite');
        form.prepend(step);
        const actions = doc.createElement('div');
        actions.className = 'quiz-step-actions';
        const previous = doc.createElement('button');
        previous.type = 'button';
        previous.className = 'button button-secondary';
        previous.textContent = 'Previous question';
        const next = doc.createElement('button');
        next.type = 'button';
        next.className = 'button button-play';
        next.textContent = 'Next question';
        actions.append(previous, next);
        const submit = form.querySelector('button[type="submit"]');
        const footer = doc.createElement('div');
        footer.className = 'quiz-footer';
        submit.before(footer);
        footer.append(actions, submit);
        const jumps = groups.map((group, index) => {
            const jump = doc.createElement('button');
            jump.type = 'button';
            jump.className = 'quiz-jump';
            jump.textContent = String(index + 1);
            jump.addEventListener('click', () => showQuestion(index));
            navigation.append(jump);
            return jump;
        });
        showQuestion = (index, focus = true) => {
            current = Math.max(0, Math.min(groups.length - 1, index));
            groups.forEach((group, position) => { group.hidden = position !== current; });
            jumps.forEach((jump, position) => {
                if (position === current) jump.setAttribute('aria-current', 'step');
                else jump.removeAttribute('aria-current');
            });
            step.textContent = `Question ${current + 1} of ${groups.length}`;
            previous.disabled = current === 0;
            previous.hidden = current === 0;
            submit.classList.toggle('button-secondary', current < groups.length - 1);
            next.hidden = current === groups.length - 1;
            actions.hidden = groups.length < 2;
            if (focus) groups[current].querySelector('input, select')?.focus();
        };
        previous.addEventListener('click', () => showQuestion(current - 1));
        next.addEventListener('click', () => showQuestion(current + 1));
        updateNavigation = () => {
            let completed = 0;
            groups.forEach((group, index) => {
                const controls = [...group.querySelectorAll('input, select')];
                const answered = controls.some(control => control.type === 'radio')
                    ? controls.some(control => control.checked)
                    : controls.length > 0 && controls.every(control => control.value.trim());
                jumps[index].dataset.answered = String(Boolean(answered));
                jumps[index].setAttribute('aria-label', `Go to question ${index + 1}${answered ? ', answered' : ', unanswered'}`);
                if (answered) completed++;
            });
            progress.textContent = `${completed} of ${groups.length} answered`;
        };
        groups.forEach(group => group.addEventListener('input', () => {
            if (busy) return;
            root.classList.remove('quiz-correct');
            group.classList.remove('question-correct', 'question-retry');
            group.querySelector('[data-question-feedback]').textContent = '';
            feedback.textContent = 'Answer changed. Check your answers again.';
            updateNavigation();
        }));
        root.classList.add('quiz-stepped');
        updateNavigation();
        showQuestion(0, false);
    }
    root.querySelector('form').addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;
        const answer = quiz.version === 1 ? root.querySelector('input:checked')?.value
            : Object.fromEntries(quiz.questions.map((question) => [question.id, readQuestionAnswer(root.querySelector(`[data-quiz-question="${question.id}"]`), question)]));
        const result = checkPracticeAnswer(quiz, answer);
        feedback.textContent = result.message;
        if (result.results) result.results.forEach((item) => {
            const group = root.querySelector(`[data-quiz-question="${item.id}"]`);
            group.querySelector('[data-question-feedback]').textContent = item.message;
            group.classList.toggle('question-correct', item.correct);
            group.classList.toggle('question-retry', !item.correct);
        });
        if (!result.results && groups.length === 1) {
            groups[0].classList.toggle('question-correct', result.correct);
            groups[0].querySelector('[data-question-feedback]').textContent = result.message;
        }
        root.classList.toggle('quiz-correct', result.correct);
        if (!result.correct) {
            const incorrect = result.results?.find(item => !item.correct);
            const group = incorrect ? root.querySelector(`[data-quiz-question="${incorrect.id}"]`) : root;
            const index = groups.indexOf(group);
            if (index >= 0) showQuestion(index);
            else group.querySelector('input, select')?.focus({ preventScroll: true });
        }
        if (result.correct) {
            busy = true;
            const controls = root.querySelectorAll('button, input, select');
            controls.forEach((control) => { control.disabled = true; });
            root.setAttribute('aria-busy', 'true');
            try {
                const saved = await saveCompletion(root, quiz.version === 1 ? { answer } : { answers: answer });
                feedback.textContent = `${result.message} ${saved}`;
            } finally {
                busy = false;
                controls.forEach((control) => { control.disabled = false; });
                root.setAttribute('aria-busy', 'false');
                const active = groups.findIndex(group => !group.hidden);
                if (active >= 0) showQuestion(active, false);
            }
        }
    });
}
