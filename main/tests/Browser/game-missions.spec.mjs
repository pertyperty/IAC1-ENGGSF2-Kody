import { test, expect } from '@playwright/test';
import { spawnSync } from 'node:child_process';
import { mkdir } from 'node:fs/promises';
import { audit } from './support/audit.mjs';

const fixture = spawnSync(process.env.KODY_TEST_PHP, ['tests/Support/practice-evaluation.php'], { encoding: 'utf8', windowsHide: true });
if (fixture.status !== 0) throw new Error('Practice reference fixture failed.');
const cases = JSON.parse(fixture.stdout);
const evidence = 'storage/app/private/game-overhaul-2026-11-21';
test.beforeEach(async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.addInitScript(() => localStorage.setItem('kody-tour-v1', 'dismissed'));
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
    await mkdir(evidence, { recursive: true });
});

for (const level of ['warmup', 'challenge', 'stretch']) {
    test(`all ten ${level} missions are playable through their real controls @cross-browser`, async ({ page }, testInfo) => {
        test.setTimeout(180_000);
        const errors = []; page.on('pageerror', error => errors.push(error.message));
        await page.setViewportSize({ width: 390, height: 844 });
        for (const { template, game, input } of cases.filter(item => item.level === level)) {
            await page.goto(`/playground?game=${template}&level=${level}`);
            const root = page.locator('[data-arcade-game], [data-coding-game]');
            await expect(root.locator('[data-mission-progress]')).not.toBeEmpty();
            await expect(root.getByRole('button', { name: 'Run', exact: true })).toBeDisabled();
            for (const command of input.program) {
                if (game.template === 'command-garden') await root.getByRole('button', { name: `Add ${command}`, exact: true }).click();
                else if (game.assessment.commands.length) await root.locator(`[data-add-command="${command}"]`).click();
                else if (game.template === 'pixel-studio') {
                    const [, x, y, color] = command.split(' ');
                    await root.getByRole('button', { name: `Paint with ${color}`, exact: true }).click();
                    await root.getByRole('button', { name: new RegExp(`^Paint column ${x}, row ${y};`) }).click();
                } else if (game.template === 'sort-lab') {
                    const [, first, second] = command.split(' ');
                    for (const position of [first, second]) await root.getByRole('button', { name: new RegExp(`^Select position ${position},`) }).click();
                } else if (game.template === 'logic-switchboard') await root.getByRole('button', { name: `Flip input ${command.split(' ')[1]}`, exact: true }).click();
                else {
                    await root.getByRole('textbox', { name: 'kody@workspace $', exact: true }).fill(command);
                    await root.getByRole('button', { name: 'Add instruction', exact: true }).click();
                }
            }
            if (input.repeat) await root.locator('[data-game-repeat]').check();
            if (input.conditional) await root.locator('[data-game-conditional]').check();
            if (level === 'challenge') {
                await audit(page);
                await page.evaluate(() => window.scrollTo(0, 0));
                await page.screenshot({ path: `${evidence}/${template}-${testInfo.project.name}-390.png`, fullPage: true });
            }
            await root.getByRole('button', { name: 'Run', exact: true }).click();
            const check = root.locator('[data-concept-check]');
            await expect(check).toBeVisible();
            await check.locator(`input[value="${game.assessment.check.answer}"]`).check();
            await root.getByRole('button', { name: 'Check answer', exact: true }).click();
            await expect(page.locator('[data-game-result]')).toBeVisible();
            await page.locator('[data-result-return]').click();
            await root.getByRole('button', { name: 'Reset', exact: true }).click();
            await expect(root.getByRole('button', { name: 'Run', exact: true })).toBeDisabled();
        }
        expect(errors).toEqual([]);
    });
}

test('instruction buttons, Undo and keyboard reordering preserve the program @cross-browser', async ({ page }) => {
    await page.goto('/playground?game=number-machine');
    const root = page.locator('[data-arcade-game]');
    await root.locator('[data-add-command="add 3"]').click();
    await root.locator('[data-add-command="multiply 2"]').click();
    const rows = root.locator('[data-arcade-program] > li');
    await rows.first().focus(); await page.keyboard.press('Alt+ArrowDown');
    await expect(rows.first()).toContainText('value = value * 2');
    await expect(rows.nth(1)).toBeFocused();
    await root.getByRole('button', { name: 'Undo', exact: true }).click();
    await expect(rows).toHaveCount(1);
    await expect(root.locator('.value-journey b')).toHaveText('4');
    await audit(page);
});

test('quiz navigation tracks answers and clears stale feedback on edits @cross-browser', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 740 });
    await page.goto('/playground?game=quiz-lab');
    const quiz = page.locator('[data-practice-quiz]');
    await expect(quiz.locator('[data-quiz-navigation]')).toContainText('0 of 3 answered');
    await quiz.locator('[data-quiz-question="repeat"] input[value="false"]').check();
    await expect(quiz.locator('[data-quiz-navigation]')).toContainText('1 of 3 answered');
    await quiz.getByRole('button', { name: 'Check answers', exact: true }).click();
    await expect(quiz.locator('[data-quiz-question="repeat"]')).toHaveClass(/question-retry/);
    await quiz.locator('[data-quiz-question="repeat"] input[value="true"]').check();
    await expect(quiz.locator('[data-quiz-question="repeat"] [data-question-feedback]')).toBeEmpty();
    await quiz.getByRole('button', { name: 'Go to question 2, unanswered' }).click();
    await expect(quiz.locator('[data-blank-answer]')).toBeFocused();
    await audit(page);
    await page.screenshot({ path: `${evidence}/quiz-320.png`, fullPage: true });
});

