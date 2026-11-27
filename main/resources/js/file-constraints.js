function formatBytes(bytes) {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

export function validateFileSelection(files, {
    maxBytes = 0,
    maxFiles = 1,
    retainedCount = 0,
    allowedTypes = [],
    allowedExtensions = [],
} = {}) {
    const list = [...files];
    if (list.length === 0) {
        return { valid: true, message: '' };
    }
    const totalCount = list.length + retainedCount;
    if (maxFiles > 0 && totalCount > maxFiles) {
        const remaining = Math.max(0, maxFiles - retainedCount);
        return {
            valid: false,
            message: retainedCount > 0
                ? `Too many files selected. Keep at most ${maxFiles} files total (${remaining} slot${remaining === 1 ? '' : 's'} remaining with your kept resources).`
                : `Choose at most ${maxFiles} file${maxFiles === 1 ? '' : 's'}.`,
        };
    }

    const errors = [];
    for (const file of list) {
        const name = file.name || 'Selected file';
        const dot = name.lastIndexOf('.');
        const extension = dot >= 0 ? name.slice(dot + 1).toLowerCase() : '';
        const type = (file.type || '').toLowerCase();

        if (allowedExtensions.length > 0 && !allowedExtensions.includes(extension)) {
            errors.push(`${name}: unsupported file type (.${extension || 'unknown'}). Allowed: ${allowedExtensions.map(e => e.toUpperCase()).join(', ')}.`);
            continue;
        }
        if (allowedTypes.length > 0 && type !== '' && !allowedTypes.includes(type)) {
            errors.push(`${name}: unsupported MIME type (${type}).`);
            continue;
        }
        if (file.size === 0) {
            errors.push(`${name}: file is empty.`);
            continue;
        }
        if (maxBytes > 0 && file.size > maxBytes) {
            errors.push(`${name} (${formatBytes(file.size)}) exceeds the ${formatBytes(maxBytes)} limit.`);
        }
    }

    if (errors.length > 0) {
        return { valid: false, message: errors.join(' ') };
    }

    const summary = list.map(file => `${file.name} (${formatBytes(file.size)})`).join(', ');
    return {
        valid: true,
        message: `Ready to upload: ${summary}`,
    };
}

export function mountFileConstraints(doc) {
    doc.querySelectorAll('input[type="file"][data-file-constraints]').forEach(input => {
        const scope = input.closest('label') || input.parentElement;
        const feedback = input.form?.querySelector(`[data-file-feedback="${input.name}"]`)
            || scope?.querySelector('[data-file-feedback]')
            || scope?.parentElement?.querySelector('[data-file-feedback]');

        const maxBytes = Number(input.dataset.maxBytes || 0);
        const maxFiles = Number(input.dataset.maxFiles || (input.multiple ? 5 : 1));
        const allowedTypes = (input.dataset.allowedTypes || '').split(',').map(v => v.trim().toLowerCase()).filter(Boolean);
        const allowedExtensions = (input.dataset.allowedExtensions || '').split(',').map(v => v.trim().toLowerCase()).filter(Boolean);
        const retainedSelector = input.dataset.retainedSelector || '';

        const evaluate = () => {
            const retainedCount = retainedSelector && input.form
                ? input.form.querySelectorAll(`${retainedSelector}:checked`).length
                : 0;
            const result = validateFileSelection(input.files || [], {
                maxBytes,
                maxFiles,
                retainedCount,
                allowedTypes,
                allowedExtensions,
            });

            input.setCustomValidity(result.valid ? '' : result.message);
            if (!feedback) return;

            if (!result.message) {
                feedback.textContent = '';
                feedback.hidden = true;
                feedback.classList.remove('is-error', 'is-ready');
                feedback.setAttribute('role', 'status');
                return;
            }
            feedback.hidden = false;
            feedback.textContent = result.message;
            feedback.classList.toggle('is-error', !result.valid);
            feedback.classList.toggle('is-ready', result.valid);
            feedback.setAttribute('role', result.valid ? 'status' : 'alert');
        };

        input.addEventListener('change', evaluate);
        if (retainedSelector && input.form) {
            input.form.querySelectorAll(retainedSelector).forEach(box => {
                box.addEventListener('change', evaluate);
            });
        }
    });
}

export function mountCredentialPreviews(doc) {
    doc.querySelectorAll('details[data-credential-preview]').forEach(details => {
        const host = details.querySelector('[data-credential-preview-host]');
        if (!host) return;
        const src = details.dataset.credentialPreviewSrc || '';
        const kind = details.dataset.credentialPreviewKind || '';
        const title = details.dataset.credentialPreviewTitle || 'Supporting credential document';

        const load = () => {
            if (!details.open || host.dataset.loaded === '1' || !src) return;
            host.dataset.loaded = '1';
            host.replaceChildren();
            if (kind === 'pdf') {
                const frame = doc.createElement('iframe');
                frame.className = 'document-frame credential-preview-frame';
                frame.title = `${title} PDF preview`;
                frame.src = src;
                host.append(frame);
                const hint = doc.createElement('p');
                hint.className = 'field-hint';
                hint.textContent = 'If your browser cannot display this PDF inline, use Download above.';
                host.append(hint);
            } else {
                const figure = doc.createElement('figure');
                figure.className = 'credential-preview-figure';
                const img = doc.createElement('img');
                img.className = 'credential-preview-image';
                img.src = src;
                img.alt = title;
                img.loading = 'lazy';
                figure.append(img);
                host.append(figure);
            }
        };

        details.addEventListener('toggle', load);
        if (details.open) load();
    });
}
