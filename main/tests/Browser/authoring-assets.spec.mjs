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

test('challenge authoring adds and renumbers checks after a row is removed @cross-browser', async ({ page }) => {
    await login(page, 'questcreator');
    await page.goto('/create/challenges/new');
    const cases = page.locator('[data-test-cases] [data-test-case]');
    const initial = await cases.count();
    await page.getByRole('button', { name: 'Add check', exact: true }).click();
    await expect(cases).toHaveCount(initial + 1);
    await cases.last().getByLabel('Input', { exact: true }).fill('1\n2');
    await cases.first().getByRole('button', { name: 'Remove check', exact: true }).click();
    await expect(cases).toHaveCount(initial);
    await expect(cases.last().getByLabel('Input', { exact: true })).toHaveAttribute('name', `test_cases[${initial - 1}][input]`);
    await expect(cases.last().getByLabel('Input', { exact: true })).toHaveValue('1\n2');
    await audit(page);
});

test('preset authoring switches quiz fields and mounts a local game preview @cross-browser', async ({ page }) => {
    await login(page, 'administrator');
    await page.goto('/manage/game-presets/create');
    const form = page.locator('[data-preset-editor]');
    await form.getByLabel('Game rules', { exact: true }).selectOption('quiz');
    await expect(form.locator('[data-garden-prompts]')).toBeHidden();
    await expect(form.getByLabel('Game instructions', { exact: true })).toBeDisabled();
    const questions = form.locator('[data-quiz-question-editor]');
    const initial = await questions.count();
    await form.locator('[data-add-question]').click();
    await expect(questions).toHaveCount(initial + 1);
    await form.getByLabel('Game rules', { exact: true }).selectOption('number-machine');
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
});
