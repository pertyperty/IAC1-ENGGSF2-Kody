import { test, expect } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { audit } from './support/audit.mjs';
const evidence = 'storage/app/private/game-workspace-evidence';
test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => localStorage.setItem('kody-tour-v1', 'dismissed'));
    await mkdir(evidence, { recursive: true });
});

test('registration legal links stay in the current tab @cross-browser', async ({ page, context }) => {
    await page.goto('/register');
    for (const title of ['Terms and conditions', 'Privacy notice']) {
        const link = page.getByRole('link', { name: title, exact: true });
        expect(await link.getAttribute('target')).toBeNull();
        await link.click();
        await expect(page.getByRole('heading', { name: title, exact: true })).toBeVisible();
        expect(context.pages()).toHaveLength(1);
        await page.goBack();
        await expect(page.locator('input[name=terms_accepted]')).not.toBeChecked();
    }
    await expect(page.locator('input[name=terms_accepted]')).toHaveAttribute('required', '');
});

test('the arcade has one editable pseudocode list and a stable Run position @cross-browser', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.setViewportSize({ width: 1280, height: 1000 });
    await page.goto('/playground?game=number-machine');
    const game = page.locator('[data-arcade-game]');
    const source = game.locator('.trace-source');
    await expect(source).toHaveCount(1);
    const run = game.getByRole('button', { name: 'Run', exact: true });
    const sourceBefore = await source.boundingBox();
    const runBefore = await run.boundingBox();
    const commands = ['add 3', 'multiply 2', 'subtract 1', 'multiply 2'];
    const writes = [];
    page.on('request', request => { if (request.method() === 'POST') writes.push(request.url()); });
    for (const command of commands) {
        await game.locator(`[data-add-command="${command}"]`).click();
    }
    await expect(source.locator('code')).toHaveCount(4);
    await expect(source).toContainText('value = value + 3');
    await expect(game.locator('[data-execution-label]')).toHaveText('Preview');
    expect(Math.abs((await source.boundingBox()).height - sourceBefore.height)).toBeLessThan(2);
    expect(Math.abs((await run.boundingBox()).y - runBefore.y)).toBeLessThan(2);
    await page.screenshot({path: `${evidence}/after-number-machine.png`, fullPage: true});
    await run.click();
    await expect(game.locator('[data-concept-check]')).toBeVisible();
    await expect(game.locator('[data-execution-label]')).toHaveText('Goal reached');
    await game.getByRole('button', { name: 'Remove step 2', exact: true }).click();
    await expect(game.getByRole('button', { name: 'Remove step 2', exact: true })).toBeFocused();
    await expect(game.locator('[data-concept-check]')).toBeHidden();
    await expect(game.locator('[data-arcade-output]')).toContainText('Program changed');
    await expect(game.locator('[data-execution-label]')).toHaveText('Preview');
    await game.getByText('Step through program', { exact: true }).click();
    await game.getByRole('button', { name: 'Step', exact: true }).click();
    await expect(source.locator('[aria-current=step]')).toHaveCount(1);
    await expect(game.locator('[data-execution-label]')).toHaveText('Preview step 1');
    await expect(game.locator('.trace-state')).toContainText('value = 5');
    expect(writes).toEqual([]);
    await game.getByRole('button', { name: 'Reset', exact: true }).click();
    await expect(source.locator('[data-instruction-index]')).toHaveCount(0);
    await expect(game.locator('[data-execution-label]')).toHaveText('Not run');
    await expect(game.getByRole('button', { name: 'Step', exact: true })).toBeDisabled();
    await audit(page);
});

test('Garden instructions are readable, editable and bounded @cross-browser', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 1000 });
    await page.goto('/tower/1');
    const game = page.locator('[data-coding-game]');
    const source = game.locator('.trace-source');
    await expect(game.getByRole('heading', { name: 'Pseudocode', exact: true })).toBeVisible();
    const before = await source.boundingBox();
    const runBefore = await game.getByRole('button', { name: 'Run', exact: true }).boundingBox();
    const limit = JSON.parse(await game.getAttribute('data-coding-game')).maxCommands;
    for (let i = 0; i < limit; i++) await game.getByRole('button', { name: 'Add right', exact: true }).click();
    await expect(source.locator('code')).toHaveText(Array(limit).fill('kody.move_right()'));
    expect(Math.abs((await source.boundingBox()).height - before.height)).toBeLessThan(2);
    expect(Math.abs((await game.getByRole('button', { name: 'Run', exact: true }).boundingBox()).y - runBefore.y)).toBeLessThan(2);
    await source.getByRole('button', { name: 'Step 2: right', exact: true }).focus();
    await page.keyboard.press('Delete');
    await expect(source.locator('code')).toHaveCount(limit - 1);
    await expect(source.getByRole('button', { name: 'Step 2: right', exact: true })).toBeFocused();
    await expect(game.locator('[data-execution-label]')).toHaveText('Not run');
    await game.getByText('Edit steps', { exact: true }).click();
    const undo = game.getByRole('button', { name: 'Remove last instruction', exact: true });
    expect((await undo.boundingBox()).height).toBeGreaterThanOrEqual(44);
    await undo.click();
    await expect(source.locator('code')).toHaveCount(limit - 2);
    await audit(page);
    await page.screenshot({path: `${evidence}/after-garden.png`, fullPage: true});
});

test('every arcade scene remains readable on desktop, mobile and dark theme', async ({ page }) => {
    for (const template of ['pixel-studio', 'number-machine', 'sort-lab', 'terminal-quest', 'logic-switchboard', 'string-forge', 'memory-trail']) {
        await page.goto(`/playground?game=${template}`);
        const game = page.locator('[data-arcade-game]');
        await expect(game).toBeVisible();
        if (template === 'memory-trail') {
            await expect(game.locator('.stack-top')).toHaveCount(2);
            await expect(game.locator('.stack-top').first()).toContainText('TOP');
        }
        for (const width of [1280, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            await expect(game.locator('[data-readable-program]')).toBeVisible();
            await audit(page);
            await page.screenshot({path: `${evidence}/after-${template}-${width}.png`, fullPage: true});
        }
    }
    await page.locator('[data-theme-toggle]').click();
    await audit(page);
    await page.screenshot({path: `${evidence}/after-stack-dark.png`, fullPage: true});
});


test('practice activity switching remains an explicit form action', async ({ page }) => {
    await page.goto('/playground?game=number-machine');
    await expect(page.getByRole('combobox', { name: 'Choose a concept' })).toBeHidden();
    await page.getByText('Change activity', { exact: true }).click();
    await page.getByRole('combobox', { name: 'Choose a concept' }).selectOption('memory-trail');
    await page.getByRole('button', { name: 'Open activity', exact: true }).click();
    await expect(page.locator('[data-arcade-game]')).toHaveAttribute('data-arcade-game', /memory-trail/);
});
