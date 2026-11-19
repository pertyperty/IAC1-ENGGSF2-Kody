import { finishSignIn } from './support/sign-in.mjs';
import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';

async function login(page, role) {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(`browser_${role}`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await finishSignIn(page);
}

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
        localStorage.setItem('kody-tour-v1', 'dismissed');
        localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true }));
    });
});

test.afterEach(async ({ page }) => {
    // Closing a browser context does not end the application's active session.
    // Leave shared fixture accounts available for the subsequent journeys.
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    if (token) {
        const response = await page.request.post('/logout', { form: { _token: token } });
        expect(response.ok()).toBeTruthy();
    }
});

test('one primary Continue follows the selected track and supporting history stays secondary', async ({ page }) => {
    await login(page, 'learner');
    await page.goto('/?track=expert');
    await page.goto('/dashboard');
    await expect(page.locator('[data-primary-continue]')).toHaveCount(1);
    await expect(page.locator('[data-primary-continue]')).toHaveText(/Continue level 71/);
    await expect(page.locator('[data-primary-continue]')).toHaveAttribute('href', /\/tower\/71$/);
    await expect(page.getByText('Your mission board', { exact: true })).toHaveCount(0);
    const starters = page.locator('.optional-starter-lessons');
    await expect(starters).not.toHaveAttribute('open');
    await starters.locator('summary').click();
    await expect(starters.getByRole('link', { name: /Open lesson|play/i }).first()).toBeVisible();
    await starters.locator('summary').click();
    for (const width of [1280, 390]) {
        await page.setViewportSize({ width, height: 1000 });
        await audit(page);
    }
    await page.locator('h1').focus();
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: 'storage/app/browser-results/consolidated-progress-mobile.png', fullPage: true });
    await page.goto('/wallet');
    await expect(page.getByText('Purchases are unavailable.', { exact: false })).toBeVisible();
    await expect(page.locator('.wallet-package')).toHaveCount(0);
    await expect(page.getByRole('heading', { name: 'KodeBit history' })).toBeVisible();
});

test('authoring warns on leave, preserves cancelled edits and allows unchanged or reverted forms', async ({ page }) => {
    await login(page, 'instructor');
    await page.goto('/create/modules/new?example=programming-check');
    // File inputs with no selected file must not make an untouched form dirty.
    await page.goto('/dashboard');
    await page.goto('/create/modules/new?example=programming-check');
    const title = page.getByLabel('Lesson title');
    const initial = await title.inputValue();
    await title.click();
    await title.fill('Unsaved lesson title');
    const dialog = page.waitForEvent('dialog');
    const navigation = page.evaluate(() => { location.href = '/dashboard'; });
    const warning = await dialog;
    expect(warning.type()).toBe('beforeunload');
    await warning.dismiss();
    await navigation;
    await expect(title).toHaveValue('Unsaved lesson title');
    await title.fill(initial);
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/dashboard$/);
});
