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
test('verified newcomer starts Expert directly and retains the choice after login @cross-browser', async ({ page }, testInfo) => {
    await login(page, `newlearner_${testInfo.project.name}`);
    const prompt = page.getByRole('dialog', { name: 'Where would you like to start?' });
    await expect(prompt).toBeVisible();
    await prompt.getByRole('radio', { name: /Expert/ }).check();
    await page.screenshot({ path: `${evidence}/proficiency-choice.png` });
    await prompt.getByRole('button', { name: 'Start my track', exact: true }).click();
    await expect(page).toHaveURL(/\/tower\/\d+$/);
    await expect(page.locator('.tower-level')).toContainText('Expert');
    await page.goto('/?view=list');
    await expect(page.getByRole('heading', { name: 'Expert', exact: true })).toBeVisible();
    await expect(prompt).not.toBeVisible();
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
