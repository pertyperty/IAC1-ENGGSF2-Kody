export function mountChallengeStatus(root) {
    const url = new URL(root.dataset.challengeStatus, window.location.origin);
    if (url.origin !== window.location.origin) return;
    const heading = root.querySelector('[data-attempt-status]');
    const feedback = root.querySelector('[data-attempt-feedback]');
    const counts = root.querySelector('[data-attempt-counts]');
    if (!heading || !feedback || !counts) return;
    let stopped = false;
    let timer;
    let controller;
    let polls = 0;
    const deadline = Date.now() + 900000;
    window.addEventListener('pagehide', () => {
        stopped = true;
        clearTimeout(timer);
        controller?.abort();
    }, { once: true });
    const poll = async () => {
        if (stopped || ++polls > 900 || Date.now() >= deadline) return;
        controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' },
                credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal });
            if ([401, 403, 404].includes(response.status)) { stopped = true; return; }
            if (!response.ok) return;
            const result = await response.json();
            if (!['Queued', 'Evaluating', 'Passed', 'Failed', 'Unavailable'].includes(result.status)) return;
            heading.textContent = result.status;
            if (result.completed === true && typeof result.feedback === 'string'
                && Number.isInteger(result.passed_cases) && Number.isInteger(result.total_cases)) {
                feedback.textContent = result.feedback;
                counts.textContent = `${result.passed_cases} / ${result.total_cases} test cases passed.`;
                stopped = true;
            }
        } catch {
            // Manual refresh remains available during network interruptions.
        } finally {
            clearTimeout(timeout);
            if (!stopped) timer = setTimeout(poll, 1000);
        }
    };
    timer = setTimeout(poll, 1000);
}
