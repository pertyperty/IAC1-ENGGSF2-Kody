import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';

test.beforeEach(async ({ page }) => {
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});

async function returning(page) {
    await page.addInitScript(() => localStorage.setItem('kody-tour-v1', 'dismissed'));
}

test('first guest gets a keyboard accessible mascot welcome and spotlight tour that can be skipped and replayed', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    const tour = page.locator('[data-kody-tour]');
    await expect(tour).toBeVisible();
    await expect(tour.getByRole('button', { name: 'Click to begin' })).toBeFocused();
    await expect(tour.getByRole('link', { name: 'Log in', exact: true })).toHaveAttribute('href', /\/login$/);
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/kody-welcome-mobile.png' });
    await page.keyboard.press('Enter');
    await expect(tour.locator('[data-tour-count]')).toContainText('1 /');
    expect(Number(await tour.locator('[data-tour-hole]').getAttribute('r'))).toBeGreaterThan(30);
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/kody-spotlight-mobile.png' });
    await tour.getByRole('button', { name: 'Next tip' }).click();
    await expect(tour.locator('[data-tour-count]')).toContainText('2 /');
    await tour.getByRole('button', { name: 'Previous' }).click();
    await expect(tour.locator('[data-tour-count]')).toContainText('1 /');
    await page.keyboard.press('Escape');
    await expect(tour).toBeHidden();
    await page.reload();
    await expect(tour).toBeHidden();
    await page.getByRole('button', { name: 'Kody guide and companion' }).click();
    await expect(tour).toBeVisible();
    await tour.getByRole('button', { name: 'Skip tutorial' }).click();
    await expect(page.getByRole('button', { name: 'Kody guide and companion' })).toBeFocused();
});

