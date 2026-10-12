export function mountNoticeEditor(root) {
    for (const field of ['title', 'message']) {
        const input = root.querySelector(`[data-notice-${field}]`), target = root.querySelector(`[data-notice-preview-${field}]`);
        input.addEventListener('input', () => { target.textContent = input.value || (field === 'title' ? 'Your notice title' : 'Your message will appear here.'); });
    }
}
