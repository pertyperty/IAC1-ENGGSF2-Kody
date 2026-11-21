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
    await expect(rows.first()).toContainText('value = value \u00d7 2');
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

test('focused games fit the screen, keep the board still and restore the program @cross-browser', async ({ page }, testInfo) => {
    for (const [width, height] of [[1440, 900], [390, 844], [320, 740]]) {
        await page.setViewportSize({ width, height });
        await page.goto('/playground?game=number-machine');
        const game = page.locator('[data-arcade-game]');
        await game.locator('[data-add-command="add 3"]').click();
        await game.getByRole('button', { name: 'Focus game', exact: true }).click();
        const dialog = page.getByRole('dialog', { name: 'Focused game workspace' });
        await expect(dialog).toBeVisible();
        await expect(dialog).toBeInViewport({ ratio: .99 });
        const scene = game.locator('.arcade-scene');
        const before = await scene.boundingBox();
        const controls = game.locator('.game-controls');
        await game.getByText('Step through program', { exact: true }).click();
        await controls.evaluate(element => { element.scrollTop = element.scrollHeight; });
        expect(Math.abs((await scene.boundingBox()).y - before.y)).toBeLessThan(2);
        await expect(scene).toBeInViewport({ ratio: .99 });
        await expect(scene.getByText('START', { exact: true })).toBeInViewport({ ratio: .99 });
        await audit(page);
        await page.screenshot({ path: `${evidence}/focus-${width}-${testInfo.project.name}.png` });
        await page.keyboard.press('Escape');
        await expect(dialog).toHaveCount(0);
        await expect(game.locator('[data-readable-program] code')).toHaveText('value = value + 3');
        await expect(game.getByRole('button', { name: 'Focus game', exact: true })).toBeFocused();
    }
});

test('every game exposes usable focused board and program panes @cross-browser', async ({ page }, testInfo) => {
    await page.setViewportSize({ width: 390, height: 844 });
    for (const { template } of cases.filter(item => item.level === 'challenge')) {
        await page.goto(`/playground?game=${template}`);
        const game = page.locator('[data-arcade-game], [data-coding-game]');
        await game.getByRole('button', { name: 'Focus game', exact: true }).click();
        await expect(game.locator('.arcade-scene, .game-world')).toBeInViewport({ ratio: .99 });
        await expect(game.locator('.game-controls')).toBeInViewport({ ratio: .99 });
        if (template === 'pixel-studio') {
            await expect(game.getByRole('heading', { name: /^Your canvas/ })).toBeInViewport({ ratio: .99 });
            const cells = game.locator('button.pixel-tile');
            for (const width of [390, 320]) {
                await page.setViewportSize({ width, height: 844 });
                for (const cell of await cells.all()) await expect(cell).toBeInViewport({ ratio: .99 });
                const paletteTop = await game.locator('.paint-palette').evaluate(element => element.getBoundingClientRect().top);
                const canvasBottom = await cells.last().evaluate(element => element.getBoundingClientRect().bottom);
                expect(canvasBottom).toBeLessThanOrEqual(paletteTop);
            }
            await page.setViewportSize({ width: 390, height: 844 });
        }
        await audit(page);
        await page.screenshot({ path: `${evidence}/focused-${template}-${testInfo.project.name}.png` });
        await page.getByRole('button', { name: 'Exit focus', exact: true }).click();
        await expect(page.getByRole('dialog', { name: 'Focused game workspace' })).toHaveCount(0);
    }
});
