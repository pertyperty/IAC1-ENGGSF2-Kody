export function normalizeBlank(answer, sensitive = false) {
    const normalized = answer.replace(/[ \t\r\n\f\v]+/g, ' ').replace(/^[ \t\r\n\f\v]+|[ \t\r\n\f\v]+$/g, '');
    return sensitive ? normalized : normalized.toLowerCase();
}

export function validateQuestion(question) {
    if (!question || !/^[a-z0-9_-]{1,30}$/.test(question.id ?? '') || !question.question?.trim()
        || [...question.question].length > 500 || !question.explanation?.trim() || [...question.explanation].length > 1000) throw new Error('Complete the question and feedback.');
    const type = question.type ?? 'choice';
    const distinct = labels => labels.every(label => typeof label === 'string' && label.trim()) && new Set(labels.map(label => label.trim())).size === labels.length;
    const allowed = { choice: ['options', 'answer'], blank: ['accepted', 'case_sensitive'], matching: ['pairs'] }[type];
    if (!allowed || Object.keys(question).some(key => !['id', 'type', 'question', 'explanation', 'retry_explanation', ...allowed].includes(key))) throw new Error('Unsupported question fields.');
    if (question.retry_explanation != null && (typeof question.retry_explanation !== 'string' || [...question.retry_explanation].length > 1000)) throw new Error('Retry feedback must be at most 1000 characters.');
    if (type === 'choice') {
        if (!Array.isArray(question.options) || question.options.length < 2 || question.options.length > 6
            || !distinct(question.options.map(option => option.label)) || question.options.some(option => [...option.label].length > 300 || !/^[a-z0-9_-]{1,30}$/.test(option.id))
            || new Set(question.options.map(option => option.id)).size !== question.options.length || !question.options.some(option => option.id === question.answer)) throw new Error('Choose a valid correct choice.');
    } else if (type === 'blank') {
        if (typeof question.case_sensitive !== 'boolean' || !Array.isArray(question.accepted) || question.accepted.length < 1 || question.accepted.length > 6
            || question.accepted.some(answer => typeof answer !== 'string' || [...answer].length > 100)
            || !distinct(question.accepted.map(answer => normalizeBlank(answer, question.case_sensitive)))) throw new Error('Add one to six distinct accepted short answers.');
    } else {
        if (!Array.isArray(question.pairs) || question.pairs.length < 2 || question.pairs.length > 6
            || !distinct(question.pairs.map(pair => pair.left)) || !distinct(question.pairs.map(pair => pair.right))
            || question.pairs.some(pair => !/^[a-z0-9_-]{1,30}$/.test(pair.id) || [...pair.left].length > 300 || [...pair.right].length > 300)
            || new Set(question.pairs.map(pair => pair.id)).size !== question.pairs.length) throw new Error('Add two to six distinct matching pairs.');
    }
    return question;
}

export function checkQuestion(question, answer) {
    const type = question.type ?? 'choice';
    const correct = type === 'blank' ? typeof answer === 'string' && [...answer].length <= 100 && question.accepted.some(accepted => normalizeBlank(accepted, question.case_sensitive) === normalizeBlank(answer, question.case_sensitive))
        : type === 'matching' ? Boolean(answer && typeof answer === 'object' && !Array.isArray(answer) && Object.keys(answer).length === question.pairs.length && question.pairs.every(pair => answer[pair.id] === pair.id))
        : answer === question.answer;
    return { correct, message: `${correct ? 'Correct.' : 'Try again.'} ${!correct && question.retry_explanation?.trim() ? question.retry_explanation : question.explanation}` };
}

export function readQuestionAnswer(group, question) {
    return question.type === 'blank' ? group.querySelector('[data-blank-answer]').value
        : question.type === 'matching' ? Object.fromEntries([...group.querySelectorAll('[data-match-answer]')].map(select => [select.dataset.matchAnswer, select.value]))
        : group.querySelector('input:checked')?.value;
}

export function renderQuizForm(root, quiz) {
    const doc = root.ownerDocument, form = root.querySelector('form');
    const button = form.querySelector('button[type="submit"], button');
    form.replaceChildren();
    const questions = quiz.version === 1 ? [{ id: 'q1', ...quiz }] : quiz.questions;
    questions.forEach((question, index) => {
        const group = doc.createElement('fieldset'); group.dataset.quizQuestion = question.id;
        const legend = doc.createElement('legend'); legend.textContent = `${questions.length > 1 ? `${index + 1}. ` : ''}${question.question}`; group.append(legend);
        if (question.type === 'blank') {
            const label = doc.createElement('label'); label.textContent = 'Your short answer';
            const input = doc.createElement('input'); input.type = 'text'; input.maxLength = 100; input.dataset.blankAnswer = ''; input.autocomplete = 'off'; label.append(input); group.append(label);
            const hint = doc.createElement('p'); hint.className = 'field-hint'; hint.textContent = question.case_sensitive ? 'Letter case matters. Extra spaces are ignored.' : 'Letter case and extra spaces are ignored.'; group.append(hint);
        } else if (question.type === 'matching') {
            const choices = [...question.pairs.slice(1), question.pairs[0]];
            question.pairs.forEach(pair => {
                const label = doc.createElement('label'); label.className = 'quiz-match-row'; label.append(doc.createTextNode(pair.left));
                const select = doc.createElement('select'); select.dataset.matchAnswer = pair.id; select.setAttribute('aria-label', pair.left);
                const placeholder = doc.createElement('option'); placeholder.value = ''; placeholder.textContent = 'Choose a match'; select.append(placeholder);
                choices.forEach(choice => { const option = doc.createElement('option'); option.value = choice.id; option.textContent = choice.right; select.append(option); });
                label.append(select); group.append(label);
            });
        } else question.options.forEach(option => {
            const label = doc.createElement('label'), input = doc.createElement('input'); input.type = 'radio'; input.name = `practice_answer_${question.id}`; input.value = option.id;
            label.append(input, doc.createTextNode(option.label)); group.append(label);
        });
        const feedback = doc.createElement('p'); feedback.dataset.questionFeedback = ''; feedback.setAttribute('role', 'status'); group.append(feedback); form.append(group);
    });
    if (button) form.append(button);
}
