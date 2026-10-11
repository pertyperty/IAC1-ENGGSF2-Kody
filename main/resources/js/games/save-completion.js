import { publishFeedback } from '../feedback.js';

export async function saveCompletion(root, input) {
    if (!root.dataset.completionUrl) {
        root.dispatchEvent?.(new CustomEvent('kody:completed', { bubbles: true, detail: { local: true } }));
        if (!root.closest?.('[data-tower-level]')) publishFeedback(root, 'Objective complete! This is local practice; no account progress or rewards were saved.', 'success', false, null, null, true);
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
            publishFeedback(root, message, 'error', false, null, null, true);
            return message;
        }
        const result = await response.json();
        root.dispatchEvent?.(new CustomEvent('kody:completed', { bubbles: true, detail: result }));
        const message = typeof result.message === 'string' ? result.message : 'Your win was saved.';
        const slug = result.progress?.next_level;
        const next = typeof slug === 'string' && /^[a-z0-9-]{1,40}$/.test(slug) && result.progress?.levels?.[slug]?.unlocked === true
            && /^\/play\/[a-z0-9-]+\/(game|quiz)$/.test(new URL(root.dataset.completionUrl, document.location.origin).pathname)
            ? { label: 'Next lesson', href: `/learn/${slug}` } : null;
        if (!root.closest?.('[data-tower-level]')) publishFeedback(root, message, 'success', true, result.journey ?? null, result.journey ? null : next, true);
        else publishFeedback(root, '', 'success', true); // The tower owns its result dialog; the HUD still refreshes after a save.
        return message;
    } catch {
        const message = 'We couldn’t confirm this win was saved. Check your connection and run it again; retries are safe.';
        publishFeedback(root, message, 'error', false, null, null, true);
        return message;
    }
}
