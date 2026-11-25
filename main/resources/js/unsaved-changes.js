const AUTHORING_FORMS = 'form[data-module-editor], form[data-preset-editor], form[data-tower-editor], [data-course-composer], [data-test-case-editor]';

export function mountUnsavedChanges(doc, win, { roots = AUTHORING_FORMS, fields = 'input, select, textarea' } = {}) {
    const forms = [...doc.querySelectorAll(roots)]
        .map(root => root.closest('form')).filter((form, index, all) => form && all.indexOf(form) === index);
    if (!forms.length) return;
    // Include unnamed Tower controls, and avoid synthetic empty File timestamps.
    const signature = form => JSON.stringify([...form.querySelectorAll(fields)]
        .filter(field => !field.closest('[data-coding-game], [data-practice-quiz], [data-arcade-game]')).map(field => [
        field.name, field.type, field.type === 'file'
            ? [...field.files].map(file => [file.name, file.size, file.lastModified]) : field.value,
        field.checked ?? false, field.multiple && field.selectedOptions ? [...field.selectedOptions].map(option => option.value) : null,
    ]));
    const initial = new Map(forms.map(form => [form, signature(form)]));
    let submitting = null;
    forms.forEach(form => {
        form.addEventListener('input', () => { submitting = null; });
        form.addEventListener('change', () => { submitting = null; });
        // Mounted after editor validation: rejected/invalid submissions remain protected.
        form.addEventListener('submit', event => { if (event.target === form && !event.defaultPrevented) submitting = form; });
    });
    win.addEventListener('pageshow', () => { submitting = null; });
    win.addEventListener('beforeunload', event => {
        if (!forms.some(form => form !== submitting && signature(form) !== initial.get(form))) return;
        event.preventDefault();
        event.returnValue = '';
    });
}
