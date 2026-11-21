import { mountGameWorkspace } from './games/workspace.js';
import { mountGarden } from './games/command-garden.js';
import { mountQuiz } from './games/choice-quiz.js';
import { mountArcade } from './games/arcade-games.js';

document.querySelectorAll('[data-coding-game]').forEach(mountGarden);
document.querySelectorAll('[data-practice-quiz]').forEach(mountQuiz);
document.querySelectorAll('[data-arcade-game]').forEach(mountArcade);

document.querySelectorAll('[data-coding-game], [data-arcade-game]').forEach(mountGameWorkspace);

// Practice continuation changes difficulty only; it grants no saved completion.
document.addEventListener('kody:completed', event => {
    const page = event.target.closest?.('.arcade-page');
    if (event.detail?.local && page) {
        const next = page.querySelector('[data-practice-next]');
        if (next) next.hidden = false;
    }
});
