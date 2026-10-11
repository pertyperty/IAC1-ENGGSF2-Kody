import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';

test.beforeEach(async ({ page }) => {
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});

test('current username sign-in pictures hamburger feedback and theme remain usable on a narrow screen', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill('browser_picturelearner');
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await expect(page).toHaveURL(/\/$/);
    await page.goto('/account/edit');
    await page.getByLabel('New picture').setInputFiles({ name: 'avatar.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAIAAADYYG7QAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAS0lEQVRYhe3OMQHAIADAMEDKFKEYbfv39GRHoiDz2Wf8ybod+BIqQkWoCBWhIlSEilARKkJFqAgVoSJUhIpQESpCRagIFaEiVITKC3xfAZBP8NoKAAAAAElFTkSuQmCC', 'base64') });
    await page.getByRole('button', { name: 'Upload picture' }).click();
    await expect(page.locator('.profile-avatar img')).toBeVisible();
    await expect(page.locator('[data-flash-toast]')).toContainText('new profile picture');
    await page.locator('[data-flash-toast] [data-toast-dismiss]').click();
    await expect(page.locator('.action-receipt')).toContainText('new profile picture');
    await page.setViewportSize({ width: 320, height: 740 });
    const toggle = page.locator('[data-theme-toggle]');
    const dark = await toggle.getAttribute('aria-pressed') === 'true';
    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-label', dark ? 'Dark mode' : 'Light mode');
    await page.getByRole('button', { name: /Remove picture/ }).click();
    await expect(page.locator('.profile-avatar img')).toHaveCount(0);
    await page.getByRole('button', { name: /Dismiss notification/ }).click();
    await page.getByLabel('Explore your workspace').click();
    await expect(page.locator('.mobile-workspace-panel')).toBeVisible();
    await expect.poll(async () => {
        const header = await page.locator('.app-header').boundingBox();
        const panel = await page.locator('.mobile-workspace-panel').boundingBox();
        return Math.abs(panel.y - header.y - header.height);
    }).toBeLessThan(2);
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/picture-and-menu-mobile.png' });
    await page.keyboard.press('Escape');
    await expect(page.locator('.mobile-workspace')).not.toHaveAttribute('open');
});

test('tower fills the page and climbs along a responsive winding path with accessible descriptions', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('[data-tower-stop]')).toHaveCount(50);
    const map = page.locator('[data-tower-viewport]');
    const first = page.locator('[data-tower-stop="1"] .tower-node');
    const second = page.locator('[data-tower-stop="2"] .tower-node');
    await expect(first).toBeInViewport();
    const header = await page.locator('[data-scroll-navigation]').boundingBox();
    const a = await first.boundingBox(), b = await second.boundingBox(), box = await map.boundingBox();
    expect(Math.abs(box.y - header.y - header.height)).toBeLessThan(2);
    expect(Math.abs(box.y + box.height - page.viewportSize().height)).toBeLessThan(2);
    expect(await page.evaluate(() => document.documentElement.scrollHeight - innerHeight)).toBeLessThan(2);
    await expect(page.locator('.site-footer')).toHaveCount(0);
    expect(a.y).toBeGreaterThan(b.y);
    expect(Math.abs(a.x + a.width / 2 - box.x - box.width / 2)).toBeLessThan(2);
    expect(b.x).toBeGreaterThan(a.x + 20);
    await page.getByRole('button', { name: 'About level 1', exact: true }).click();
    await expect(page.locator('#tower-detail-1')).toHaveCSS('opacity', '1');
    const initial = await map.evaluate(element => element.scrollTop);
    await page.getByRole('button', { name: 'Climb higher' }).click();
    await expect.poll(() => map.evaluate(element => element.scrollTop)).toBeLessThan(initial);
    await page.screenshot({ path: 'storage/app/browser-results/tower-upward-wide.png' });
    await page.setViewportSize({ width: 390, height: 844 });
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/tower-upward-mobile.png' });
    await page.setViewportSize({ width: 320, height: 740 });
    await page.goto('/');
    await expect(first).toBeInViewport({ ratio: 0.99 });
    const mobileMap = await map.boundingBox();
    expect(Math.abs(mobileMap.y + mobileMap.height - 740)).toBeLessThan(2);
    expect(await page.evaluate(() => document.documentElement.scrollHeight - innerHeight)).toBeLessThan(2);
    await page.getByLabel('Tower guide', { exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Your climb' })).toBeVisible();
    await page.getByLabel('Tower guide', { exact: true }).click();
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/tower-start-mobile.png' });
    await page.setViewportSize({ width: 740, height: 360 });
    await page.goto('/');
    await expect(first).toBeInViewport({ ratio: 0.99 });
    await audit(page);
});

test('tower dot grid responds to the mouse and respects reduced motion', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    await page.goto('/');
    await expect(page.locator('[data-tower-map]')).toHaveClass(/has-interactive-grid/);
    const canvas = page.locator('[data-tower-grid]');
    const pixels = () => canvas.evaluate(element => element.toDataURL());
    const before = await pixels();
    const box = await canvas.boundingBox();
    await page.mouse.move(48, box.y + box.height / 2);
    await expect.poll(async () => (await pixels()) !== before).toBe(true);
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await expect(page.locator('[data-tower-map]')).not.toHaveClass(/has-interactive-grid/);
    await expect(canvas).toBeHidden();
    await expect(page.locator('[data-tower-stop="1"] .tower-node')).toBeInViewport();
    await audit(page);
});

test.describe('touch tower grid', () => {
    test.use({ hasTouch: true, viewport: { width: 390, height: 844 } });
    test('taps repel dots and swipes retain native map scrolling', async ({ page }) => {
        await page.goto('/');
        const canvas = page.locator('[data-tower-grid]');
        await expect(page.locator('[data-tower-map]')).toHaveClass(/has-interactive-grid/);
        const pixels = () => canvas.evaluate(element => element.toDataURL());
        const before = await pixels();
        const box = await canvas.boundingBox();
        await page.touchscreen.tap(36, box.y + box.height * .5);
        await expect.poll(async () => (await pixels()) !== before).toBe(true);
        const map = page.locator('[data-tower-viewport]');
        const initial = await map.evaluate(element => element.scrollTop);
        const session = await page.context().newCDPSession(page);
        await session.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [{ x: 36, y: box.y + box.height * .35 }] });
        for (const fraction of [.4, .5, .6, .7]) await session.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [{ x: 36, y: box.y + box.height * fraction }] });
        await session.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
        await session.detach();
        await expect.poll(() => map.evaluate(element => element.scrollTop)).toBeLessThan(initial);
        expect(await page.evaluate(() => scrollY)).toBe(0);
        await audit(page);
    });
});

for (const [template, program] of [['logic-switchboard', 'flip 1\nflip 2'], ['string-forge', 'upper\nreverse'], ['memory-trail', 'push 2\npush 3']]) {
    test(`${template} has an interactive world and confirms practice without saving account progress`, async ({ page }) => {
        await page.goto(`/playground?game=${template}`);
        await page.getByText('Edit instructions as text', { exact: true }).click();
        await page.locator('[data-arcade-code]').fill(program);
        await page.locator('[data-arcade-run]').click();
        await expect(page.locator('[data-arcade-output]')).toContainText('Objective complete');
        await expect(page.locator('[data-arcade-world]')).toHaveClass(/puzzle-world/);
        await audit(page);
        await page.setViewportSize({ width: 320, height: 740 });
        await audit(page);
    });
}
