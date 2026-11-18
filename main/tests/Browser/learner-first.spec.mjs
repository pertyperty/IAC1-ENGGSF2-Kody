import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
        localStorage.setItem('kody-tour-v1', 'dismissed');
        localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true }));
    });
});

test('learner-first visual audit', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.goto('/playground?game=number-machine');
    await expect(page.locator('[data-arcade-game]')).toBeVisible();
    await page.screenshot({ path: 'storage/app/browser-results/rework-arcade-after.png', fullPage: true });
    await page.goto('/login');
    await page.getByLabel('Email or username').fill('browser_instructor');
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await page.waitForURL(/\/(?:dashboard|login\/confirmation)$/);
    if (page.url().endsWith('/login/confirmation')) await page.getByRole('button', { name: 'End previous session and continue' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
    await page.goto('/create/modules/new');
    await expect(page.locator('[data-module-editor]')).toBeVisible();
    await page.screenshot({ path: 'storage/app/browser-results/rework-studio-after.png', fullPage: true });
    await audit(page);
});

test('constrained practice gives inline feedback and requires a concept check before completion', async ({ page }) => {
    const writes = [];
    page.on('request', request => { if (request.method() === 'POST') writes.push(request.url()); });
    await page.goto('/playground?game=number-machine');
    const game = page.locator('[data-arcade-game]');
    await expect(game.locator('.activity-goal')).toContainText('at most 4 instructions');
    await game.locator('.program-arrange summary').click();
    await game.getByLabel('Your program', { exact: true }).fill('add 16');
    await game.getByRole('button', { name: 'Run program', exact: true }).click();
    await expect(game.locator('[data-arcade-output]')).toContainText('provided for this task');
    await expect(page.locator('[data-game-result]')).not.toBeVisible();
    await game.getByLabel('Your program', { exact: true }).fill('add 3\nmultiply 2\nsubtract 1\nmultiply 2');
    await game.getByRole('button', { name: 'Run program', exact: true }).click();
    await expect(game.locator('[data-concept-check]')).toBeVisible();
    await expect(game).toHaveAttribute('data-play-state', 'checking');
    await game.getByLabel('17', { exact: true }).check();
    await game.getByRole('button', { name: 'Check answer', exact: true }).click();
    await expect(game.locator('[data-concept-check] [role=status]')).toContainText('Try again');
    await game.getByLabel('21', { exact: true }).check();
    await game.getByRole('button', { name: 'Check answer', exact: true }).click();
    await expect(page.locator('[data-game-result]')).toBeVisible();
    await page.locator('[data-result-return]').click();
    expect(writes).toEqual([]);
    for (const width of [1440, 320]) {
        await page.setViewportSize({ width, height: 1000 });
        await audit(page);
    }
    await page.screenshot({ path: 'storage/app/browser-results/rework-concept-check-mobile.png', fullPage: true });
});

test('an instructor previews and saves a guided assessment without configuring a game world', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill('browser_polishcreator');
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await page.waitForURL(/\/(?:dashboard|login\/confirmation)$/);
    if (page.url().endsWith('/login/confirmation')) await page.getByRole('button', { name: 'End previous session and continue' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
    await page.goto('/create/modules/new');
    await page.getByLabel('Lesson title').fill('Reason about the top of a stack');
    await page.getByLabel('Lesson summary').fill('Use stack operations and explain what TOP means.');
    await page.getByLabel('Explanation and examples').fill('A stack changes at TOP. In [2, 5], 5 is TOP. POP removes 5. DUPLICATE copies TOP.');
    const writes = [];
    page.on('request', request => { if (request.method() === 'POST') writes.push(request.url()); });
    await page.getByLabel('Assessment task').selectOption('repeat-pattern');
    await page.getByRole('button', { name: 'Preview assessment', exact: true }).click();
    const garden = page.locator('[data-guided-preview-host] [data-coding-game]');
    await expect(garden.locator('.activity-goal')).toContainText('3-move pattern repeated twice');
    await expect(garden.locator('[data-game-conditional]').locator('..')).toBeHidden();
    await garden.getByLabel('Repeat my instructions 2 times').check();
    for (const direction of ['right', 'up', 'right']) await garden.getByRole('button', { name: `Add ${direction}`, exact: true }).click();
    await garden.getByRole('button', { name: 'Run program', exact: true }).click();
    await expect(garden.locator('[data-concept-check]')).toBeVisible();
    await garden.getByLabel('6', { exact: true }).check();
    await garden.getByRole('button', { name: 'Check answer', exact: true }).click();
    await expect(page.locator('[data-game-result]')).toBeVisible();
    await page.locator('[data-result-return]').click();
    expect(writes).toEqual([]);
    await page.getByLabel('Assessment task').selectOption('stack-recovery');
    await expect(page.locator('[data-custom-game]')).not.toBeVisible();
    await page.getByRole('button', { name: 'Preview assessment', exact: true }).click();
    await expect(page.locator('[data-guided-preview-host] [data-arcade-game]')).toBeVisible();
    await page.getByRole('button', { name: 'Save draft', exact: true }).click();
    await expect(page).toHaveURL(/\/create\/modules\/\d+$/);
    await expect(page.getByLabel('Assessment task')).toHaveValue('stack-recovery');
    const saved = JSON.parse(await page.locator('.studio-preview [data-arcade-game]').getAttribute('data-arcade-game'));
    expect(saved.assessment.limit).toBe(3);
    expect(saved.assessment.commands).toEqual(['pop', 'swap', 'duplicate']);
    await audit(page);
});
