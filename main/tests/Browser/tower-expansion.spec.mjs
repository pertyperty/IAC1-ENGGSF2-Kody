import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => { localStorage.setItem('kody-tour-v1', 'dismissed'); localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true })); });
});

async function login(page, name) {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(`browser_${name}`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
}

async function answerQuiz(quiz) {
    const instance = JSON.parse(await quiz.getAttribute('data-practice-quiz'));
    for (const question of instance.questions) {
        const group = quiz.locator(`[data-quiz-question="${question.id}"]`);
        if (question.type === 'blank') await group.locator('input').fill(question.accepted[0]);
        else if (question.type === 'matching') for (const pair of question.pairs) await group.locator(`[data-match-answer="${pair.id}"]`).selectOption(pair.id);
        else await group.locator(`input[value="${question.answer}"]`).check();
    }
}

test('Expert map resumes the chosen track and a mixed quiz saves exactly the verified stage', async ({ page }) => {
    await login(page, 'expertlearner');
    await page.getByText('Choose a skill track', { exact: true }).click();
    await page.getByRole('link', { name: /^Expert/ }).click();
    await expect(page.locator('.tower-track-status')).toContainText('Expert');
    await expect(page.locator('[data-tower-continue]')).toContainText('Start level 71');
    await expect(page.locator('[data-tower-stop="71"] .tower-node')).toBeInViewport();
    for (const width of [1440, 390]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.getByText('Choose a skill track', { exact: true }).click();
        await audit(page);
        await page.getByText('Choose a skill track', { exact: true }).click();
    }
    await page.locator('[data-tower-continue]').click();
    const quiz = page.locator('[data-practice-quiz]');
    await expect(quiz.locator('.lesson-briefing')).toContainText('loop invariant');
    const submissions = [];
    page.on('request', request => { if (request.method() === 'POST' && request.url().includes('/tower/')) submissions.push(request.url()); });
    await quiz.getByRole('button', { name: 'Check my idea' }).click();
    await expect(page.locator('[data-game-result]')).toBeVisible();
    expect(submissions).toHaveLength(0);
    await page.locator('[data-result-return]').click();
    await answerQuiz(quiz);
    const response = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('/tower/'));
    await quiz.getByRole('button', { name: 'Check my idea' }).click();
    expect((await response).status()).toBe(200);
    await expect(page.locator('[data-game-result]')).toBeVisible();
    await expect(page.locator('[data-progress-level]')).toContainText('1 / 80');
    await page.locator('[data-result-return]').click();
    await expect(page.locator('[data-tower-victory]')).toBeVisible();
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/expert-lesson-mobile.png', fullPage: true });
    await page.getByRole('link', { name: 'Level map', exact: false }).click();
    await expect(page.locator('[data-tower-continue]')).toContainText('Continue level 72');
    await page.reload();
    await expect(page.locator('[data-tower-continue]')).toContainText('Continue level 72');
    await expect(page.locator('[data-tower-stop="1"]')).not.toHaveClass(/is-cleared/);
});

test('Administrator filters advanced floors and edits teaching notes through isolated previews and publication', async ({ page }) => {
    await login(page, 'experteditor');
    await page.goto('/manage/tower?track=expert');
    await expect(page.locator('.tower-editor-card')).toHaveCount(10);
    await page.getByRole('link', { name: /LEVEL 71/ }).click();
    const editor = page.locator('[data-tower-editor]');
    await editor.getByText('Customize stage 1', { exact: true }).click();
    const original = JSON.parse(await editor.locator('[name="stages_json"]').inputValue())[0].briefing;
    await editor.getByRole('button', { name: 'Remove custom teaching notes' }).click();
    await expect(editor.getByLabel('Explain the rule', { exact: true })).toHaveCount(0);
    await editor.getByRole('button', { name: 'Add teaching notes' }).click();
    for (const [key, label] of Object.entries({ outcome: 'Stage learning objective', explanation: 'Explain the rule', example: 'Different worked example', task: 'Learner task', success: 'Success criteria' })) {
        await editor.getByLabel(label, { exact: true }).fill(original[key]);
    }
    await editor.getByLabel('Explain the rule', { exact: true }).fill('Reviewed teaching note: preserve a shrinking half-open interval and its boundary invariant.');
    let mutations = 0;
    page.on('request', request => { if (['POST', 'PUT', 'PATCH'].includes(request.method()) && request.url().includes('/tower')) mutations++; });
    await editor.getByRole('button', { name: 'Try unsaved stage' }).click();
    await expect(editor.locator('.tower-draft-preview .lesson-briefing')).toContainText('Reviewed teaching note');
    expect(mutations).toBe(0);
    for (const width of [1440, 390]) { await page.setViewportSize({ width, height: 1000 }); await audit(page); }
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: 'storage/app/browser-results/expert-studio-mobile.png' });
    await editor.getByRole('button', { name: 'Publish level revision' }).click();
    await expect(page.locator('[data-flash-toast]')).toContainText('Tower revision published');
    await page.locator('[data-tower-editor]').getByText('Customize stage 1', { exact: true }).click();
    await expect(page.getByLabel('Explain the rule', { exact: true })).toHaveValue('Reviewed teaching note: preserve a shrinking half-open interval and its boundary invariant.');
});
