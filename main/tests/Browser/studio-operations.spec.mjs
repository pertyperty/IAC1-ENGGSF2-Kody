import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { audit } from './support/audit.mjs';

const manifest = JSON.parse(readFileSync(process.env.KODY_BROWSER_MANIFEST, 'utf8'));
test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => { localStorage.setItem('kody-tour-v1', 'dismissed'); localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true })); });
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});
async function login(page, name, learner = false) {
    await page.goto('/login'); await page.getByLabel('Email or username').fill(`browser_${name}`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!'); await page.getByRole('button', { name: /Sign in/ }).click();
    await page.waitForURL(/\/(?:dashboard|login\/confirmation)?$/);
    if (page.url().endsWith('/login/confirmation')) {
        await page.getByRole('button', { name: 'End previous session and continue' }).click();
    }
    await expect(page).toHaveURL(learner ? /\/$/ : /\/dashboard$/);
}

test('visual scenario editors preserve typed content focus and previews without duplicate controls', async ({ page }) => {
    await login(page, 'polishcreator'); await page.goto('/create/modules/new?example=pixel-studio');
    const visual = page.locator('[data-scenario-canvas]');
    await expect(visual.locator('.designer-pixel-grid button')).toHaveCount(9);
    await visual.getByRole('button', { name: 'peach', exact: true }).click();
    await visual.locator('.designer-pixel-grid button').last().click();
    const scenario = page.locator('input[name="game_scenario"]');
    await expect.poll(async () => JSON.parse(await scenario.inputValue()).pixels).toContain('3 3 peach');
    const steps = page.locator('.editor-jumps'); await steps.getByRole('link').last().click();
    const stepBox = await steps.boundingBox(), backBox = await page.locator('.page-navigation').boundingBox();
    expect(backBox.y + backBox.height).toBeLessThanOrEqual(stepBox.y + 1);
    await page.getByLabel('Game template').selectOption('terminal-quest');
    const content = visual.getByLabel('File 1 contents');
    await content.fill('Typed once, used immediately.');
    await expect(content).toBeFocused();
    await visual.getByRole('button', { name: 'Use contents as goal' }).first().click();
    await expect.poll(async () => JSON.parse(await scenario.inputValue()).content).toBe('Typed once, used immediately.');
    await page.getByRole('button', { name: 'Try your scenario' }).click();
    await expect(page.locator('[data-scenario-preview-host] [data-arcade-run]')).toHaveCount(1);
    await page.getByRole('button', { name: 'Try your scenario' }).click();
    await expect(page.locator('[data-scenario-preview-host] [data-arcade-run]')).toHaveCount(1);
    await audit(page); await page.screenshot({ path: 'storage/app/browser-results/visual-creator-desktop.png', fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 }); await audit(page);
});

test('private Office resources render pages in adjustable split and fullscreen previews', async ({ page }) => {
    await login(page, 'polishlearner', true); await page.setViewportSize({ width: 1920, height: 1000 });
    await page.goto(`/learn/modules/${manifest.modules['welcome-code']}`);
    const viewer = page.locator('[data-resource-viewer]');
    const resource = extension => page.locator('.resource-card').filter({ hasText: `programming-foundations.${extension}` }).getByRole('link', { name: 'View', exact: true });
    await resource('docx').click();
    const office = page.frameLocator('[data-resource-frame]');
    await expect(office.locator('[data-office-status]')).toContainText('Document preview.');
    await expect(office.locator('.docx-wrapper section.docx')).toHaveCount(1);
    await expect(office.locator('[data-office-pages]')).not.toHaveAttribute('aria-busy', 'true');
    const initial = (await viewer.boundingBox()).width;
    await page.locator('[data-resource-width]').fill('65');
    await expect.poll(async () => (await viewer.boundingBox()).width).toBeGreaterThan(initial + 100);
    await viewer.locator('[data-resource-close]').click(); await resource('pptx').click();
    await expect(office.locator('[data-office-status]')).toContainText('Document preview.');
    await expect(office.locator('[data-office-pages] img')).toHaveCount(3);
    await expect.poll(() => office.locator('[data-office-pages] img').first().evaluate(image => image.naturalWidth)).toBeGreaterThan(100);
    await viewer.locator('[data-resource-enlarge]').click(); expect((await viewer.boundingBox()).width).toBe(1920);
    await page.screenshot({ path: 'storage/app/browser-results/powerpoint-rendered-preview.png' });
    await page.keyboard.press('Escape'); await page.setViewportSize({ width: 390, height: 844 });
    await resource('docx').click(); await expect(office.locator('[data-office-status]')).toContainText('Document preview.');
    expect((await viewer.boundingBox()).width).toBe(390); await audit(page);
    const original = await viewer.locator('[data-resource-original]').getAttribute('href');
    await viewer.locator('[data-resource-close]').click(); await page.goto(original);
    await page.locator('[data-document-minimize]').click(); await expect(page.locator('[data-scroll-navigation]')).toHaveClass(/navigation-hidden/);
    await page.locator('[data-document-fullscreen]').click(); await expect(page.locator('[data-document-stage]')).toHaveClass(/is-expanded/);
    await page.locator('[data-document-fullscreen]').click(); await expect(page.locator('[data-document-stage]')).not.toHaveClass(/is-expanded/);
    await audit(page);
});

test('article completion shows shared progress XP wallet and readable reaction counts', async ({ page }) => {
    await login(page, 'polishlearner', true); const id = manifest.modules['welcome-code'];
    await page.goto(`/learn/modules/${id}`); await page.getByRole('button', { name: 'Mark as read', exact: true }).click();
    await expect(page.locator('[data-progress-xp]')).toHaveText('40');
    await expect(page.locator('[data-wallet-available]')).toHaveText('0');
    const reaction = page.locator('main [data-content-reactions]').getByRole('button', { name: /^Like/ });
    await reaction.click(); await expect(reaction).toHaveAttribute('aria-pressed', 'true');
    await expect(reaction).toHaveAccessibleName('Like · 1 reactions');
    await audit(page); await page.getByRole('button', { name: /Dismiss notification/ }).first().click();
    await page.goto('/learn?q=What%20is%20a%20program'); await expect(page.getByText('Completed · article read', { exact: true })).toBeVisible();
    await page.goto('/wallet'); await expect(page.getByText('Live checkout is being prepared.', { exact: false })).toBeVisible();
    for (const width of [1920, 390, 320]) { await page.setViewportSize({ width, height: 900 }); await audit(page); }
    await page.screenshot({ path: 'storage/app/browser-results/wallet-mobile-polish.png', fullPage: true });
});

test('Administrator account browser defaults to ten and reports export formatted files', async ({ page }) => {
    await login(page, 'polishadmin'); await page.goto('/manage/accounts?reset=1');
    await expect(page.locator('.account-results .account-result')).toHaveCount(10);
    await page.getByRole('combobox', { name: 'Browse', exact: true }).selectOption('all');
    await page.getByRole('combobox', { name: 'Results per page', exact: true }).selectOption('20'); await page.getByRole('button', { name: 'Apply filters' }).click();
    await expect(page.locator('.account-results .account-result')).toHaveCount(20);
    await page.goto('/manage/reports?type=overview'); await expect(page.locator('.report-insight')).toHaveCount(4);
    for (const [name, extension] of [['PDF', 'pdf'], ['CSV', 'csv'], ['Excel', 'xlsx']]) {
        const download = page.waitForEvent('download'); await page.getByRole('link', { name, exact: true }).click();
        const file = await download; expect(file.suggestedFilename()).toMatch(new RegExp(`\\.${extension}$`));
        if (extension === 'pdf') await file.saveAs('storage/app/browser-results/platform-report.pdf');
    }
    await audit(page); await page.screenshot({ path: 'storage/app/browser-results/admin-reports-desktop.png', fullPage: true });
    await page.setViewportSize({ width: 320, height: 740 }); await audit(page);
});
