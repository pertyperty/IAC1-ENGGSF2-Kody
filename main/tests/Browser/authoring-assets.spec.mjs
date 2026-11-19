import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
        localStorage.setItem('kody-tour-v1', 'dismissed');
        localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true }));
    });
});

async function login(page, role) {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(`browser_${role}`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await expect(page).toHaveURL(/\/(?:dashboard|login\/confirmation)$/);
    if (page.url().endsWith('/login/confirmation')) await page.getByRole('button', { name: 'End previous session and continue' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

test('challenge authoring adds and renumbers test cases after a row is removed @cross-browser', async ({ page }) => {
    await login(page, 'questcreator');
    await page.goto('/create/challenges/new');
    const cases = page.locator('[data-test-cases] [data-test-case]');
    const initial = await cases.count();
    await page.getByRole('button', { name: 'Add test case', exact: true }).click();
    await expect(cases).toHaveCount(initial + 1);
    await cases.last().getByLabel('Input', { exact: true }).fill('1\n2');
    await cases.first().getByRole('button', { name: 'Remove test case', exact: true }).click();
    await expect(cases).toHaveCount(initial);
    await expect(cases.first().getByLabel('Input', { exact: true })).toBeFocused();
    await expect(cases.last().getByLabel('Input', { exact: true })).toHaveAttribute('name', `test_cases[${initial - 1}][input]`);
    await expect(cases.last().getByLabel('Input', { exact: true })).toHaveValue('1\n2');
    await cases.last().getByRole('button', { name: 'Remove test case', exact: true }).focus();
    await page.keyboard.press('Enter');
    await expect(cases).toHaveCount(1);
    await expect(page.getByRole('status').filter({ hasText: 'Keep at least one test case.' })).toBeVisible();
    await expect(cases.last().getByRole('button', { name: 'Remove test case', exact: true })).toBeFocused();
    await page.getByRole('button', { name: 'Add test case', exact: true }).click();
    await cases.last().getByRole('button', { name: 'Remove test case', exact: true }).click();
    await expect(cases.first().getByRole('textbox', { name: 'Input', exact: true })).toBeFocused();
    await audit(page);
});

test('challenge settings reveal invalid fields and preserve drafts and exact test text @cross-browser', async ({ page }) => {
    await login(page, 'questcreator');
    await page.goto('/create/challenges/new?example=sum-two&language=python');
    const form = page.locator('form.challenge-editor');
    const limits = form.locator('details').filter({ has: page.locator('#cpu_time_ms') });
    const access = form.locator('details').filter({ has: page.locator('#price_kb') });
    await expect(limits).not.toHaveAttribute('open');
    await expect(access).not.toHaveAttribute('open');
    const title = `Browser challenge ${Date.now()}`;
    await form.getByLabel('Challenge title', { exact: true }).fill(title);
    const input = form.locator('[data-test-case]').first().getByRole('textbox', { name: 'Input', exact: true });
    const exactInput = '\n 3 4 \n';
    await input.fill(exactInput);
    await limits.locator('summary').click();
    const cpu = form.getByLabel('CPU time per test (milliseconds)', { exact: true });
    await cpu.fill('1');
    await limits.locator('summary').click();
    await form.getByRole('button', { name: 'Save challenge draft', exact: true }).click();
    await expect(limits).toHaveAttribute('open');
    await expect(cpu).toBeFocused();
    await cpu.fill('1000');
    await limits.locator('summary').click();

    await access.locator('summary').click();
    const price = form.locator('#price_kb');
    await price.fill('99999999');
    await access.locator('summary').click();
    await form.getByRole('button', { name: 'Save challenge draft', exact: true }).click();
    await expect(access).toHaveAttribute('open');
    await expect(price).toBeFocused();
    await price.fill('0');
    await access.locator('summary').click();

    // Exercise a server error as well as native browser validation.
    await form.evaluate(element => { element.noValidate = true; });
    await limits.locator('summary').click();
    await cpu.fill('6000');
    await limits.locator('summary').click();
    await form.getByRole('button', { name: 'Save challenge draft', exact: true }).click();
    const error = page.locator('[data-field-error="cpu_time_ms"] a');
    await expect(error).toBeVisible();
    await expect(limits).toHaveAttribute('open');
    await limits.locator('summary').click();
    await error.click();
    await expect(cpu).toBeFocused();
    await expect(input).toHaveValue(exactInput);
    await cpu.fill('1000');
    await limits.locator('summary').click();
    await form.getByRole('button', { name: 'Save challenge draft', exact: true }).click();
    await expect(page).toHaveURL(/\/create\/challenges\/\d+$/);
    await expect(form.getByLabel('Challenge title', { exact: true })).toHaveValue(title);
    await expect(input).toHaveValue(exactInput);
    await expect(cpu).toHaveValue('1000');
    await expect(price).toHaveValue('0');
    await expect(page.getByRole('button', { name: 'Submit saved draft for review', exact: true })).toBeVisible();

    for (const width of [1280, 390]) {
        await page.setViewportSize({ width, height: 900 });
        await expect(form.locator('#draft-save')).toHaveCSS('position', 'static');
        const jumpSize = await page.locator('.editor-jumps a').first().evaluate(element => parseFloat(getComputedStyle(element).fontSize));
        expect(jumpSize).toBeGreaterThanOrEqual(15);
        await audit(page);
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.screenshot({ path: `storage/app/private/creator-clarity-evidence/challenge-${width}.png` });
    }
});

test('preset authoring switches quiz fields and mounts a local game preview @cross-browser', async ({ page }) => {
    await login(page, 'administrator');
    await page.goto('/manage/game-presets/create');
    const form = page.locator('[data-preset-editor]');
    await expect(form.locator('[name="reward_mode"]')).toHaveValue('Deferred');
    await expect(form.getByLabel('Reward mode', { exact: true })).toHaveCount(0);
    await form.getByLabel('Activity type', { exact: true }).selectOption('quiz');
    await expect(form.locator('[data-garden-prompts]')).toBeHidden();
    await expect(form.getByLabel('Game instructions', { exact: true })).toBeDisabled();
    const questions = form.locator('[data-quiz-question-editor]');
    const initial = await questions.count();
    await form.locator('[data-add-question]').click();
    await expect(questions).toHaveCount(initial + 1);
    await form.getByLabel('Activity type', { exact: true }).selectOption('number-machine');
    await expect(form.locator('[data-quiz-prompts]')).toBeHidden();
    await expect(form.getByLabel('Game instructions', { exact: true })).toBeEnabled();
    await form.getByLabel('Activity title', { exact: true }).fill('Number preview');
    await form.getByLabel('Game instructions', { exact: true }).fill('Try an operation and inspect the new value.');
    const writes = [];
    page.on('request', request => { if (request.method() === 'POST') writes.push(request.url()); });
    await form.getByRole('button', { name: 'Preview activity', exact: true }).click();
    const preview = form.locator('[data-scenario-preview-host] [data-arcade-game]');
    await expect(preview).toHaveAttribute('data-play-state', 'ready');
    await expect(preview).not.toHaveAttribute('data-completion-url');
    await preview.getByRole('button', { name: 'Add instruction', exact: true }).click();
    await expect(preview.locator('.trace-source')).toContainText('value =');
    expect(writes).toEqual([]);
    await audit(page);
    for (const width of [1280, 390]) {
        await page.setViewportSize({ width, height: 900 });
        await audit(page);
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.screenshot({ path: `storage/app/private/creator-clarity-evidence/preset-${width}.png` });
    }
});
