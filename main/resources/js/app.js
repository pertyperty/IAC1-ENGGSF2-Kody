import { mountModuleEditor } from './module-editor.js';
import { mountPresetEditor } from './preset-editor.js';
import { mountCourseComposer } from './course-composer.js';
import { mountTestCaseEditor } from './test-case-editor.js';
import { mountChallengeStatus } from './challenge-status.js';
import { mountContentReactions } from './content-reactions.js';
import { mountGarden } from './games/command-garden.js';
import { mountQuiz } from './games/choice-quiz.js';

document.querySelectorAll('[data-module-editor]').forEach(mountModuleEditor);
document.querySelectorAll('[data-preset-editor]').forEach(mountPresetEditor);
document.querySelectorAll('[data-course-composer]').forEach(mountCourseComposer);
document.querySelectorAll('[data-test-case-editor]').forEach(mountTestCaseEditor);
document.querySelectorAll('[data-challenge-status]').forEach(mountChallengeStatus);
document.querySelectorAll('[data-content-reactions]').forEach((root) => mountContentReactions(root));

document.querySelectorAll('[data-coding-game]').forEach(mountGarden);
document.querySelectorAll('[data-practice-quiz]').forEach(mountQuiz);

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

const verificationForm = document.querySelector('#verify-link-form');
const verificationInput = document.querySelector('#verification-token');
if (verificationForm && verificationInput && window.location.hash) {
    const token = new URLSearchParams(window.location.hash.slice(1)).get('token');
    // Fragments are never sent to web servers; remove the secret from browser history.
    window.history.replaceState(null, '', window.location.pathname);
    if (token && /^[a-f0-9]{64}$/.test(token)) {
        verificationInput.value = token;
        verificationForm.submit();
    }
}

const recoveryForm = document.querySelector('#recovery-token-form');
const recoveryToken = document.querySelector('#recovery-token');
if (recoveryForm && recoveryToken && window.location.hash) {
    const token = new URLSearchParams(window.location.hash.slice(1)).get('recovery');
    window.history.replaceState(null, '', window.location.pathname);
    if (token && /^[a-f0-9]{64}$/.test(token)) {
        recoveryToken.value = token;
        recoveryForm.submit();
    }
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
