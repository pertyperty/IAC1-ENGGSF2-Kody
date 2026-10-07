export function mountPasswordFields(document) {
    document.querySelectorAll('[data-password-field]').forEach((field) => {
        const input = field.querySelector('input');
        const button = field.querySelector('[data-password-toggle]');
        const label = field.querySelector('label').textContent.trim().toLowerCase();
        button.hidden = false;
        button.addEventListener('click', () => {
            const shown = input.type === 'password';
            input.type = shown ? 'text' : 'password';
            button.textContent = shown ? 'Hide' : 'Show';
            button.setAttribute('aria-pressed', String(shown));
            button.setAttribute('aria-label', `${shown ? 'Hide' : 'Show'} ${label}`);
        });
    });
}
