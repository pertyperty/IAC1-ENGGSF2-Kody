import { test, expect } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { phpFixture } from '../Support/php-fixture.mjs';
import { audit } from './support/audit.mjs';
import { readableInstruction } from '../../resources/js/games/learning-trace.js';
const tasks = JSON.parse(phpFixture(['-r', 'echo json_encode(require "config/assessment-challenges.php");'], { encoding: 'utf8' }));
const evidence = 'storage/app/private/learning-consolidation-evidence';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => localStorage.setItem('kody-tour-v1', 'dismissed'));
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await mkdir(evidence, { recursive: true });
});

for (const template of ['pixel-studio', 'number-machine', 'sort-lab', 'terminal-quest', 'logic-switchboard', 'string-forge', 'memory-trail']) {
    test(`${template} executes, checks understanding and resets @cross-browser`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/playground?game=${template}`);
        const game = page.locator('[data-arcade-game]');
        const instance = JSON.parse(await game.getAttribute('data-arcade-game'));
        const task = tasks[instance.assessment.id];
        await game.getByText('Edit commands as text', { exact: true }).click();
        await game.getByLabel('Your program', { exact: true }).fill(task.reference.program.map(command => readableInstruction(template, command)).join('\n'));
        await game.getByRole('button', { name: 'Run', exact: true }).click();
        await expect(game.locator('[data-execution-label]')).toHaveText('Goal reached');
        await expect(game.locator('[data-concept-check]')).toBeVisible();
        await game.locator(`[data-concept-check] input[value="${task.check.answer}"]`).check();
        await game.getByRole('button', { name: 'Check answer', exact: true }).click();
        await expect(page.locator('[data-game-result]')).toBeVisible();
        await page.locator('[data-result-return]').click();
        await game.getByRole('button', { name: 'Reset', exact: true }).click();
        await expect(game.locator('[data-instruction-index]')).toHaveCount(0);
        await expect(game.locator('[data-concept-check]')).toBeHidden();
        await expect(game.getByRole('button', { name: 'Run', exact: true })).toBeDisabled();
        expect(errors).toEqual([]);
    });
}

test('desktop instruction scrolling keeps the scene and document stationary @cross-browser', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 800 });
    await page.goto('/playground?game=number-machine');
    const controls = page.getByRole('region', { name: 'Instructions and program' });
    const scene = page.locator('.arcade-scene');
    await controls.getByText('How it works', { exact: true }).click();
    await controls.getByText('Step through program', { exact: true }).click();
    const before = await scene.boundingBox();
    expect(before.y).toBeGreaterThanOrEqual(70);
    expect(before.y + before.height).toBeLessThanOrEqual(800);
    const documentY = await page.evaluate(() => window.scrollY);
    expect(await controls.evaluate(element => element.scrollHeight > element.clientHeight)).toBe(true);
    await controls.evaluate(element => { element.scrollTop = element.scrollHeight; });
    expect(Math.abs((await scene.boundingBox()).y - before.y)).toBeLessThan(2);
    expect(await page.evaluate(() => window.scrollY)).toBe(documentY);
    await page.screenshot({ path: `${evidence}/desktop-workspace.png` });
    await page.setViewportSize({ width: 390, height: 844 });
    expect(await controls.evaluate(element => getComputedStyle(element).overflowY)).toBe('auto');
    await audit(page);
    await page.screenshot({ path: `${evidence}/mobile-workspace.png`, fullPage: true });
});

test('guided games mount without crypto.randomUUID on HTTP development hosts @cross-browser', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.addInitScript(() => Object.defineProperty(globalThis.crypto, 'randomUUID', { value: undefined }));
    await page.goto('/playground?game=number-machine');
    const game = page.locator('[data-arcade-game]');
    await expect(game.getByRole('group', { name: 'Available instructions' })).toBeVisible();
    await game.locator('[data-add-command]').first().click();
    await expect(game.locator('[data-instruction-index]')).toHaveCount(1);
    expect(errors).toEqual([]);
});

// A learner needs to see the target/board before building a program on mobile.
test('mobile games show the goal and board before the program @cross-browser', async ({ page }) => {
    for (const path of ['/playground?game=number-machine', '/playground?game=pixel-studio', '/tower/1']) {
        for (const width of [320, 390, 900]) {
            await page.setViewportSize({ width, height: 900 });
            await page.goto(path);
            const game = page.locator('[data-arcade-game], [data-coding-game]').first();
            const goal = game.locator('.activity-goal');
            const run = game.getByRole('button', { name: 'Run', exact: true });
            const scene = game.locator('.arcade-scene, .game-world');
            expect((await goal.boundingBox()).y).toBeLessThan((await run.boundingBox()).y);
            expect((await goal.boundingBox()).y).toBeLessThan((await scene.boundingBox()).y);
            if (width <= 800) expect((await scene.boundingBox()).y).toBeLessThan((await game.locator('.game-controls').boundingBox()).y);
            else expect((await scene.boundingBox()).x).toBeLessThan((await game.locator('.game-controls').boundingBox()).x);
            await expect(run).toBeDisabled();
            await run.scrollIntoViewIfNeeded();
            await expect(run).toBeInViewport();
            await audit(page);
            await page.screenshot({ path: `${evidence}/mobile-first-${path.includes('pixel') ? 'pixels' : path.includes('tower') ? 'garden' : 'numbers'}-${width}.png`, fullPage: true });
        }
    }
});
