import { test, expect } from '@playwright/test';
import { readFile } from 'node:fs/promises';
import { finishSignIn } from './support/sign-in.mjs';

const routes = {
    guest: [
        '/', '/welcome', '/tower/1', '/playground', '/learn', '/learn/courses', '/challenges',
        '/help', '/contact', '/terms/2026-11-18', '/privacy/2026-11-18', '/register', '/login', '/recover', '/email/verify',
    ],
    learner: [
        '/dashboard', '/updates', '/account', '/account/edit', '/account/google', '/account/archive', '/account/delete',
        '/account/contributor-application', '/account/creator-application', '/learn/courses/mine', '/learn/sequences',
        '/learn/courses/:course', '/learn/modules/:module', '/challenges/:challenge', '/creators/:creator',
        '/weekly', '/weekly/:weekly', '/leaderboards', '/weekly/leaderboard', '/wallet',
    ],
    instructor: [
        '/create', '/create/modules/new', '/create/curriculum', '/create/courses', '/create/courses/new',
        '/create/challenges', '/create/challenges/new', '/account/public-profile', '/wallet/earnings',
    ],
    contributor: ['/dashboard', '/create/challenges', '/create/challenges/new', '/account/public-profile'],
    moderator: [
        '/dashboard', '/manage/accounts', '/manage/reviews', '/manage/weekly-events', '/manage/content',
        '/manage/instructors', '/manage/contributors',
    ],
    administrator: [
        '/manage/accounts', '/manage/finance', '/manage/site', '/manage/reviews', '/manage/tower', '/manage/tower/1',
        '/manage/game-presets', '/manage/faqs', '/manage/faqs/create', '/manage/content', '/manage/modules',
        '/manage/courses', '/manage/challenges', '/manage/contributors', '/manage/instructors',
        '/manage/creator-erasure', '/manage/reports',
    ],
};

const visualSamples = new Set([
    '/welcome', '/tower/1', '/playground', '/learn', '/learn/courses', '/challenges', '/help', '/login',
    '/dashboard', '/updates', '/account', '/learn/courses/mine', '/learn/modules/:module', '/wallet',
    '/create/modules/new', '/manage/reviews', '/manage/game-presets', '/manage/finance', '/manage/faqs',
]);

test.beforeEach(async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.addInitScript(() => localStorage.setItem('kody-tour-v1', 'dismissed'));
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});

for (const [role, paths] of Object.entries(routes)) {
    test(`${role} pages keep content inside the viewport`, async ({ page }, testInfo) => {
        test.setTimeout(180_000);
        if (role !== 'guest') {
            await page.goto('/login');
            await page.getByLabel('Email or username').fill(`browser_${role === 'contributor' ? 'navcontributor' : role}`);
            await page.getByLabel(/^Password/).fill('BrowserStrong12!');
            await page.getByRole('button', { name: /Sign in/ }).click();
            await finishSignIn(page);
        }
        const manifest = JSON.parse(await readFile(process.env.KODY_BROWSER_MANIFEST, 'utf8'));
        const params = {
            course: Object.values(manifest.courses)[0].id,
            module: Object.values(manifest.modules)[0],
            challenge: manifest.challenge,
            creator: manifest.credCreator,
            weekly: manifest.weekly,
        };
        for (const width of [320, 768, 1101, 1440]) {
            await page.setViewportSize({ width, height: 800 });
            for (const template of paths) {
                const path = template.replace(/:(\w+)/g, (_, key) => params[key]);
                const response = await page.goto(path);
                await page.evaluate(() => document.fonts.ready);
                const layout = await page.evaluate(() => ({
                    width: document.documentElement.scrollWidth,
                    offenders: [...document.querySelectorAll('body *')].filter(element => {
                        const box = element.getBoundingClientRect();
                        return box.width > 0 && box.right > innerWidth + 1 && !element.closest('[aria-hidden="true"]');
                    }).slice(0, 6).map(element => `${element.tagName}.${element.getAttribute('class') || ''}`),
                }));
                expect.soft(response?.ok(), `${role} ${path} HTTP ${response?.status()}`).toBe(true);
                expect.soft(new URL(page.url()).pathname, `${role} ${path} destination`).toBe(path);
                expect.soft(layout.width, `${role} ${path} at ${width}px: ${layout.offenders.join(', ')}`).toBeLessThanOrEqual(width + 1);
                if (visualSamples.has(template) && [320, 1440].includes(width)) {
                    const label = template.slice(1).replace(/[^a-z0-9-]+/g, '-');
                    await page.screenshot({ path: testInfo.outputPath(`${role}-${label}-${width}.png`) });
                }
                if (path === paths[0] && [320, 768].includes(width)) {
                    if (role !== 'guest' && width <= 768) {
                        const workspace = page.getByLabel('Explore your workspace');
                        await expect(workspace).toBeVisible();
                        await workspace.click();
                        const menu = page.getByRole('navigation', { name: 'Mobile workspace' });
                        await expect(menu.getByRole('link', { name: 'Modules', exact: true })).toBeVisible();
                        expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width + 1);
                    }
                }
            }
        }
    });
}


test('challenge filters stay out of the way until needed and retain the selected topic', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await page.goto('/challenges');
    const filters = page.getByText('More filters', { exact: true });
    const topic = page.getByLabel('Topic', { exact: true });
    await expect(topic).toBeHidden();
    await filters.focus();
    await filters.press('Enter');
    await topic.selectOption('collections');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    await expect(topic).toBeVisible();
    await expect(topic).toHaveValue('collections');
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(321);
    await page.getByRole('link', { name: 'Clear filters' }).click();
    await expect(topic).toBeHidden();
});
