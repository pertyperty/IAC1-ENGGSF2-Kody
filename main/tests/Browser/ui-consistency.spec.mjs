import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
        localStorage.setItem('kody-tour-v1', 'dismissed');
        localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true }));
    });
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});

test('course cards render unjoined progress and shared actions have aligned icons and a centered HUD', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill('browser_questlearner');
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await expect(page).toHaveURL(/(?:\/|\/login\/confirmation)$/);
    if (page.url().endsWith('/login/confirmation')) await page.getByRole('button', { name: 'End previous session and continue' }).click();
    await expect(page).toHaveURL(/\/$/);
    await page.setViewportSize({ width: 1920, height: 1080 });
    await page.goto('/learn/courses');
    const cards = page.locator('.module-card');
    expect(await cards.count()).toBeGreaterThan(0);
    expect(await page.locator('main h1').evaluate(element => parseFloat(getComputedStyle(element).fontSize))).toBeGreaterThanOrEqual(28);
    expect((await cards.first().locator('.content-price').boundingBox()).height).toBeLessThan(40);
    await expect(cards.first().locator('.saved-progress')).toContainText('Not joined');
    expect(await page.locator('main').textContent()).not.toMatch(/@(include|endif|else|if)\b/);
    expect(await page.locator('main').textContent()).not.toMatch(/[←-⇿\uFE0E\uFE0F]/);
    const hud = page.locator('[data-progress-strip]');
    const boxes = await hud.locator('a').evaluateAll(links => links.map(link => { const box = link.getBoundingClientRect(); return { left: box.left, right: box.right }; }));
    expect(Math.abs((boxes[0].left + boxes.at(-1).right) / 2 - 960)).toBeLessThan(2);
    const action = page.getByRole('main').getByRole('link', { name: 'Browse lessons' });
    const icon = await action.locator('svg').boundingBox(), button = await action.boundingBox();
    expect(Math.abs(icon.y + icon.height / 2 - button.y - button.height / 2)).toBeLessThan(2);
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/consistent-course-cards-wide.png' });
    await page.setViewportSize({ width: 320, height: 740 });
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/consistent-course-cards-mobile.png' });
    await page.goto('/account/edit');
    await expect(hud).toHaveCount(0);
    await page.goto('/account');
    const link = page.locator('.secondary-link a').first();
    if (await link.count()) {
        await expect(link).toHaveCSS('display', 'inline');
        expect(await link.evaluate(element => getComputedStyle(element).textDecorationLine)).toContain('underline');
    }
});

test('tower grid redraws with theme and ascent colors without a floating satellite', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('.ascent-satellite, .brand-dot')).toHaveCount(0);
    const grid = page.locator('[data-tower-grid]');
    const color = () => grid.evaluate(element => getComputedStyle(element).color);
    const pixels = () => grid.evaluate(element => element.toDataURL());
    await expect(page.locator('[data-tower-map]')).toHaveAttribute('data-ascent-zone', '0');
    const meadow = await color(), initial = await pixels();
    await page.getByRole('button', { name: /mode$/, exact: false }).click();
    await expect.poll(color).not.toBe(meadow);
    await expect.poll(pixels).not.toBe(initial);
    const themed = await color(), themedPixels = await pixels();
    await page.locator('[data-tower-viewport]').evaluate(element => { element.scrollTop = 0; });
    await expect(page.locator('[data-tower-map]')).toHaveAttribute('data-ascent-zone', '4');
    await expect.poll(color).not.toBe(themed);
    await expect.poll(pixels).not.toBe(themedPixels);
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/consistent-tower-summit.png' });
});
