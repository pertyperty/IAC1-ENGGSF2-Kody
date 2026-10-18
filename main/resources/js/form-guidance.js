export function mountFormGuidance(document) {
    let sequence = 0;
    for (const form of document.querySelectorAll('form')) {
        const fields = [...form.querySelectorAll('input, select, textarea')].filter(field => field.name && field.type !== 'hidden' && !field.closest('[data-coding-game], [data-arcade-game], [data-quiz]'));
        for (const field of fields) {
            if (!field.id) field.id = `form-field-${++sequence}`;
            if (field.getAttribute('aria-describedby') || !['text', 'email', 'textarea', 'number'].includes(field.type)) continue;
            const hints = [];
            if (field.minLength > 0 && field.maxLength > 0) hints.push(`${field.minLength}–${field.maxLength} characters.`);
            else if (field.maxLength > 0) hints.push(`Up to ${field.maxLength} characters.`);
            if (field.type === 'number' && field.min !== '' && field.max !== '') hints.push(`From ${field.min} to ${field.max}.`);
            if (!hints.length) continue;
            const help = document.createElement('small'); help.id = `${field.id}-guide`; help.className = 'field-guide'; help.textContent = hints.join(' ');
            const label = field.closest('label');
            if (label && !field.hasAttribute('aria-label') && !field.hasAttribute('aria-labelledby')) {
                // Preserve both the form's grid cell and its original accessible name.
                const copy = label.cloneNode(true);
                copy.querySelectorAll('input, select, textarea, button, [aria-hidden=true]').forEach(node => node.remove());
                const name = copy.textContent.trim();
                if (name) field.setAttribute('aria-label', name);
            }
            field.insertAdjacentElement('afterend', help); field.setAttribute('aria-describedby', help.id);
        }
        for (const error of document.querySelectorAll('[data-field-error]')) {
            const field = fields.find(item => item.name.replace(/\[([^\]]+)\]/g, '.$1') === error.dataset.fieldError);
            if (!field) continue;
            if (!error.id) error.id = `form-error-${++sequence}`;
            field.setAttribute('aria-invalid', 'true');
            field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
            const link = document.createElement('a'); link.href = `#${field.id}`; link.className = 'quiet-link'; link.textContent = error.textContent;
            link.addEventListener('click', event => { event.preventDefault(); field.focus(); }); error.replaceChildren(link);
        }
    }
}
