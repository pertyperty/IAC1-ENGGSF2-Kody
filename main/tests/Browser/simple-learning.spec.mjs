import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';
import { readFileSync } from 'node:fs';

const manifest = JSON.parse(readFileSync(process.env.KODY_BROWSER_MANIFEST, 'utf8'));
async function login(page, name) {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(`browser_${name}`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await expect(page).not.toHaveURL(/\/login$/);
}

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
        localStorage.setItem('kody-tour-v1', 'dismissed');
        localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true }));
    });
});

test('@cross-browser pseudocode stays visible and bounded as the program fills', async ({ page }) => {
    await page.goto('/tower/1');
    const game = page.locator('[data-coding-game]');
    await expect(game.getByRole('heading', { name: 'Pseudocode' })).toBeVisible();
    const code = game.locator('.trace-source');
    const before = await code.boundingBox();
    const limit = JSON.parse(await game.getAttribute('data-coding-game')).maxCommands;
    for (let i = 0; i < limit; i++) await game.getByRole('button', { name: 'Add right', exact: true }).click();
    await expect(code).toContainText('MOVE RIGHT');
    expect(Math.abs((await code.boundingBox()).height - before.height)).toBeLessThan(2);
    await game.getByRole('button', { name: 'Step preview', exact: true }).click();
    await expect(code.locator('[aria-current="step"]')).toHaveCount(1);
    for (const width of [1280, 390]) {
        await page.setViewportSize({ width, height: 1000 });
        await audit(page);
    }
});

test('@cross-browser Help controls have space and account forms stay accessible', async ({ page }) => {
    await page.goto('/help');
    const form = page.locator('.help-search form');
    const search = await form.getByRole('button', { name: 'Search', exact: true }).boundingBox();
    const topic = await form.getByLabel('Topic', { exact: true }).boundingBox();
    expect(search.y - topic.y - topic.height).toBeGreaterThanOrEqual(8);
    await audit(page);
    await page.goto('/register');
    await expect(page.getByLabel('Username (required)')).toBeVisible();
    await audit(page);
});

test('creator publishes a profile and staff explicitly grants then revokes the qualifications badge', async ({ page }) => {
    await login(page, 'credinstructor');
    await page.goto('/account/public-profile');
    await page.getByLabel('Line of work').fill('Programming teacher');
    await page.getByLabel('About you').fill('I teach programming through small working examples.');
    await page.getByLabel('Experience and accomplishments').fill('Five years of teaching.');
    await page.getByLabel('Publish these details').check();
    await page.getByRole('button', { name: 'Save public profile' }).click();
    await expect(page.getByRole('heading', { name: 'browser_credinstructor', exact: true })).toBeVisible();
    await expect(page.locator('.creator-badge')).toHaveCount(0);
    await audit(page);
    await page.locator('.account-menu > summary').click();
    await page.getByRole('button', { name: 'Sign out', exact: false }).click();
    await login(page, 'credmoderator');
    await page.goto(`/manage/creator-badges/${manifest.credCreator}`);
    await page.getByRole('link', { name: 'Open approved application and private documents' }).click();
    const download = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Download private credentials' }).click();
    expect((await download).suggestedFilename()).toMatch(/^instructor-credential\./);
    await page.getByRole('link', { name: 'Review qualifications badge', exact: true }).click();
    await page.getByLabel('Decision', { exact: true }).selectOption('grant');
    await page.getByLabel('I checked the supporting qualifications.').check();
    await page.getByLabel('Private review reason').fill('Checked this synthetic qualification and its issuing institution.');
    await page.getByLabel('Current password').fill('BrowserStrong12!');
    await page.getByLabel('I confirm this action').check();
    await page.getByRole('button', { name: 'Save badge decision' }).click();
    await expect(page.locator('main')).toContainText('Badge active');
    await page.goto(`/creators/${manifest.credCreator}`);
    await expect(page.locator('.creator-badge')).toBeVisible();
    for (const width of [1280, 390]) { await page.setViewportSize({ width, height: 1000 }); await audit(page); }
    await page.screenshot({ path: 'storage/app/browser-results/creator-profile-mobile.png', fullPage: true });
    await page.goto(`/manage/creator-badges/${manifest.credCreator}`);
    await page.getByLabel('Decision', { exact: true }).selectOption('revoke');
    await page.getByLabel('Private review reason').fill('Synthetic revocation exercise.');
    await page.getByLabel('Current password').fill('BrowserStrong12!');
    await page.getByLabel('I confirm this action').check();
    await page.getByRole('button', { name: 'Save badge decision' }).click();
    await page.goto(`/creators/${manifest.credCreator}`);
    await expect(page.locator('.creator-badge')).toHaveCount(0);
});

test('learner saves a Tower concept for later without earning a clearance', async ({ page }) => {
    await login(page, 'reviewlearner');
    await page.goto('/tower/1');
    await page.getByRole('button', { name: 'Review later', exact: true }).click();
    await page.getByRole('link', { name: 'My review list', exact: true }).click();
    await expect(page.locator('.review-list')).toContainText('First steps');
    await expect(page.locator('[data-progress-level]')).toContainText('0 / 80');
    await page.getByRole('button', { name: 'Remove', exact: true }).click();
    await expect(page.locator('.review-list')).toContainText('No lessons saved');
    await audit(page);
});
