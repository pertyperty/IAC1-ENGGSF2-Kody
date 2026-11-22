import { mountLivePreviews } from './live-preview.js';
import { mountFormGuidance } from './form-guidance.js';
import { mountChallengeEditor } from './challenge-editor.js';
import { mountChallengeStatus } from './challenge-status.js';
import { mountContentReactions } from './content-reactions.js';
import { mountTheme } from './theme.js';
import { mountAccountLinks } from './account-links.js';
import { mountPasswordFields } from './password-fields.js';
import { mountWorkspaceMenus } from './workspace-menus.js';
import { mountScrollNavigation } from './scroll-navigation.js';
import { mountFeedback } from './feedback.js';
import { mountProgressStrip } from './progress-strip.js';
import { mountCourseJourney } from './course-journey.js';
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
mountFormGuidance(document);
// On narrow reports, begin at the latest periods without scrolling the page.
document.querySelectorAll('.report-trend-chart').forEach(chart => {
    window.requestAnimationFrame(() => { chart.scrollLeft = chart.scrollWidth; });
});
mountNavigationRail(document, window);
document.querySelectorAll('[data-resource-workspace]').forEach(root => mountResourceViewer(root, window));
document.querySelectorAll('[data-payment-status]').forEach(root => mountPaymentStatus(root, window));
document.querySelectorAll('[data-document-stage]').forEach(root => mountDocumentStage(root, window));
document.querySelectorAll('[data-notice-editor]').forEach(mountNoticeEditor);
mountAccountLinks(document, window);
mountPasswordFields(document);
mountWorkspaceMenus(document);
mountFeedback(document, window);
mountGameResult(document, window);
mountProgressStrip(document, window);
document.querySelectorAll('[data-course-journey]').forEach(root => mountCourseJourney(root, document, window));
mountScrollNavigation(document, window);

document.querySelectorAll('[data-challenge-editor]').forEach(mountChallengeEditor);
document.querySelectorAll('[data-challenge-status]').forEach(mountChallengeStatus);
document.querySelectorAll('[data-content-reactions]').forEach((root) => mountContentReactions(root));

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

mountLivePreviews(document, window);
