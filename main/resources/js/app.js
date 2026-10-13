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
import { mountPasswordFields } from './password-fields.js';
import { mountWorkspaceMenus } from './workspace-menus.js';
import { mountScrollNavigation } from './scroll-navigation.js';
import { mountFeedback } from './feedback.js';
import { mountProgressStrip } from './progress-strip.js';
import { mountCourseJourney } from './course-journey.js';
import { mountTower, mountTowerEditor } from './tower.js';
import { mountNavigationRail } from './navigation-rail.js';
import { mountResourceViewer } from './resource-viewer.js';
import { mountGameResult } from './game-result.js';
import { mountPaymentStatus } from './payment-status.js';
import { mountDocumentStage } from './document-stage.js';
import { mountNoticeEditor } from './site-notice.js';
import { decorateCurrency } from './currency-icons.js';
import { mountRoleUpdate } from './role-update.js';
import { mountKodyCompanion, mountPageLoader } from './kody-companion.js';

mountTheme(document, window);
mountNavigationRail(document, window);
mountTower(document, window);
document.querySelectorAll('[data-resource-workspace]').forEach(root => mountResourceViewer(root, window));
document.querySelectorAll('[data-payment-status]').forEach(root => mountPaymentStatus(root, window));
document.querySelectorAll('[data-document-stage]').forEach(root => mountDocumentStage(root, window));
document.querySelectorAll('[data-notice-editor]').forEach(mountNoticeEditor);
document.querySelectorAll('[data-tower-editor]').forEach(mountTowerEditor);
mountAccountLinks(document, window);
mountPasswordFields(document);
mountWorkspaceMenus(document);
mountFeedback(document, window);
mountGameResult(document, window);
mountProgressStrip(document, window);
document.querySelectorAll('[data-course-journey]').forEach(root => mountCourseJourney(root, document, window));
mountScrollNavigation(document, window);

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

const pageContext = document.querySelector('[data-page-context]');
if (pageContext) pageContext.textContent = document.querySelector('main h1')?.textContent?.trim() ?? '';
mountKodyCompanion(document, window);
document.querySelectorAll('[data-role-update]').forEach(dialog => mountRoleUpdate(dialog, document, window));
mountPageLoader(document, window);
decorateCurrency(document);
document.addEventListener('kody:feedback', () => requestAnimationFrame(() => decorateCurrency(document, document.querySelector('[data-toast-region]'))));
