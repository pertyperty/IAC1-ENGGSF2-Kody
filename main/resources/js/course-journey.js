export function journeyValues(value, courseId, origin) {
    if (!value || value.course_id !== courseId || !Number.isInteger(value.completed) || !Number.isInteger(value.total)
        || value.total < 1 || value.completed < 0 || value.completed > value.total || value.current_completed !== true
        || typeof value.finished !== 'boolean' || value.finished !== (value.completed === value.total)) return null;
    let next = null;
    if (value.next !== null) {
        if (!value.next || value.finished || typeof value.next.title !== 'string' || !value.next.title.trim()) return null;
        try {
            const url = new URL(value.next.url, origin);
            if (url.origin !== origin || url.username || url.password || url.search || url.hash
                || !new RegExp(`^/learn/courses/${courseId}/modules/[1-9][0-9]*$`).test(url.pathname)) return null;
            next = { url: url.href, title: value.next.title };
        } catch { return null; }
    }
    return { completed: value.completed, total: value.total, finished: value.finished, next };
}

export function mountCourseJourney(root, doc, win) {
    const courseId = Number(root.dataset.courseJourney);
    if (!Number.isSafeInteger(courseId) || courseId < 1) return;
    doc.addEventListener('kody:feedback', event => {
        if (event.detail?.progressSaved !== true) return;
        const state = journeyValues(event.detail.journey, courseId, win.location.origin);
        if (!state) return;
        root.classList.toggle('journey-finished', state.finished);
        root.querySelector('[data-journey-title]').textContent = state.finished ? 'Journey cleared!' : 'Ready for your next adventure.';
        root.querySelector('[data-journey-count]').textContent = `${state.completed} of ${state.total} adventures completed.`;
        const progress = root.querySelector('[data-journey-progress]');
        progress.value = state.completed; progress.max = state.total;
        root.querySelector('[data-journey-note]').textContent = state.finished
            ? 'All your lesson progress is saved. Discover another journey or revisit a favorite.'
            : state.next ? 'Your win is saved. Your next adventure is ready.' : 'Your win is saved. Open the trail to check the remaining adventures.';
        const next = root.querySelector('[data-journey-next]');
        next.hidden = !state.next;
        if (state.next) {
            next.href = state.next.url;
            next.setAttribute('aria-label', `Next adventure: ${state.next.title}`);
        } else next.removeAttribute('href');
        const overview = root.querySelector('[data-journey-overview]');
        overview.href = state.finished ? `${win.location.origin}/learn/courses` : `${win.location.origin}/learn/courses/${courseId}`;
        overview.textContent = state.finished ? 'Find another journey' : 'View your trail';
    });
}
