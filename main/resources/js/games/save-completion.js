import { publishFeedback } from '../feedback.js';

export async function saveCompletion(root, input) {
    if (!root.dataset.completionUrl) {
        root.dispatchEvent?.(new CustomEvent('kody:completed', { bubbles: true, detail: { local: true } }));
        return '';
    }
    try {
        const response = await fetch(root.dataset.completionUrl, {
            method: 'POST', credentials: 'same-origin', redirect: 'error', signal: AbortSignal.timeout(10000),
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(input),
        });
        if (!response.ok) {
            const message = 'This win wasn’t saved. Check that you’re signed in and this level is unlocked, then try again.';
            publishFeedback(root, message, 'error');
            return message;
        }
        const result = await response.json();
        root.dispatchEvent?.(new CustomEvent('kody:completed', { bubbles: true, detail: result }));
        const message = typeof result.message === 'string' ? result.message : 'Your win was saved.';
        publishFeedback(root, message, 'success', true, result.journey ?? null);
        return message;
    } catch {
        const message = 'We couldn’t confirm this win was saved. Check your connection and run it again; retries are safe.';
        publishFeedback(root, message, 'error');
        return message;
    }
}
