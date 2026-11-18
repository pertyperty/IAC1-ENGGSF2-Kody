import { test, expect } from '@playwright/test';
import { readFileSync, writeFileSync } from 'node:fs';
import { gzipSync } from 'node:zlib';

test('pages request only their required script entries and retain working controls', async ({ page, context }) => {
    test.setTimeout(120_000);
    await page.addInitScript(() => {
        localStorage.setItem('kody-tour-v1', 'dismissed');
        localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true }));
    });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const session = await context.newCDPSession(page);
    await session.send('Network.enable');
    await session.send('Network.setCacheDisabled', { cacheDisabled: true });
    await session.send('Emulation.setCPUThrottlingRate', { rate: 4 });
    const samples = [];
    const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
    function dependencies(entries) {
        const files = new Set();
        const visit = key => {
            const chunk = manifest[key];
            if (files.has(chunk.file)) return;
            files.add(chunk.file);
            (chunk.imports ?? []).forEach(visit);
        };
        entries.forEach(visit);
        return [...files].map(file => `/build/${file}`).sort();
    }
    async function sample(name, url, entries = [], ready) {
        const keys = ['resources/js/app.js', ...entries.map(entry => `resources/js/${entry}.js`)];
        for (let attempt = 0; attempt < 3; attempt++) {
            await page.goto(url);
            await expect(page.locator('[data-theme-toggle][aria-pressed]')).toBeVisible();
            if (ready) await ready();
            const timing = await page.evaluate(() => ({
                domContentLoadedMs: performance.getEntriesByType('navigation')[0].domContentLoadedEventEnd,
                scripts: performance.getEntriesByType('resource').filter(entry => new URL(entry.name).pathname.endsWith('.js'))
                    .map(entry => new URL(entry.name).pathname),
            }));
            const scripts = [...new Set(timing.scripts)].sort();
            const entryScripts = await page.locator('script[type="module"][src]').evaluateAll(elements => elements.map(element => new URL(element.src).pathname).sort());
            expect(entryScripts).toEqual(keys.map(key => `/build/${manifest[key].file}`).sort());
            expect(scripts).toEqual(dependencies(keys));
            let bytes = 0, gzipBytes = 0;
            for (const path of scripts) {
                expect(path).toMatch(/^\/build\/assets\/[\w-]+\.js$/);
                const source = readFileSync(`public${path}`);
                bytes += source.length;
                gzipBytes += gzipSync(source).length;
            }
            samples.push({ name, attempt: attempt + 1, scripts, bytes, gzipBytes, domContentLoadedMs: timing.domContentLoadedMs });
        }
    }
    await sample('login', '/login');
    await sample('help', '/help');
    await sample('practice', '/playground?game=number-machine', ['play'], () => expect(page.locator('[data-arcade-game]')).toHaveAttribute('data-play-state', 'ready'));
    await sample('tower-list', '/?view=list', ['tower-page'], () => expect(page.locator('[data-tower-list-level="2"] a')).toHaveAttribute('aria-disabled', 'true'));
    await page.goto('/login');
    await page.getByLabel('Email or username').fill('browser_instructor');
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await page.waitForURL(/\/(?:dashboard|login\/confirmation)$/);
    if (page.url().endsWith('/login/confirmation')) await page.getByRole('button', { name: 'End previous session and continue' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
    await sample('lesson-editor', '/create/modules/new', ['authoring', 'play'], () => expect(page.locator('[data-custom-game]')).toBeHidden());
    expect(errors).toEqual([]);
    writeFileSync('storage/app/private/page-assets-current.json', JSON.stringify({ cpuThrottlingRate: 4, cache: 'disabled', samples }, null, 2));
});
