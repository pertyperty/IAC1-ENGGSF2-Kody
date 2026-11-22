import { finishSignIn } from './support/sign-in.mjs';
import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';

async function login(page, role) {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(`browser_discovery${role}`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await finishSignIn(page);
}

async function logout(page) {
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    if (token) expect((await page.request.post('/logout', { form: { _token: token } })).ok()).toBeTruthy();
}

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
        localStorage.setItem('kody-tour-v1', 'dismissed');
        localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true }));
    });
});
test.afterEach(async ({ page }) => { await logout(page); });

test('Tower list supports guest trials, keyboard navigation and selected-track locks @cross-browser', async ({ page }) => {
    await page.goto('/?view=list');
    const trials = page.getByRole('list', { name: 'Beginner levels' });
    await expect(trials.getByRole('link', { name: /^Level 1:/ })).toHaveAttribute('aria-disabled', 'false');
    await expect(trials.getByRole('link', { name: /^Level 2:/ })).toHaveAttribute('aria-disabled', 'true');
    await page.evaluate(() => localStorage.setItem('kody-guest-tower-v1', '1'));
    await page.reload();
    await expect(trials.getByRole('link', { name: /^Level 2:/ })).toHaveAttribute('aria-disabled', 'false');
    await expect(trials.getByRole('link', { name: /^Level 3:/ })).toHaveAttribute('aria-disabled', 'true');
    await trials.getByRole('link', { name: /^Level 4:/ }).click();
    await expect(page).toHaveURL(/\/register\?from=tower$/);
    await expect(page.getByText('Trial clears stay in this browser.')).toBeVisible();
    await login(page, 'learner');
    await page.goto('/?view=list&track=expert');
    const levels = page.getByRole('list', { name: 'Expert levels' });
    await expect(levels.getByRole('link', { name: /^Level 71:/ })).toHaveCount(1);
    await expect(levels.getByRole('link', { name: /^Level 72:/ })).toHaveCount(0);
    const next = levels.getByRole('link', { name: /^Level 71:/ });
    await next.focus();
    await expect(next).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/\/tower\/71$/);
    await page.goBack();
    await expect(levels).toBeVisible();
    for (const width of [1280, 390, 320]) {
        await page.setViewportSize({ width, height: 900 });
        await audit(page);
    }
    await page.screenshot({ path: 'storage/app/browser-results/tower-list-mobile.png', fullPage: true });
    await page.getByRole('link', { name: 'Map view', exact: true }).click();
    await expect(page.locator('[data-tower-continue]')).toContainText('level 71');
    await audit(page);
    await page.getByRole('link', { name: 'List view', exact: true }).click();
    await expect(levels).toBeVisible();
    await page.goto('/');
    await expect(levels).toBeVisible();
});

test('review inbox filters a real submission and opens the existing review page', async ({ page }) => {
    await login(page, 'instructor');
    await page.goto('/create/modules/new?example=programming-check');
    await page.getByLabel('Module title').fill('Navigation inbox lesson');
    await page.getByRole('button', { name: 'Save draft', exact: true }).click();
    await expect(page).toHaveURL(/\/create\/modules\/\d+$/);
    const id = new URL(page.url()).pathname.split('/').at(-1);
    await page.getByRole('button', { name: 'Submit saved draft for review' }).click();
    await logout(page);
    await login(page, 'moderator');
    await page.goto('/manage/reviews');
    await expect(page.locator('.review-list').getByRole('link', { name: /Navigation inbox lesson/ })).toBeVisible();
    await page.getByLabel('Review type').selectOption('instructors');
    await page.getByRole('button', { name: 'Filter', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Nothing pending here.' })).toBeVisible();
    await page.getByLabel('Review type').selectOption('lessons');
    await page.getByRole('button', { name: 'Filter', exact: true }).click();
    await expect(page).toHaveURL(/category=lessons/);
    for (const width of [1280, 390, 320]) {
        await page.setViewportSize({ width, height: 900 });
        for (const dark of [false, true]) {
            const toggle = page.locator('[data-theme-toggle]');
            if ((await toggle.getAttribute('aria-pressed') === 'true') !== dark) await toggle.click();
            await audit(page);
        }
    }
    await page.screenshot({ path: 'storage/app/browser-results/review-inbox-mobile.png', fullPage: true });
    await page.locator('.review-list').getByRole('link', { name: /Navigation inbox lesson/ }).click();
    await expect(page).toHaveURL(new RegExp(`/manage/modules/${id}$`));
    await expect(page.getByRole('button', { name: 'Approve and publish' })).toBeVisible();
});


test('Show navigation remains fixed while hovering and clicking on narrow screens @cross-browser', async ({ page }) => {
    await login(page, 'learner');
    await page.setViewportSize({ width: 320, height: 740 });
    await page.goto('/learn/sequences');
    await page.getByRole('button', { name: 'Exit focus', exact: true }).click();
    await expect(page.locator('.focus-game-shell')).toHaveCount(0);
    // Exit focus leaves the pointer over the restored header, which intentionally stays open on hover.
    await page.mouse.move(160, 600);
    await page.evaluate(() => window.scrollTo(0, 450));
    const reveal = page.locator('[data-navigation-reveal]');
    await expect(reveal).toBeVisible();
    const position = await page.evaluate(() => window.scrollY);
    await reveal.hover();
    await expect(reveal).toBeVisible();
    expect(await page.evaluate(() => window.scrollY)).toBe(position);
    await reveal.click();
    const workspace = page.locator('.mobile-workspace');
    await expect(workspace.locator('summary')).toBeInViewport();
    await workspace.locator('summary').click();
    await expect(workspace).toHaveAttribute('open', '');
    await workspace.getByRole('link', { name: 'Help', exact: true }).click();
    await expect(page).toHaveURL(/\/help$/);
    await audit(page);
});
