export function mountTestCaseEditor(root) {
    const cases = root.querySelector('[data-test-cases]');
    const template = root.querySelector('[data-test-case-template]');
    const feedback = root.querySelector('[data-case-feedback]');
    const limit = Number(root.dataset.maxCases);
    const renumber = () => cases.querySelectorAll('[data-test-case]').forEach((row, index) => {
        row.querySelector('[data-case-number]').textContent = index + 1;
        row.querySelectorAll('[data-case-field]').forEach(field => {
            field.name = `test_cases[${index}][${field.dataset.caseField}]`;
        });
    });
    root.querySelector('[data-add-case]').addEventListener('click', () => {
        if (cases.children.length >= limit) {
            feedback.textContent = `You can add up to ${limit} checks.`;
            return;
        }
        cases.append(template.content.cloneNode(true));
        renumber();
        cases.lastElementChild.querySelector('textarea').focus();
        feedback.textContent = 'Check added.';
    });
    cases.addEventListener('click', event => {
        if (!event.target.closest('[data-remove-case]')) return;
        if (cases.children.length <= 1) {
            feedback.textContent = 'Keep at least one check for your challenge.';
            return;
        }
        event.target.closest('[data-test-case]').remove();
        renumber();
        feedback.textContent = 'Check removed.';
    });
    renumber();
}
