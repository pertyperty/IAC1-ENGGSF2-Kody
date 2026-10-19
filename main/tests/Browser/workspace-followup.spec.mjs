import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => { localStorage.setItem('kody-tour-v1', 'dismissed'); localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true })); });
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});
async function login(page, name) {
    await page.goto('/login'); await page.getByLabel('Email or username').fill(`browser_${name}`); await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await page.waitForURL(/\/(?:dashboard|login\/confirmation)$/);
    if (page.url().endsWith('/login/confirmation')) await page.getByRole('button', { name: 'End previous session and continue' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

test('instructor insights are owned graphical records and Tower play preserves the creator workspace', async ({ page }) => {
    await login(page, 'insightcreator');
    await expect(page.locator('.creator-metrics article').nth(0).locator('b')).toHaveText('1');
    await expect(page.locator('.creator-metrics article').nth(1).locator('b')).toHaveText('1');
    await expect(page.locator('.insight-plot')).toHaveCount(2);
    await page.getByLabel('Reporting window').selectOption('7'); await page.getByRole('button', { name: 'Update charts' }).click();
    await expect(page).toHaveURL(/insight_days=7/); await expect(page.locator('.creator-chart').first().locator('.chart-bar')).toHaveCount(7);
    await page.locator('.creator-chart').first().getByText('View daily totals', { exact: true }).click(); await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/creator-insights-desktop.png', fullPage: true });
    await page.getByRole('link', { name: 'Play the Tower', exact: false }).click(); await expect(page.locator('[data-tower-stop]')).toHaveCount(80);
    await page.locator('[data-tower-stop="1"] .tower-node').click();
    for (const direction of ['right', 'right', 'up', 'right', 'right']) await page.getByRole('button', { name: `Add ${direction}`, exact: true }).click();
    await page.locator('[data-game-run]').click(); await expect(page.locator('[data-game-result]')).toBeVisible();
    // Another journey publishes a second stage on floor 1; exercise that approved revision too.
    const secondStage = page.locator('[data-tower-stage="1"]');
    if (await secondStage.count()) {
        await page.locator('[data-result-return]').click(); await expect(secondStage).toBeVisible();
        await secondStage.getByRole('radio').first().check(); await secondStage.getByRole('button', { name: 'Check my idea', exact: true }).click();
    }
    await expect(page.locator('[data-tower-victory]')).toBeVisible();
    await expect(page.locator('[data-progress-level]')).toHaveText('1 / 80');
    await page.goto('/dashboard'); await expect(page.locator('[data-progress-strip]')).toHaveCount(0);
    await page.setViewportSize({ width: 320, height: 740 }); await audit(page);
});

test('quiz questions stay in a bounded editor with accessible collapsible controls', async ({ page }) => {
    await login(page, 'insightcreator'); await page.goto('/create/modules/new?example=quiz-lab');
    const root = page.locator('[data-quiz-editor]'), list = root.locator('[data-quiz-questions]');
    const initial = (await list.boundingBox()).height;
    for (let index = 0; index < 7; index++) await root.locator('[data-add-question]').click();
    await expect(root.locator('[data-quiz-question-editor]')).toHaveCount(10);
    await expect(root.locator('[data-quiz-question-editor]:not(.is-collapsed)')).toHaveCount(1);
    expect((await list.boundingBox()).height).toBeLessThanOrEqual(Math.max(initial, page.viewportSize().height * .65) + 10);
    await expect(root.locator('[data-add-question]')).toBeDisabled();
    await root.locator('[data-question-toggle]').first().click(); await expect(root.locator('[data-quiz-question-editor]').first()).not.toHaveClass(/is-collapsed/);
    await root.locator('[data-quiz-question-editor]').first().locator('[data-question-field="question"]').fill('A question that stays editable.');
    await page.setViewportSize({ width: 390, height: 844 }); await audit(page);
});

test('Administrator announcement preview saves safely and operations panels remain responsive', async ({ page }) => {
    await login(page, 'siteadmin'); await page.goto('/manage/site');
    await page.getByLabel('Title', { exact: true }).fill('Browser support notice');
    await page.getByLabel('Message', { exact: true }).fill('A fresh guide is available in Help.');
    await expect(page.locator('[data-notice-preview-title]')).toHaveText('Browser support notice');
    await page.getByLabel('Destination').selectOption('help'); await page.getByLabel('Visibility').selectOption('1');
    await page.getByLabel('Current password').fill('BrowserStrong12!'); await page.getByLabel(/I confirm this action/).check();
    await page.getByRole('button', { name: 'Save announcement' }).click();
    try {
        await page.goto('/help'); await expect(page.locator('.site-announcement')).toContainText('Browser support notice'); await audit(page);
        await page.goto('/manage/site'); await page.setViewportSize({ width: 390, height: 844 }); await audit(page);
        await page.screenshot({ path: 'storage/app/browser-results/site-controls-mobile.png', fullPage: true });
    } finally {
        await page.goto('/manage/site'); await page.getByLabel('Visibility').selectOption('0'); await page.getByLabel('Current password').fill('BrowserStrong12!'); await page.getByLabel(/I confirm this action/).check(); await page.getByRole('button', { name: 'Save announcement' }).click();
    }
});

test('welcome support and wallet tiers have clear responsive destinations without in-site email', async ({ page }) => {
    for (const width of [1920, 390, 320]) {
        await page.setViewportSize({ width, height: 900 }); await page.goto('/welcome'); await audit(page);
        await expect(page.getByRole('link', { name: 'Explore courses', exact: false })).toBeVisible();
        await page.goto('/contact'); await expect(page.getByRole('link', { name: 'Email an appeal' })).toHaveAttribute('href', /^mailto:kody.support@gmail.com\?subject=/); await audit(page);
    }
    await page.goto('/login'); await page.getByLabel('Email or username').fill('browser_appeallearner'); await page.getByLabel(/^Password/).fill('BrowserStrong12!'); await page.getByRole('button', { name: /Sign in/ }).click();
    await expect(page).toHaveURL(/\/login$/); await expect(page.getByRole('link', { name: /appeal a suspension/ })).toHaveAttribute('href', /\/contact#appeals$/);
    await login(page, 'siteadmin'); await page.goto('/wallet'); await expect(page.locator('.wallet-package')).toHaveCount(3);
    await expect(page.locator('.package-coins img')).toHaveCount(6); await expect(page.locator('.wallet-package .kodebit-value img')).toHaveCount(3);
    await expect(page.locator('.header-wallet img')).toHaveCount(1);
    await audit(page); await page.screenshot({ path: 'storage/app/browser-results/wallet-distinct-packages.png', fullPage: true });
});

test('page spotlight follows rectangular elements and companion hides and returns smoothly', async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 1000 }); await page.goto('/contact');
    await page.getByRole('button', { name: 'Kody guide and companion' }).click(); const tour = page.locator('[data-kody-tour]');
    await tour.locator('[data-tour-next]').click();
    const heading = await page.locator('main h1').boundingBox(); const hole = tour.locator('[data-tour-hole]');
    expect(Number(await hole.getAttribute('width'))).toBeGreaterThan(heading.width);
    expect(Number(await hole.getAttribute('height'))).toBeLessThan(heading.height + 16);
    expect(Math.abs(Number(await hole.getAttribute('x')) - Math.max(6, heading.x - 6))).toBeLessThan(1);
    await audit(page); await page.screenshot({ path: 'storage/app/browser-results/element-spotlight-desktop.png' });
    await tour.locator('[data-tour-skip]').click(); const pet = page.locator('[data-companion]'); await expect(pet).toBeVisible();
    await pet.locator('[data-companion-handle]').click(); await pet.locator('[data-companion-hide]').click(); await expect(pet).toBeHidden();
    await page.getByRole('button', { name: 'Kody guide and companion' }).click(); await tour.locator('[data-tour-skip]').click(); await expect(pet).toBeVisible();
    await page.setViewportSize({ width: 390, height: 844 }); await audit(page);
});
