import { mountModuleEditor } from './module-editor.js';
import { mountPresetEditor } from './preset-editor.js';
import { mountCourseComposer } from './course-composer.js';
import { mountTestCaseEditor } from './test-case-editor.js';
import { mountChallengeStatus } from './challenge-status.js';
import { mountContentReactions } from './content-reactions.js';
import { mountGarden } from './games/command-garden.js';
import { mountQuiz } from './games/choice-quiz.js';
import { mountArcade } from './games/arcade-games.js';
import { mountTheme } from './theme.js';
import { mountAccountLinks } from './account-links.js';

mountTheme(document, window);
mountAccountLinks(document, window);

document.querySelectorAll('[data-module-editor]').forEach(mountModuleEditor);
document.querySelectorAll('[data-preset-editor]').forEach(mountPresetEditor);
document.querySelectorAll('[data-course-composer]').forEach(mountCourseComposer);
document.querySelectorAll('[data-test-case-editor]').forEach(mountTestCaseEditor);
document.querySelectorAll('[data-challenge-status]').forEach(mountChallengeStatus);
document.querySelectorAll('[data-content-reactions]').forEach((root) => mountContentReactions(root));

document.querySelectorAll('[data-coding-game]').forEach(mountGarden);
document.querySelectorAll('[data-practice-quiz]').forEach(mountQuiz);
document.querySelectorAll('[data-arcade-game]').forEach(mountArcade);

const accountType = document.querySelector('#account-type');
const instructorFields = document.querySelector('#instructor-fields');

if (accountType && instructorFields) {
    const updateInstructorFields = () => {
        const applying = accountType.value === 'instructor';
        instructorFields.hidden = !applying;
        instructorFields.querySelectorAll('input').forEach((input) => {
            input.disabled = !applying;
            input.required = applying;
        });
    };

    accountType.addEventListener('change', updateInstructorFields);
    updateInstructorFields();
}

const cooldown = document.querySelector('[data-recovery-cooldown]');
if (cooldown) {
    const updateCooldown = () => {
        const seconds = Math.max(0, Number(cooldown.dataset.recoveryCooldown) - Math.floor(Date.now() / 1000));
        cooldown.textContent = seconds > 0 ? `Wait ${seconds} seconds before requesting again.` : 'You can request another recovery email.';
        if (seconds === 0) clearInterval(timer);
    };
    const timer = setInterval(updateCooldown, 1000);
    updateCooldown();
}
