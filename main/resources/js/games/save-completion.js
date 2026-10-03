export async function saveCompletion(root, input) {
    if (!root.dataset.completionUrl) return '';
    try {
        const response = await fetch(root.dataset.completionUrl, {
            method: 'POST', credentials: 'same-origin', signal: AbortSignal.timeout(10000),
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(input),
        });
        if (!response.ok) return 'This win wasn’t saved. Check that you’re signed in and this level is unlocked, then try again.';
        const result = await response.json();
        return typeof result.message === 'string' ? result.message : 'Your win was saved.';
    } catch {
        return 'We couldn’t confirm this win was saved. Check your connection and run it again; retries are safe.';
    }
}