test('tower pans from land to space and the icon target restores the present floor without changing progress', async ({ page }) => {
    await returning(page); await page.setViewportSize({ width: 1920, height: 1000 }); await page.goto('/');
    const viewport = page.locator('[data-tower-viewport]');
    const art = page.locator('[data-ascent-art]');
    await expect.poll(() => art.evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
    const before = await art.evaluate(img => img.style.transform);
    await viewport.evaluate(element => { element.scrollTop = 0; });
    await expect(page.locator('[data-tower-altitude]')).toContainText('Planet trail');
    await expect.poll(() => art.evaluate(img => img.style.transform)).not.toBe(before);
    await page.screenshot({ path: 'storage/app/browser-results/kody-ascent-space.png' });
    await page.getByRole('button', { name: 'Jump to your current level' }).click();
    await expect(page.locator('[data-tower-stop="1"] .tower-node')).toBeInViewport();
    await expect(page.locator('[data-tower-altitude]')).toContainText('Meadow');
    expect(await page.locator('.tower-grid').evaluate(el => Number(getComputedStyle(el).opacity))).toBeLessThan(.4);
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/kody-ascent-land.png' });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await viewport.evaluate(element => { element.scrollTop = 0; });
    await expect(art).toHaveCSS('transform', 'none');
    await page.setViewportSize({ width: 320, height: 740 }); await audit(page);
});

test('companion supports bounded drag keyboard movement hide and restoration across pages', async ({ page }) => {
    await returning(page); await page.goto('/help');
    const pet = page.locator('[data-companion]'); const handle = pet.locator('[data-companion-handle]');
    await expect(handle).toBeVisible();
    const box = await handle.boundingBox();
    await page.mouse.move(box.x + 30, box.y + 30); await page.mouse.down(); await page.mouse.move(260, 210, { steps: 8 }); await page.mouse.up();
    await expect(pet.locator('[data-companion-bubble]')).toBeHidden();
    const moved = await pet.boundingBox(); expect(moved.x).toBeLessThan(box.x - 50);
    await handle.focus(); await page.keyboard.press('ArrowLeft'); expect((await pet.boundingBox()).x).toBeLessThan(moved.x);
    await page.reload(); expect((await pet.boundingBox()).x).toBeLessThan(moved.x);
    await handle.click(); await expect(pet.locator('[data-companion-bubble]')).toBeVisible();
    await pet.getByRole('button', { name: 'Hide companion' }).click(); await expect(pet).toBeHidden();
    await page.reload(); await expect(pet).toBeHidden();
    await page.getByRole('button', { name: 'Kody guide and companion' }).click();
    await page.getByRole('button', { name: 'Skip tutorial' }).click(); await expect(pet).toBeVisible();
    await page.setViewportSize({ width: 320, height: 740 }); await handle.focus();
    for (let index = 0; index < 5; index++) await page.keyboard.press('ArrowRight');
    await handle.click(); await audit(page);
    await page.setViewportSize({ width: 740, height: 360 }); await audit(page);
});

test('help topics profile photo and sidebar labels have coherent wide and mobile layouts', async ({ page }) => {
    await returning(page); await page.goto('/help'); await expect(page.getByRole('heading', { name: 'A little help goes a long way.' })).toBeVisible(); await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/kody-help-wide.png', fullPage: true });
    await page.setViewportSize({ width: 320, height: 740 }); await audit(page);
    await page.goto('/login'); await page.getByLabel('Email or username').fill('browser_guidelearner'); await page.getByLabel(/^Password/).fill('BrowserStrong12!'); await page.getByRole('button', { name: /Sign in/ }).click();
    await page.setViewportSize({ width: 1440, height: 900 }); await page.goto('/account');
    const picture = page.getByRole('link', { name: 'Edit your profile picture', exact: true });
    await picture.hover(); await expect(picture.locator('.profile-edit-icon')).toHaveCSS('opacity', '1');
    await audit(page); await page.screenshot({ path: 'storage/app/browser-results/kody-profile-wide.png' });
    const link = page.locator('.app-rail .workspace-nav a').first();
    await link.hover(); await expect(link.locator('.nav-label')).toHaveCSS('transform', 'matrix(1, 0, 0, 1, 0, 0)');
    await picture.click(); await expect(page).toHaveURL(/\/account\/edit#profile-picture$/); await expect(page.getByLabel('New picture')).toBeVisible();
    await page.setViewportSize({ width: 320, height: 740 }); await audit(page);
});

test('contextual guides explain all five role workspaces without submitting forms', async ({ browser }) => {
    test.setTimeout(150000);
    for (const [role, path, expected] of [
        ['guidewallet', '/wallet', 'Know what is available'],
        ['guideinstructor', '/create/modules/new', 'Give the idea a playground'],
        ['guidecontributor', '/create/challenges/new', 'Check the important cases'],
        ['guidemoderator', '/manage/accounts', 'Find the right account'],
        ['guideadministrator', '/manage/accounts', 'Your staff workspace'],
    ]) {
        const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
        try {
            await context.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
            const page = await context.newPage(); await returning(page);
            await page.goto('/login'); await page.getByLabel('Email or username').fill(`browser_${role}`);
            await page.getByLabel(/^Password/).fill('BrowserStrong12!'); await page.getByRole('button', { name: /Sign in/ }).click();
            await page.goto(path); const mutations = [];
            page.on('request', request => { if (!['GET', 'HEAD'].includes(request.method())) mutations.push(request.method()); });
            const tour = page.locator('[data-kody-tour]');
            await page.getByRole('button', { name: 'Kody guide and companion' }).click(); await tour.locator('[data-tour-next]').click();
            let found = false;
            for (let step = 0; step < 40 && await tour.isVisible(); step++) {
                if (await tour.locator('#tour-title').textContent() === expected) { found = true; break; }
                await tour.locator('[data-tour-next]').click();
            }
            expect(found, `${role} should have a relevant page guide`).toBe(true);
            expect(Number(await tour.locator('[data-tour-hole]').getAttribute('r'))).toBeGreaterThan(30);
            await audit(page); await page.screenshot({ path: `storage/app/browser-results/kody-guide-${role}.png` });
            await page.setViewportSize({ width: 320, height: 740 }); await audit(page);
            await tour.getByRole('button', { name: 'Skip tutorial' }).click();
            expect(mutations).toEqual([]); await expect(page).toHaveURL(new RegExp(`${path}$`));
            if (role.includes('moderator') || role.includes('administrator')) await expect(page.locator('[data-progress-strip]')).toHaveCount(0);
        } finally { await context.close(); }
    }
});

test('custom errors have a safe exit and slow navigation loader releases on browser restoration', async ({ page }) => {
    await returning(page); const response = await page.goto('/a-missing-kody-path'); expect(response.status()).toBe(404);
    await expect(page.getByRole('heading', { name: 'This path leads somewhere new.' })).toBeVisible(); await audit(page);
    await expect(page.locator('.kody-error img').first()).toHaveAttribute('src', /thinking\.webp$/);
    await page.goto('/help');
    await page.route('**/welcome', async route => { await new Promise(resolve => setTimeout(resolve, 700)); await route.continue(); });
    // A real delayed document request shows a lightweight status, without intercepting the navigation.
    await page.evaluate(() => {
        const loader = document.querySelector('[data-page-loader]');
        new MutationObserver(() => { if (!loader.hidden) sessionStorage.setItem('loader-was-visible', 'yes'); }).observe(loader, { attributes: true, attributeFilter: ['hidden'] });
        const link = document.createElement('a'); link.href = '/welcome'; link.textContent = 'Delayed journey'; document.querySelector('main').append(link);
    });
    await page.getByRole('link', { name: 'Delayed journey' }).click(); await page.waitForURL(/\/welcome$/);
    expect(await page.evaluate(() => sessionStorage.getItem('loader-was-visible'))).toBe('yes');
    await expect(page.locator('[data-page-loader]')).toBeHidden(); await page.goBack(); await expect(page.locator('[data-page-loader]')).toBeHidden();
    await page.clock.install();
    await page.evaluate(() => { const link = document.createElement('a'); link.href = '/images/kody/mascot.webp'; link.download = ''; link.textContent = 'Download mascot'; document.querySelector('main').append(link); });
    const download = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Download mascot' }).click(); await download;
    await page.clock.runFor(500); await expect(page.locator('[data-page-loader]')).toBeHidden();
    // Protected endpoints retain normal redirect/error navigation when access expires.
    let unexpectedDownload = false; page.on('download', () => { unexpectedDownload = true; });
    await page.evaluate(() => { const link = document.createElement('a'); link.href = '/manage/instructors/1/credential'; link.setAttribute('data-navigation-download', ''); link.textContent = 'Expired private download'; document.querySelector('main').append(link); });
    await page.getByRole('link', { name: 'Expired private download' }).click();
    await expect(page).toHaveURL(/\/login$/); expect(unexpectedDownload).toBe(false);
});
