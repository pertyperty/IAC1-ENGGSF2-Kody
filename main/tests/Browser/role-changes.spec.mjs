import { test, expect } from '@playwright/test';
import { resolve } from 'node:path';
import { audit } from './support/audit.mjs';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => { localStorage.setItem('kody-tour-v1', 'dismissed'); localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true })); });
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});
async function login(page, identifier) {
    await page.goto('/login'); await page.getByLabel('Email or username').fill(identifier); await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', {name:/Sign in/}).click(); await page.waitForURL(/\/(?:dashboard|login\/confirmation)?$/);
    if (page.url().endsWith('/login/confirmation')) await page.getByRole('button', {name:'End previous session and continue'}).click();
}
const document = resolve('database/fixtures/lesson-resources/programming-foundations.pdf');

test('ready Learner 4 applies through the real form and Moderator approval reveals the Contributor mascot transition live', async ({page,browser}) => {
    await login(page,'kody_learner_4'); await page.goto('/account/contributor-application');
    await expect(page.getByText('Local role-review demonstration')).toBeVisible();
    await page.getByLabel('Why would you like to contribute?').fill('LOCAL DEMO: I want to build coding quests. The attached Kody worksheet is a mock supporting file.');
    await page.getByLabel('Supporting credentials').setInputFiles(document); await page.getByLabel('I confirm these application details.').check();
    await page.getByRole('button',{name:'Submit for review'}).click(); await expect(page.locator('.toast')).toContainText('awaiting review');
    const context=await browser.newContext(); const staff=await context.newPage();
    try {
        await login(staff,'browser_moderator'); await staff.goto('/manage/contributors');
        await staff.locator('main a[href*="/manage/contributors/"]').first().click();
        await staff.getByRole('combobox',{name:/^Decision/}).selectOption('Approved'); await staff.getByLabel('I reviewed the application and confirm this decision.').check();
        await staff.getByRole('button',{name:'Confirm decision'}).click();
        const dialog=page.locator('[data-role-update]'); await expect(dialog).toBeVisible({timeout:25000});
        await expect(dialog.getByRole('heading',{name:'You’re now a Contributor'})).toBeVisible();
        await expect(dialog.locator('img')).toHaveAttribute('src',/celebration.webp$/); await audit(page);
        await page.screenshot({path:'storage/app/browser-results/contributor-role-celebration.png'});
        await dialog.getByRole('button',{name:'Explore your quest studio'}).click(); await expect(page).toHaveURL(/\/create\/challenges$/);
        await expect(page.locator('[data-role-update]')).not.toBeVisible(); await page.reload(); await expect(page.locator('[data-role-update]')).not.toBeVisible();
        await page.goto('/updates'); await expect(page.getByText('Contributor access approved',{exact:true})).toBeVisible();
    } finally { await context.close(); }
});

test('ready Learner 5 can become an Instructor with an Administrator decision and a mobile reduced-motion welcome', async ({page,browser}) => {
    await page.emulateMedia({reducedMotion:'reduce'}); await page.setViewportSize({width:390,height:844});
    await login(page,'kody_learner_5'); await page.goto('/account/creator-application');
    await page.getByLabel('Institution',{exact:true}).fill('Kody local demonstration studio'); await page.getByLabel('Specialization',{exact:true}).fill('Programming fundamentals');
    await page.getByLabel('Teaching credentials').setInputFiles(document); await page.getByLabel('I confirm these credentials are ready for review.').check();
    await page.getByRole('button',{name:'Apply to become a creator'}).click();
    const context=await browser.newContext(); const staff=await context.newPage();
    try {
        await login(staff,'browser_administrator'); await staff.goto('/manage/instructors');
        await staff.locator('main a[href*="/manage/instructors/"]').first().click();
        await staff.getByRole('combobox',{name:/^Decision/}).selectOption('Approved'); await staff.getByLabel('I have manually reviewed the credentials and their credibility.').check();
        await staff.getByRole('button',{name:/Save review/}).click();
        const dialog=page.locator('[data-role-update]'); await expect(dialog).toBeVisible({timeout:25000});
        await expect(dialog.getByRole('heading',{name:'You’re now an Instructor'})).toBeVisible(); await audit(page);
        await expect(dialog).toHaveCSS('animation-name','none'); await page.screenshot({path:'storage/app/browser-results/instructor-role-celebration-mobile.png'});
        await dialog.getByRole('button',{name:'Go to your overview',exact:true}).last().click(); await expect(page).toHaveURL(/\/dashboard$/);
        await expect(dialog).not.toBeVisible(); await page.goto('/dashboard'); await expect(page.locator('.creator-insights')).toBeVisible();
    } finally { await context.close(); }
});

