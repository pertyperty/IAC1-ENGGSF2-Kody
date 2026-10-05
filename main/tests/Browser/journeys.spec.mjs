import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { readFileSync } from 'node:fs';
import { spawnSync } from 'node:child_process';

const manifest = JSON.parse(readFileSync(process.env.KODY_BROWSER_MANIFEST, 'utf8'));
function fixture(operation, email) {
    const result = spawnSync(process.env.KODY_TEST_PHP, ['tests/Browser/support/fixture.php', operation, email], { encoding: 'utf8', windowsHide: true, timeout: 30_000 });
    expect(result.status, result.stderr).toBe(0);
    return result.stdout;
}
async function login(page, role) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(`${role}@browser.example.test`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: 'Sign in', exact: false }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}
async function audit(page) {
    await page.evaluate(async () => {
        await Promise.all(document.getAnimations().filter(animation => animation.effect?.getComputedTiming().iterations !== Infinity)
            .map(animation => animation.finished.catch(() => {})));
    });
    const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
    expect(result.violations.map(({ id, nodes }) => ({ id, targets: nodes.map(node => ({ target: node.target, detail: node.failureSummary })) }))).toEqual([]);
    const overflow = await page.evaluate(() => ({ width: window.innerWidth, scroll: document.documentElement.scrollWidth,
        elements: [...document.querySelectorAll('body *')].filter(element => element.getBoundingClientRect().right > window.innerWidth + 1)
            .slice(0, 15).map(element => `${element.tagName}.${element.className}`) }));
    expect(overflow.scroll, JSON.stringify(overflow)).toBeLessThanOrEqual(overflow.width + 1);
}
async function solveGarden(page, saved = true) {
    const game = page.locator('[data-coding-game]');
    for (const direction of ['right', 'right', 'up', 'right', 'right']) await game.getByRole('button', { name: `Add ${direction}`, exact: true }).click();
    await game.getByRole('button', { name: 'Run my code', exact: true }).click();
    await expect(game.locator('[data-game-success]')).toBeVisible();
    if (saved) await expect(game.locator('[data-game-feedback]')).toContainText('saved');
}
test.beforeEach(async ({ page }) => {
    // Acceptance tests cannot accidentally visit or contact a live provider.
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});

