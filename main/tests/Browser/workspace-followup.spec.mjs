import { test, expect } from '@playwright/test';
import { readFile, mkdir } from 'node:fs/promises';
import { finishSignIn } from './support/sign-in.mjs';
import { audit } from './support/audit.mjs';
const manifest = JSON.parse(await readFile(process.env.KODY_BROWSER_MANIFEST, 'utf8'));
const evidence = 'storage/app/private/learning-consolidation-evidence';
async function login(page, name) {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(`browser_${name}`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await finishSignIn(page);
}
test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => { localStorage.setItem('kody-tour-v1', 'dismissed'); localStorage.setItem('kody-rail', 'expanded'); });
    await mkdir(evidence, { recursive: true });
});
test('sidebar preserves its own scroll after link navigation @cross-browser', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 650 });
    await login(page, 'followupadmin');
    const nav = page.locator('[data-navigation-rail] .workspace-nav');
    await page.evaluate(() => document.fonts.ready);
    await nav.evaluate(element => { element.scrollTop = element.scrollHeight; });
    const help = nav.getByRole('link', { name: 'Help', exact: true });
    await help.scrollIntoViewIfNeeded();
    // Capture the position at activation, after native focus/automation scrolling.
    await nav.evaluate(element => {
        element.addEventListener('click', () => {
            sessionStorage.setItem('test-navigation-scroll', String(element.scrollTop));
        }, { capture: true, once: true });
    });
    await help.click();
    await expect(page.getByRole('heading', { name: 'Help and FAQs' })).toBeVisible();
    await page.evaluate(() => document.fonts.ready);
    const saved = await page.evaluate(() => Number(sessionStorage.getItem('test-navigation-scroll')));
    expect(saved).toBeGreaterThan(0);
    // The destination's scroll range can shrink; browsers clamp to its new maximum.
    await expect.poll(() => nav.evaluate((element, position) =>
        Math.abs(element.scrollTop - Math.min(position, element.scrollHeight - element.clientHeight)), saved)).toBeLessThan(3);

    // Also check an interior position, where clamping cannot hide a reset.
    // Use real input so a pending font/layout restoration yields to the user.
    // A synthetic scroll alone can race the final restoration animation frame.
    await nav.hover();
    const initial = await nav.evaluate(element => element.scrollTop);
    const delta = Math.floor(initial / 2);
    await page.mouse.wheel(0, -delta);
    // Wheel deltas are input requests, not exact scroll distances (Firefox can
    // round or animate them). Verify real movement, then capture its settled
    // position so the persistence assertion below still checks actual pixels.
    await expect.poll(() => nav.evaluate(element => element.scrollTop)).toBeLessThan(initial);
    let previousPosition;
    let stableSamples = 0;
    await expect.poll(async () => {
        const position = await nav.evaluate(element => element.scrollTop);
        stableSamples = position === previousPosition ? stableSamples + 1 : 0;
        previousPosition = position;
        return stableSamples;
    }, { intervals: [100] }).toBeGreaterThanOrEqual(3);
    const interior = await nav.evaluate(element => element.scrollTop);
    expect(interior).toBeGreaterThan(0);
    await page.reload();
    await expect(page.getByRole('heading', { name: 'Help and FAQs' })).toBeVisible();
    await expect.poll(() => nav.evaluate((element, position) => Math.abs(element.scrollTop - position), interior)).toBeLessThan(3);
    await audit(page);
});
test('account preview follows typing without publishing private details @cross-browser', async ({ page }) => {
    await login(page, 'followupprofile');
    await page.goto('/account/edit');
    await page.getByText('Preview profile', { exact: true }).click();
    const preview = page.getByRole('region', { name: 'Profile preview' });
    await page.getByLabel('Username', { exact: true }).fill('preview_player');
    await page.getByLabel('First name (optional)', { exact: true }).fill('PrivateName');
    await expect(preview).toContainText('preview_player');
    await expect(preview).not.toContainText('PrivateName');
    await page.screenshot({ path: `${evidence}/profile-live-preview.png` });
    await audit(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await audit(page);
});
test('administrator edits Help beside an escaped live preview', async ({ page }) => {
    await login(page, 'followupadmin');
    await page.goto('/help?new=1');
    await page.getByLabel('Question', { exact: true }).fill('Where are my saved modules?');
    const answer = 'Open Library, then choose Bookmarks. <script>example only</script>';
    await page.getByLabel('Answer', { exact: true }).fill(answer);
    await expect(page.getByRole('article', { name: 'FAQ preview' })).toContainText(answer);
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: `${evidence}/help-edit-preview.png` });
    await page.getByRole('button', { name: 'Save to Help', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Help and FAQs' })).toBeVisible();
    await expect(page.getByLabel('Answer', { exact: true })).toHaveValue(answer);
    await audit(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await audit(page);
});
test('Library combines module and Tower bookmarks and filters their titles', async ({ page }) => {
    await login(page, 'followuplearner');
    await page.goto(`/learn/modules/${manifest.modules['number-machine']}`);
    await page.getByRole('button', { name: 'Exit focus', exact: true }).click();
    await expect(page.locator('.focus-game-shell')).toHaveCount(0);
    await page.getByRole('button', { name: 'Bookmark', exact: true }).click();
    await page.getByRole('button', { name: 'Exit focus', exact: true }).click();
    await expect(page.locator('.focus-game-shell')).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Bookmarked', exact: true })).toBeVisible();
    await page.goto('/tower/1');
    await page.getByRole('button', { name: 'Exit focus', exact: true }).click();
    await expect(page.locator('.focus-game-shell')).toHaveCount(0);
    await page.getByRole('button', { name: 'Bookmark level', exact: true }).click();
    await page.goto('/learn/courses/mine?type=bookmarks');
    await expect(page.getByRole('heading', { name: 'Bookmarks', exact: true })).toBeVisible();
    await expect(page.locator('.saved-learning-list li')).toHaveCount(2);
    await page.getByLabel('Search library', { exact: true }).fill('no-matching-title');
    await page.getByRole('button', { name: 'Apply filters', exact: true }).click();
    await expect(page.locator('.saved-learning-list')).toContainText('No matching bookmarks');
    await page.getByRole('link', { name: 'Clear filters', exact: true }).click();
    await page.getByLabel('Show', { exact: true }).selectOption('bookmarks');
    await page.getByRole('button', { name: 'Apply filters', exact: true }).click();
    await expect(page.locator('.saved-learning-list li')).toHaveCount(2);
    await page.screenshot({ path: `${evidence}/library-bookmarks.png`, fullPage: true });
    await audit(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await audit(page);
    await page.screenshot({ path: `${evidence}/library-bookmarks-mobile.png`, fullPage: true });
});

test('companion stays out of mobile controls until requested @cross-browser', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/help');
    await expect(page.locator('[data-companion]')).toBeHidden();
    await page.getByRole('button', { name: 'Kody guide and companion' }).click();
    await page.getByRole('button', { name: 'Skip tutorial' }).click();
    await expect(page.locator('[data-companion]')).toBeVisible();
    await page.reload();
    await expect(page.locator('[data-companion]')).toBeVisible();
});
