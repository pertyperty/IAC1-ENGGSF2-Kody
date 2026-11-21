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
        root.querySelector('[data-journey-title]').textContent = state.finished ? 'Course complete' : 'Your next module is ready.';
        root.querySelector('[data-journey-count]').textContent = `${state.completed} of ${state.total} modules completed.`;
        const progress = root.querySelector('[data-journey-progress]');
        progress.value = state.completed; progress.max = state.total;
        root.querySelector('[data-journey-note]').textContent = state.finished
            ? 'Your progress is saved. Review a module or find another course.'
            : state.next ? 'Your progress is saved. Your next module is ready.' : 'Your progress is saved. View the course to check your remaining modules.';
        const next = root.querySelector('[data-journey-next]');
        next.hidden = !state.next;
        if (state.next) {
            next.href = state.next.url;
            next.setAttribute('aria-label', `Next module: ${state.next.title}`);
        } else next.removeAttribute('href');
        const overview = root.querySelector('[data-journey-overview]');
        overview.href = state.finished ? `${win.location.origin}/learn/courses` : `${win.location.origin}/learn/courses/${courseId}`;
        overview.textContent = state.finished ? 'Find another course' : 'View course';
    });
}
