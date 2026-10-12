import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { audit } from './support/audit.mjs';

const manifest = () => JSON.parse(readFileSync(process.env.KODY_BROWSER_MANIFEST, 'utf8'));
test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => localStorage.setItem('kody-tour-v1', 'dismissed'));
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});

async function login(page, name) {
    await page.goto('/login'); await page.getByLabel('Email or username').fill(`browser_${name}`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!'); await page.getByRole('button', { name: /Sign in/ }).click();
}

async function guide(page, title) {
    await page.getByRole('button', { name: 'Kody guide and companion' }).click();
    const tour = page.locator('[data-kody-tour]');
    await expect(tour.locator('#tour-title')).toContainText(await page.locator('main h1').textContent());
    await tour.locator('[data-tour-next]').click();
    const titles = [];
    for (let step = 0; step < 20 && await tour.isVisible(); step++) {
        const current = await tour.locator('#tour-title').textContent(); titles.push(current);
        if (current === title) break;
        await tour.locator('[data-tour-next]').click();
    }
    expect(titles).toContain(title); expect(titles).not.toContain('Your way home'); expect(titles).not.toContain('One discovery at a time');
    expect(Number(await tour.locator('[data-tour-hole]').getAttribute('width'))).toBeGreaterThan(30);
    await audit(page); await tour.locator('[data-tour-skip]').click();
}

test('favicon and page guides stay specific across Help Arcade weekly quests wallet and profile', async ({ page }) => {
    test.setTimeout(120000);
    await page.goto('/help');
    await expect(page.locator('header .brand-mark')).toHaveAttribute('src', /head\.webp$/);
    const icon = page.locator('link[rel="icon"][sizes="32x32"]');
    const image = await page.request.get(await icon.getAttribute('href')); expect(image.status()).toBe(200);
    expect((await image.body()).subarray(0, 8).toString('hex')).toBe('89504e470d0a1a0a');
    expect(await page.evaluate(async () => { const img = new Image(); img.src = document.querySelector('link[rel="icon"][sizes="32x32"]').href; await img.decode(); return [img.naturalWidth, img.naturalHeight]; })).toEqual([32, 32]);
    await guide(page, 'Find a helpful answer');
    await page.goto('/playground'); await guide(page, 'Choose your game cabinet');
    await login(page, 'questlearner');
    await page.goto('/weekly'); await guide(page, 'This week’s shared adventure');
    await page.setViewportSize({ width: 1920, height: 1000 }); await page.goto('/weekly'); await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/weekly-lobby-wide.png', fullPage: true });
    await page.goto(`/weekly/${manifest().weekly}`); await expect(page.locator('[data-execution-pending]')).toContainText('Live code execution: setup pending');
    await expect(page.getByRole('button', { name: 'Submission unavailable' })).toBeDisabled();
    await guide(page, 'Execution setup is pending'); await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/weekly-quest-wide.png', fullPage: true });
    await page.goto(`/challenges/${manifest().challenge}`); await expect(page.locator('[data-execution-pending]')).toBeVisible();
    await page.setViewportSize({ width: 320, height: 740 }); await audit(page);
    await page.goto('/weekly/leaderboard'); await expect(page.getByRole('heading', { name: 'Weekly leaderboard.' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'The first weekly podium is waiting' })).toBeVisible(); await guide(page, 'Choose a completed week'); await audit(page);
    await page.goto('/wallet'); await guide(page, 'Know what is available');
    await page.goto('/account'); await guide(page, 'Make your profile yours');
});

test('arcade lobby quiz cabinet automatically checks choices blanks and matching without mutation requests', async ({ page }) => {
    const writes = []; page.on('request', request => { if (!['GET', 'HEAD'].includes(request.method())) writes.push(request.method()); });
    await page.setViewportSize({ width: 1920, height: 1000 }); await page.goto('/playground?game=quiz-lab');
    await expect(page.locator('.arcade-cabinet')).toHaveCount(8); await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/arcade-cabinet-lobby.png', fullPage: true });
    const quiz = page.locator('[data-practice-quiz]');
    await quiz.getByLabel('True', { exact: true }).check(); await quiz.getByLabel('Your short answer').fill(' IF ');
    for (const [label, value] of [['Loop', 'loop'], ['Variable', 'variable'], ['Condition', 'condition']]) await quiz.getByLabel(label, { exact: true }).selectOption(value);
    await page.setViewportSize({ width: 320, height: 740 }); await audit(page);
    await quiz.getByRole('button', { name: 'Check my idea' }).click();
    const result = page.locator('[data-game-result]'); await expect(result).toBeVisible(); await expect(result.locator('[data-result-message]')).toContainText('local practice');
    await audit(page); await result.locator('[data-result-return]').click();
    await expect(quiz.getByRole('button', { name: 'Check my idea' })).toBeFocused(); expect(writes).toEqual([]);
});

test('creator mixed quiz preview is editable and learner completion is saved by the server', async ({ browser }) => {
    test.setTimeout(120000);
    for (const role of ['quizcreator', 'quizlearner']) {
        const context = await browser.newContext(); const page = await context.newPage();
        await page.addInitScript(() => localStorage.setItem('kody-tour-v1', 'dismissed'));
        await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
        try {
            await login(page, role);
            if (role === 'quizcreator') {
                await page.goto('/create/modules/new?example=quiz-lab');
                const editor = page.locator('[data-quiz-editor]');
                await expect(editor.locator('[data-question-field="type"]')).toHaveCount(3);
                await expect(editor.locator('[data-question-field="type"]').nth(1)).toHaveValue('blank');
                await expect(editor.locator('[data-question-field="type"]').nth(2)).toHaveValue('matching');
                await editor.locator('[data-question-toggle]').nth(2).click(); await editor.locator('[data-add-pair]').nth(2).click();
                const pair = editor.locator('[data-quiz-question-editor]').nth(2).locator('[data-matching-pair]').last();
                await pair.locator('[data-pair-field="left"]').fill('Sequence'); await pair.locator('[data-pair-field="right"]').fill('Runs in order');
                await page.locator('[data-quiz-preview-button]').click();
                const preview = page.locator('[data-quiz-preview-host] [data-practice-quiz]'); await expect(preview.locator('[data-match-answer]')).toHaveCount(4);
                await expect(preview).not.toHaveAttribute('data-completion-url');
                await page.setViewportSize({ width: 320, height: 740 }); await audit(page);
                await page.getByRole('button', { name: /Save draft/ }).click();
                await expect(page).toHaveURL(/\/create\/modules\/\d+$/); await expect(page.locator('[data-quiz-question-editor]').nth(2).locator('[data-matching-pair]')).toHaveCount(4);
            } else {
                await page.goto(`/learn/modules/${manifest().modules['quiz-lab']}`);
                const quiz = page.locator('[data-practice-quiz]'); await expect(quiz).toHaveAttribute('data-completion-url', /\/quiz$/);
                await quiz.getByLabel('True', { exact: true }).check(); await quiz.getByLabel('Your short answer').fill(' IF ');
                for (const [label, value] of [['Loop', 'loop'], ['Variable', 'variable'], ['Condition', 'condition']]) await quiz.getByLabel(label, { exact: true }).selectOption(value);
                await quiz.getByRole('button', { name: 'Check my idea' }).click();
                await expect(page.locator('[data-game-result]')).toBeVisible(); await expect(quiz.locator('[data-quiz-feedback]')).toContainText(/saved/i);
                await page.locator('[data-result-return]').click(); await page.goto('/learn?q=Choose%2C%20recall'); await expect(page.locator('.module-card').filter({ hasText: 'Choose, recall and connect' }).locator('.saved-progress')).toContainText('Completed');
            }
        } finally { await context.close(); }
    }
});
