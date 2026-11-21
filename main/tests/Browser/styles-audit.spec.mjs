import { test, expect } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { audit } from './support/audit.mjs';
import { finishSignIn } from './support/sign-in.mjs';

const evidence = 'storage/app/private/styles-audit-2026-11-22';
test.beforeEach(async ({ page }) => {
    await mkdir(evidence, { recursive: true });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.addInitScript(() => {
        localStorage.setItem('kody-tour-v1', 'dismissed');
        localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true }));
    });
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});

for (const theme of ['light', 'dark']) {
    test(`public pages share readable unobstructed ${theme} controls`, async ({ page }) => {
        test.setTimeout(180000);
        for (const width of [1440, 320]) {
            await page.setViewportSize({ width, height: 900 });
            for (const path of ['/welcome', '/learn', '/learn/courses', '/challenges', '/help', '/contact', '/register', '/login']) {
                await page.goto(path);
                await page.evaluate(value => document.documentElement.dataset.theme = value, theme);
                await expect(page.locator('.page-navigation')).toHaveCount(0);
                await audit(page);
                expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width + 1);
                for (const button of await page.locator('.button:visible, .primary-button:visible').all()) {
                    expect((await button.boundingBox()).height).toBeGreaterThanOrEqual(43);
                }
                await page.screenshot({ path: `${evidence}/${path.slice(1).replaceAll('/', '-')}-${theme}-${width}.png`, fullPage: true });
            }
        }
    });
}

test('staff forms and workspace icons stay usable on desktop and mobile', async ({ page }) => {
    test.setTimeout(180000);
    await page.goto('/login');
    await page.getByLabel('Email or username').fill('browser_administrator');
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await finishSignIn(page);
    const rail = page.locator('[data-navigation-rail]');
    await expect(rail.getByRole('link', { name: 'Modules', exact: true }).locator('use')).toHaveAttribute('href', /#module$/);
    await expect(rail.getByRole('link', { name: 'Courses', exact: true }).locator('use')).toHaveAttribute('href', /#course$/);
    for (const width of [1440, 390, 320]) {
        await page.setViewportSize({ width, height: 900 });
        for (const path of ['/manage/accounts', '/manage/game-presets', '/help', '/updates', '/account', '/manage/site']) {
            await page.goto(path);
            await audit(page);
            await expect(page.locator('.page-navigation')).toHaveCount(0);
            expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width + 1);
            await page.screenshot({ path: `${evidence}/staff-${path.replaceAll('/', '-')}-${width}.png`, fullPage: true });
        }
    }
    await page.goto('/playground?game=number-machine');
    const focused = page.getByRole('dialog', { name: 'Focused game workspace' });
    await expect(focused).toBeVisible();
    await focused.getByText('Menu', { exact: true }).click();
    const navigation = focused.getByRole('navigation', { name: 'Game navigation' });
    await expect(navigation.getByRole('link', { name: 'Overview', exact: true })).toHaveAttribute('href', /\/dashboard$/);
    await expect(navigation.getByRole('link', { name: 'Tower', exact: true })).toHaveCount(0);
    await page.screenshot({ path: `${evidence}/staff-focused-menu-320.png` });
});

test('default focus provides a menu, learning context and a reversible exit @cross-browser', async ({ page }) => {
    for (const width of [1440, 390, 320]) {
        await page.setViewportSize({ width, height: 844 });
        await page.goto('/playground?game=number-machine');
        const dialog = page.getByRole('dialog', { name: 'Focused game workspace' });
        await expect(dialog).toBeVisible();
        await dialog.getByRole('button', { name: 'Learn', exact: true }).click();
        await expect(dialog.getByRole('region', { name: 'Activity explanation' })).toBeVisible();
        await expect(dialog.getByRole('link', { name: 'Related modules' })).toHaveAttribute('href', /template=number-machine/);
        await dialog.getByText('Menu', { exact: true }).click();
        await expect(dialog.getByRole('navigation', { name: 'Game navigation' })).toBeVisible();
        await expect(dialog.getByRole('link', { name: 'Modules', exact: true })).toHaveAttribute('href', /\/learn$/);
        const theme = await page.locator('html').getAttribute('data-theme');
        await dialog.getByRole('navigation', { name: 'Game navigation' }).getByRole('button', { name: /^(Dark|Light) mode$/ }).click();
        await expect(page.locator('html')).toHaveAttribute('data-theme', theme === 'dark' ? 'light' : 'dark');
        await dialog.getByText('Menu', { exact: true }).click();
        await audit(page);
        await page.screenshot({ path: `${evidence}/focus-${width}.png` });
        await dialog.getByRole('button', { name: 'Exit focus' }).click();
        await expect(dialog).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Focus game' })).toBeFocused();
        await page.getByRole('button', { name: 'Focus game' }).click();
        await expect(dialog).toBeVisible();
    }
});

test('quiz steps retain answers, reveal errors and complete through a final check @cross-browser', async ({ page }) => {
    for (const width of [1440, 390, 320]) {
        await page.setViewportSize({ width, height: 740 });
        await page.goto('/playground?game=quiz-lab');
        await expect(page.getByRole('dialog', { name: 'Focused quiz workspace' })).toBeVisible();
        await audit(page);
        await expect(page.getByRole('button', { name: 'Check answers' })).toBeInViewport();
        await page.screenshot({ path: `${evidence}/quiz-choice-${width}.png` });
    }
    const dialog = page.getByRole('dialog', { name: 'Focused quiz workspace' });
    await expect(dialog).toBeVisible();
    const quiz = dialog.locator('[data-practice-quiz]');
    await expect(quiz.locator('[data-quiz-question]:visible')).toHaveCount(1);
    await quiz.getByLabel('True', { exact: true }).check();
    await quiz.getByRole('button', { name: 'Next question' }).click();
    await quiz.getByLabel('Your short answer').fill(' IF ');
    await quiz.getByRole('button', { name: 'Previous question' }).click();
    await expect(quiz.getByLabel('True', { exact: true })).toBeChecked();
    await quiz.getByRole('button', { name: 'Check answers' }).click();
    await expect(quiz.locator('.quiz-current-question')).toHaveText('Question 3 of 3');
    for (const [label, value] of [['Loop', 'loop'], ['Variable', 'variable'], ['Condition', 'condition']]) {
        await quiz.getByLabel(label, { exact: true }).selectOption(value);
    }
    await expect(quiz.locator('[data-quiz-navigation]')).toContainText('3 of 3 answered');
    await quiz.locator('form').evaluate(element => { element.scrollTop = 0; });
    await expect(quiz.getByRole('button', { name: 'Check answers' })).toBeInViewport();
    await audit(page);
    await page.screenshot({ path: `${evidence}/quiz-320.png` });
    await quiz.getByRole('button', { name: 'Check answers' }).click();
    await expect(page.locator('[data-game-result]')).toBeVisible();
    await expect(page.locator('[data-result-message]')).toContainText('local practice');
    await page.locator('[data-result-return]').click();
    await expect(quiz.getByRole('button', { name: 'Check answers' })).toBeFocused();
});