test('focused games fit the screen, keep the board still and preserve the program @cross-browser', async ({ page }, testInfo) => {
    for (const [width, height] of [[1440, 900], [390, 844], [320, 740]]) {
        await page.setViewportSize({ width, height });
        await page.goto('/playground?game=number-machine');
        const game = page.locator('[data-arcade-game][data-focus-surface="game"]');
        await expect(game).toBeVisible();
        await game.locator('[data-add-command="add 3"]').click();
        const scene = game.locator('.arcade-scene');
        const boardTop = () => scene.evaluate((element, stacked) => element.getBoundingClientRect().top + (stacked ? window.scrollY : 0), width <= 800);
        const beforeOpening = await boardTop();
        const controls = game.locator('.game-controls');
        await game.getByText('Step through program', { exact: true }).click();
        // On stacked layouts, bringing the controls into view can scroll the page.
        if (width <= 800) expect(Math.abs(await boardTop() - beforeOpening)).toBeLessThan(2);
        const beforeScrolling = await boardTop();
        await controls.evaluate(element => { element.scrollTop = element.scrollHeight; });
        await expect.poll(async () => Math.abs(await boardTop() - beforeScrolling)).toBeLessThan(2);
        await expect(scene).toBeVisible();
        await expect(scene.getByText('START', { exact: true })).toBeVisible();
        await expect(game.locator('[data-readable-program] code')).toHaveText('value = value + 3');
        await audit(page);
        await page.screenshot({ path: `${evidence}/focus-${width}-${testInfo.project.name}.png` });
    }
});

test('the arcade shows one title and keeps Run reachable in the program pane @cross-browser', async ({ page }, testInfo) => {
    for (const [width, height] of [[1440, 800], [768, 800], [390, 844], [320, 740]]) {
        await page.setViewportSize({ width, height });
        await page.goto('/playground?game=number-machine');
        await expect(page.getByRole('heading', { name: 'Calibrate the power supply', exact: true })).toHaveCount(1);
        const controls = page.getByRole('region', { name: 'Instructions and program' });
        const firstCommand = controls.locator('[data-add-command="add 3"]');
        await expect(firstCommand).toBeVisible();
        await firstCommand.scrollIntoViewIfNeeded();
        await firstCommand.click();
        if (width <= 800) {
            // The stacked page keeps the scene first. Bring the entire program pane into view.
            await controls.evaluate(element => element.scrollIntoView({ block: 'center' }));
        }
        const run = controls.getByRole('button', { name: 'Run', exact: true });
        await expect(run).toBeInViewport({ ratio: 1 });
        await expect(run).toBeEnabled();
        await page.screenshot({ path: testInfo.outputPath(`arcade-${width}.png`) });
        await run.click();
        const result = controls.getByRole('status', { name: 'Program output' });
        await expect(page.locator('[data-arcade-game]')).toHaveAttribute('data-play-state', 'retry');
        await expect(result).toBeFocused();
        await expect(result).toBeInViewport({ ratio: 1 });
        await controls.getByRole('button', { name: 'Hint', exact: true }).click();
        await expect(result).toBeFocused();
        await expect(result).toBeInViewport({ ratio: 1 });
    }
});

test('every game exposes usable focused board and program panes @cross-browser', async ({ page }, testInfo) => {
    await page.setViewportSize({ width: 390, height: 844 });
    for (const { template } of cases.filter(item => item.level === 'challenge')) {
        await page.goto(`/playground?game=${template}`);
        const game = page.locator('[data-arcade-game][data-focus-surface="game"], [data-coding-game][data-focus-surface="game"]');
        await expect(game).toBeVisible();
        await expect(game.locator('.arcade-scene, .game-world')).toBeVisible();
        await expect(game.locator('.game-controls')).toBeVisible();
        if (template === 'pixel-studio') {
            await expect(game.getByRole('heading', { name: /^Your canvas/ })).toBeVisible();
            const cells = game.locator('button.pixel-tile');
            for (const width of [390, 320]) {
                await page.setViewportSize({ width, height: 844 });
                for (const cell of await cells.all()) await expect(cell).toBeVisible();
                const paletteTop = await game.locator('.paint-palette').evaluate(element => element.getBoundingClientRect().top);
                const canvasBottom = await cells.last().evaluate(element => element.getBoundingClientRect().bottom);
                expect(canvasBottom).toBeLessThanOrEqual(paletteTop);
            }
            await page.setViewportSize({ width: 390, height: 844 });
        }
        await audit(page);
        await page.screenshot({ path: `${evidence}/focused-${template}-${testInfo.project.name}.png` });
    }
});
