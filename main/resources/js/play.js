import { mountGameWorkspace } from './games/workspace.js';
import { mountGarden } from './games/command-garden.js';
import { mountQuiz } from './games/choice-quiz.js';
import { mountArcade } from './games/arcade-games.js';

document.querySelectorAll('[data-coding-game]').forEach(mountGarden);
document.querySelectorAll('[data-practice-quiz]').forEach(mountQuiz);
document.querySelectorAll('[data-arcade-game]').forEach(mountArcade);

document.querySelectorAll('[data-coding-game], [data-arcade-game]').forEach(mountGameWorkspace);
