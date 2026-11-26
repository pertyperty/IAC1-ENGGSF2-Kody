import { test, expect } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { audit } from './support/audit.mjs';
import { finishSignIn } from './support/sign-in.mjs';
const evidence = 'storage/app/private/learning-consolidation-evidence';
async function login(page, name) {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(`browser_${name}`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await finishSignIn(page);
}
test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => localStorage.setItem('kody-tour-v1', 'dismissed'));
    await mkdir(evidence, { recursive: true });
});
test('verification input preserves leading zeros and accepts six digits only @cross-browser', async ({ page }) => {
    await page.goto('/email/verify');
    const code = page.getByLabel('Six-digit verification code');
    await expect(code).toHaveAttribute('autocomplete', 'one-time-code');
    await expect(code).toHaveAttribute('inputmode', 'numeric');
    await code.fill('00 12-34');
    await expect(code).toHaveValue('001234');
    expect(await code.evaluate(input => input.checkValidity())).toBe(true);
    await code.fill('0012345');
    expect(await code.evaluate(input => input.checkValidity())).toBe(false);
});
test('verified newcomer chooses Expert, reads the tutorial, and lands on the Tower map @cross-browser', async ({ page }, testInfo) => {
    await login(page, `newlearner_${testInfo.project.name}`);
    await expect(page).toHaveURL(/\/tower\/onboarding$/);
    await expect(page.getByRole('heading', { name: 'Choose your coding level' })).toBeVisible();
    await page.setViewportSize({ width: 390, height: 844 });
    await page.getByRole('radio', { name: /Expert/ }).check();
    await audit(page);
    await page.screenshot({ path: `${evidence}/proficiency-choice-${testInfo.project.name}.png` });
    await page.getByRole('button', { name: 'Continue to tutorial' }).click();
    await expect(page.getByRole('heading', { name: 'How to play the Tower' })).toBeVisible();
    await audit(page);
    await page.getByRole('button', { name: 'Open Tower map' }).click();
    await expect(page).toHaveURL(/\/\?view=map&track=expert$/);
    await expect(page.getByRole('link', { name: 'Start level 71', exact: true })).toBeVisible();
    await expect(page.locator('[data-coding-game]')).toHaveCount(0);
    await audit(page);
});
test('staff accounts notifications and presets expose useful filters without footer overlap @cross-browser', async ({ page }) => {
    await login(page, 'administrator');
    for (const [url, label] of [['/manage/accounts', 'accounts'], ['/updates', 'notifications'], ['/manage/game-presets', 'presets']]) {
        await page.goto(url);
        await expect(page.locator('main')).toBeVisible();
        await page.screenshot({ path: `${evidence}/${label}-desktop.png`, fullPage: true });
        await audit(page);
        const footer = page.locator('footer');
        if (await footer.count()) {
            await footer.scrollIntoViewIfNeeded();
            const bounds = await footer.boundingBox();
            const rail = await page.locator('.app-rail').boundingBox();
            expect(rail.y + rail.height).toBeLessThanOrEqual(bounds.y + 2);
        }
        await page.setViewportSize({ width: 390, height: 844 });
        await audit(page);
        await page.screenshot({ path: `${evidence}/${label}-mobile.png`, fullPage: true });
        await page.setViewportSize({ width: 1280, height: 900 });
    }
});
