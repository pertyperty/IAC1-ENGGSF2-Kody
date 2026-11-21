import { test, expect } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { audit } from './support/audit.mjs';

const evidence = 'storage/app/private/first-play-2026-11-21';
test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => localStorage.setItem('kody-tour-v1', 'dismissed'));
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await mkdir(evidence, { recursive: true });
});

test('all seven games expose the first action and require a program before Run @cross-browser', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.setViewportSize({ width: 390, height: 844 });
    for (const template of ['pixel-studio', 'number-machine', 'sort-lab', 'terminal-quest', 'logic-switchboard', 'string-forge', 'memory-trail']) {
        await page.goto(`/playground?game=${template}`);
        const game = page.locator('[data-arcade-game]');
        await expect(game.locator('[data-first-play-cue]')).not.toBeEmpty();
        const run = game.getByRole('button', { name: 'Run', exact: true });
        await expect(run).toBeDisabled();
        if (template === 'pixel-studio') {
            const instance = JSON.parse(await game.getAttribute('data-arcade-game'));
            const [x, y, color] = instance.scenario.pixels[0].split(' ');
            // The palette must not be clipped by the bounded state scroller.
            await expect(game.locator('[data-arcade-world] .paint-palette')).toHaveCount(0);
            await expect(game.locator('.arcade-scene > .paint-palette')).toBeVisible();
            await game.getByRole('button', { name: `Paint with ${color}`, exact: true }).click();
            await game.getByRole('button', { name: new RegExp(`^Paint column ${x}, row ${y};`) }).click();
            await expect(game.locator('[data-readable-program] code')).toHaveCount(1);
        } else if (template === 'sort-lab') {
            await game.getByRole('button', { name: /^Select position 1,/ }).click();
            await game.getByRole('button', { name: /^Select position 2,/ }).click();
        } else if (template === 'logic-switchboard') {
            await game.getByRole('button', { name: 'Flip input 1', exact: true }).click();
        } else if (template === 'terminal-quest') {
            await game.getByRole('textbox', { name: 'kody@workspace $', exact: true }).fill('ls');
            await game.getByRole('button', { name: 'Add instruction', exact: true }).click();
        } else {
            const choices = game.getByRole('group', { name: 'Available instructions' });
            await expect(choices).toBeVisible();
            if (template === 'memory-trail') await expect(choices).toContainText('stack.');
            await choices.getByRole('button').first().click();
        }
        await expect(run).toBeEnabled();
        await expect(game.locator('[data-execution-label]')).toHaveText('Preview');
        await game.getByRole('button', { name: 'Reset', exact: true }).click();
        await expect(run).toBeDisabled();
        await audit(page);
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.screenshot({ path: `${evidence}/${template}-390.png`, fullPage: true });
    }
    expect(errors).toEqual([]);
});

test('Garden shows its board before moves and keeps optional help out of the first action @cross-browser', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 740 });
    await page.goto('/tower/1');
    const game = page.locator('[data-coding-game]');
    const run = game.getByRole('button', { name: 'Run', exact: true });
    await expect(run).toBeDisabled();
    await expect(game.locator('.first-play-cue')).toContainText('Kody moves when you press Run');
    const board = await game.locator('.game-board').boundingBox();
    const moves = await game.locator('.command-buttons').boundingBox();
    expect(board.y + board.height).toBeLessThan(moves.y);
    await game.getByRole('button', { name: 'Add right', exact: true }).click();
    await expect(run).toBeEnabled();
    await expect(game.locator('[data-readable-program]')).toContainText('kody.move_right()');
    await audit(page);
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: `${evidence}/garden-320.png`, fullPage: true });
});

test('mobile learning and account pages retain readable filters and comfortable actions', async ({ page }) => {
    for (const path of ['/help', '/learn', '/learn/courses', '/challenges', '/register', '/login']) {
        await page.setViewportSize({ width: 320, height: 740 });
        await page.goto(path);
        expect((await page.title()).toLowerCase()).not.toContain('not found');
        await audit(page);
        await page.screenshot({ path: `${evidence}/${path.replaceAll('/', '-') || 'home'}-320.png`, fullPage: true });
    }
});
