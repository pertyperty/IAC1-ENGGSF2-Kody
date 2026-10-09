export function mountChallengeStatus(root) {
    const url = new URL(root.dataset.challengeStatus, window.location.origin);
    if (url.origin !== window.location.origin) return;
    const heading = root.querySelector('[data-attempt-status]');
    const feedback = root.querySelector('[data-attempt-feedback]');
    const counts = root.querySelector('[data-attempt-counts]');
    if (!heading || !feedback || !counts) return;
    let stopped = false;
    let paused = false;
    let inFlight = false;
    let timer;
    let controller;
    let polls = 0;
    const deadline = Date.now() + 900000;
    window.addEventListener('pagehide', () => {
        paused = true;
        clearTimeout(timer);
        controller?.abort();
    });
    window.addEventListener('pageshow', event => {
        if (!event.persisted || stopped) return;
        paused = false;
        if (!inFlight) timer = setTimeout(poll, 0);
    });
    const poll = async () => {
        if (stopped || paused || inFlight) return;
        if (++polls > 900 || Date.now() >= deadline) {
            stopped = true;
            feedback.textContent = 'Automatic updates paused. Use Refresh result to check your saved attempt. Evaluation continues on the server.';
            return;
        }
        inFlight = true;
        controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' },
                credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal });
            if (paused || controller.signal.aborted) return;
            if ([401, 403, 404].includes(response.status)) {
                stopped = true;
                feedback.textContent = 'Sign in again or refresh this page to check your access. Your committed attempt continues on the server.';
                return;
            }
            if (!response.ok) return;
            const result = await response.json();
            if (!['Queued', 'Evaluating', 'Passed', 'Failed', 'Unavailable'].includes(result.status)) return;
            const terminal = ['Passed', 'Failed', 'Unavailable'].includes(result.status);
            if (terminal && (result.completed !== true || typeof result.feedback !== 'string'
                || !Number.isInteger(result.passed_cases) || !Number.isInteger(result.total_cases)
                || result.passed_cases < 0 || result.total_cases < 0 || result.passed_cases > result.total_cases)) return;
            if (!terminal && result.completed !== false) return;
            heading.textContent = result.status;
            if (terminal) {
                feedback.textContent = result.feedback;
                counts.textContent = `${result.passed_cases} / ${result.total_cases} test cases passed.`;
                stopped = true;
                const refresh = root.querySelector('[data-attempt-refresh]');
                if (refresh) refresh.hidden = true;
            }
        } catch {
            // Manual refresh remains available during network interruptions.
        } finally {
            clearTimeout(timeout);
            inFlight = false;
            if (!stopped && !paused) timer = setTimeout(poll, 1000);
        }
    };
    timer = setTimeout(poll, 1000);
}