test('A01 A02 B03 B05: guest trial, registration, verified play, reading and course assessment persist exactly once', async ({ page }) => {
    const email = 'newplayer@browser.example.test';
    await page.goto('/');
    await expect(page.locator('[data-coding-game]')).toBeVisible();
    await audit(page);
    await solveGarden(page, false);
    await page.goto('/register');
    await page.getByLabel('First name').fill('Browser');
    await page.getByLabel('Last name').fill('Player');
    await page.getByLabel('Username').fill('browser_newplayer');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByLabel('Confirm password').fill('BrowserStrong12!');
    await audit(page);
    await page.getByRole('button', { name: 'Create account' }).click();
    await expect(page).toHaveURL(/\/email\/verify/);
    const token = fixture('verify-token', email);
    await page.goto(`/email/verify#token=${encodeURIComponent(token)}`);
    await expect(page.getByRole('heading', { name: 'You’re verified' })).toBeVisible();
    await expect(page).not.toHaveURL(/#/);
    await login(page, 'newplayer');
    expect(JSON.parse(fixture('snapshot', email))).toMatchObject({ xp: 0, levels: 0 });
    await page.goto('/learn/sequences');
    await solveGarden(page);
    expect(JSON.parse(fixture('snapshot', email))).toMatchObject({ xp: 20, levels: 1, streak: 1 });
    const course = manifest.courses['first-programs'];
    await page.goto(`/learn/courses/${course.id}`);
    await page.getByRole('button', { name: 'Join this journey' }).click();
    await page.goto(`/learn/courses/${course.id}/modules/${course.slots[0]}`);
    await page.getByRole('button', { name: 'Mark as read' }).click();
    expect(JSON.parse(fixture('snapshot', email))).toMatchObject({ xp: 20, completed_lessons: 1 });
    await page.goto(`/learn/courses/${course.id}/modules/${course.slots[1]}`);
    await solveGarden(page);
    await page.reload();
    await solveGarden(page);
    expect(JSON.parse(fixture('snapshot', email))).toMatchObject({ xp: 60, xp_awards: 2, completed_lessons: 2, streak: 1 });
    await audit(page);
});

test('D01 D05 D07: creator draft, staff review, course composition and learner publication use the real UI', async ({ browser }) => {
    const creator = await browser.newContext();
    const staff = await browser.newContext();
    const learner = await browser.newContext();
    try {
        const authorPage = await creator.newPage(); const reviewerPage = await staff.newPage(); const learnerPage = await learner.newPage();
        for (const context of [creator, staff, learner]) await context.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
        await login(authorPage, 'instructor');
        await authorPage.goto('/create/modules/new?example=programming-check');
        await authorPage.getByLabel('Adventure title').fill('Browser review adventure');
        await audit(authorPage);
        await authorPage.getByRole('button', { name: 'Save draft', exact: true }).click();
        await expect(authorPage).toHaveURL(/\/create\/modules\/\d+$/);
        const moduleId = new URL(authorPage.url()).pathname.split('/').at(-1);
        await authorPage.getByRole('button', { name: 'Submit saved draft for review' }).click();
        await login(reviewerPage, 'moderator');
        await reviewerPage.goto(`/manage/modules/${moduleId}`);
        await audit(reviewerPage);
        await reviewerPage.getByRole('button', { name: 'Approve and publish' }).click();
        await authorPage.goto('/create/courses/new');
        await authorPage.getByLabel('Course title').fill('Browser reviewed journey');
        await authorPage.getByLabel('The big idea').fill('An original programming knowledge check.');
        await authorPage.getByLabel('Category', { exact: true }).fill('Programming basics');
        await authorPage.getByRole('combobox', { name: 'Adventure 1', exact: true }).selectOption(moduleId);
        await authorPage.getByRole('button', { name: 'Save course draft', exact: true }).click();
        await expect(authorPage).toHaveURL(/\/create\/courses\/\d+$/);
        const courseId = new URL(authorPage.url()).pathname.split('/').at(-1);
        await authorPage.getByRole('button', { name: 'Submit saved course for review' }).click();
        await reviewerPage.goto(`/manage/courses/${courseId}`);
        await reviewerPage.getByRole('button', { name: 'Approve course publication' }).click();
        await login(learnerPage, 'learner');
        await learnerPage.goto(`/learn/courses/${courseId}`);
        await expect(learnerPage.getByRole('heading', { name: 'Browser reviewed journey' })).toBeVisible();
        await learnerPage.getByRole('button', { name: 'Join this journey' }).click();
        await audit(learnerPage);
    } finally { await Promise.all([creator.close(), staff.close(), learner.close()]); }
});

test('Shared interface: keyboard navigation, light/dark themes, narrow layouts and disabled-provider wallet', async ({ page }) => {
    await page.goto('/');
    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Skip to content' })).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page.locator('#main-content')).toBeFocused();
    for (const width of [1280, 390, 320]) {
        await page.setViewportSize({ width, height: 900 });
        for (const dark of [false, true]) {
            const toggle = page.getByRole('button', { name: 'Dark mode' });
            if ((await toggle.getAttribute('aria-pressed')) !== String(dark)) await toggle.click();
            await expect(toggle).toHaveAttribute('aria-pressed', String(dark));
            await audit(page);
            await page.reload();
            await expect(page.getByRole('button', { name: 'Dark mode' })).toHaveAttribute('aria-pressed', String(dark));
        }
    }
    await login(page, 'administrator');
    await page.goto('/wallet');
    await expect(page.getByText('105 KB', { exact: false })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Continue to GCash' })).toHaveCount(0);
    await audit(page);
});

test('B05: four creator arcade assessments and a multi-question quiz save validated wins on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 900 });
    await login(page, 'arcadelearner');
    const programs = { 'pixel-studio': 'paint 1 1 mint\npaint 2 2 peach', 'number-machine': 'add 3\nmultiply 2',
        'sort-lab': 'swap 1 2\nswap 2 3', 'terminal-quest': 'ls\ncat hello.txt\ncp hello.txt release.txt\ncat release.txt' };
    for (const [slug, program] of Object.entries(programs)) {
        await page.goto(`/learn/modules/${manifest.modules[slug]}`);
        await page.getByLabel('Your program', { exact: true }).fill(program);
        await audit(page);
        await page.getByRole('button', { name: 'Run program' }).click();
        await expect(page.locator('[data-arcade-output]')).toContainText('saved');
        await audit(page);
    }
    await page.goto(`/learn/modules/${manifest.modules['programming-check']}`);
    for (const label of ['The result must always stay the same', 'A loop around the three steps', 'Check a condition before collecting']) {
        await page.getByRole('radio', { name: label, exact: true }).check();
    }
    await page.getByRole('button', { name: 'Check my idea' }).click();
    await expect(page.locator('[data-quiz-feedback]')).toContainText('try again');
    expect(JSON.parse(fixture('snapshot', 'arcadelearner@browser.example.test'))).toMatchObject({ xp: 160, xp_awards: 4 });
    await audit(page);
    await page.getByRole('radio', { name: 'The robot may end up somewhere different', exact: true }).check();
    await page.getByRole('button', { name: 'Check my idea' }).click();
    await expect(page.locator('[data-quiz-feedback]')).toContainText('saved');
    await audit(page);
    expect(JSON.parse(fixture('snapshot', 'arcadelearner@browser.example.test'))).toMatchObject({ xp: 200, xp_awards: 5, streak: 1 });
});
