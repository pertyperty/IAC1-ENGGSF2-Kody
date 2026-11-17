import { test, expect } from '@playwright/test';
import { audit } from './support/audit.mjs';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => { localStorage.setItem('kody-tour-v1', 'dismissed'); localStorage.setItem('kody-companion-v1', JSON.stringify({ hidden: true })); });
});

test('Garden teaching is before the board and previews never submit or unlock guest progress', async ({ page }) => {
    await page.goto('/tower/1');
    const game = page.locator('[data-coding-game]');
    await expect(game.locator('.lesson-briefing')).toContainText('Explain how ordered');
    await game.locator('[data-command=right]').click();
    await expect(game.locator('.trace-source')).toContainText('MOVE RIGHT');
    const submissions = [];
    page.on('request', request => { if (request.method() === 'POST') submissions.push(request.url()); });
    await game.getByRole('button', { name: 'Step preview', exact: true }).click();
    await expect(game.locator('.trace-state')).toContainText('column 2, row 3');
    await expect(game.locator('[data-game-board]')).toHaveAttribute('aria-label', /column 2, row 3/);
    await game.getByRole('button', { name: 'Restart preview', exact: true }).click();
    await expect(game.locator('.trace-state')).toContainText('column 1, row 3');
    expect(submissions).toEqual([]);
    expect(await page.evaluate(() => localStorage.getItem('kody-guest-tower-v1'))).toBeNull();
    for (const width of [1440, 390]) {
        await page.setViewportSize({ width, height: 1000 });
        await audit(page);
        await page.screenshot({ path: `storage/app/browser-results/teaching-garden-${width}.png`, fullPage: true });
    }
});

test('new registration explains its four inputs without collecting names or public credentials', async ({ page }) => {
    await page.goto('/register');
    await expect(page.locator('input[name=first_name], input[name=last_name], input[type=file], select[name=account_type]')).toHaveCount(0);
    await expect(page.getByLabel('Username (required)', { exact: true })).toHaveAttribute('aria-describedby', 'username-help');
    await expect(page.locator('#username-help')).toContainText('case-sensitive');
    await page.setViewportSize({ width: 390, height: 900 });
    await audit(page);
});

test('registration server errors link to the correct field without changing its accessible label', async ({ page }) => {
    await page.goto('/register');
    await page.getByLabel('Username (required)', { exact: true }).fill('browser_tracklearner');
    await page.getByLabel('Email address (required)', { exact: true }).fill('duplicatecheck@browser.example.test');
    await page.getByLabel('Password (required)', { exact: true }).fill('BrowserStrong12!');
    await page.getByLabel('Confirm password (required)', { exact: true }).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Create account/ }).click();
    const error = page.locator('[data-field-error=username]');
    await expect(error).toContainText('already been taken');
    await expect(page.getByLabel('Username (required)', { exact: true })).toHaveAttribute('aria-invalid', 'true');
    await error.getByRole('link').click();
    await expect(page.getByLabel('Username (required)', { exact: true })).toBeFocused();
    await audit(page);
});

for (const [template, command, expected] of [
    ['pixel-studio', 'paint 1 1 mint', '(1, 1) = mint'],
    ['number-machine', 'add 3', 'value = 5'],
    ['sort-lab', 'swap 1 2', 'items = [1, 3, 2]'],
    ['terminal-quest', 'cat hello.txt', 'hello.txt:'],
    ['logic-switchboard', 'flip 1', 'TRUE AND FALSE = FALSE'],
    ['string-forge', 'upper', 'text = "KODY"'],
    ['memory-trail', 'push 2', 'stack = [1, 2]'],
]) {
    test(`${template} shows the same program and state in mobile preview without submitting`, async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 950 });
        await page.goto(`/playground?game=${template}`);
        const requests = [];
        page.on('request', request => { if (request.method() === 'POST') requests.push(request.url()); });
        const game = page.locator('[data-arcade-game]');
        await game.locator('.arcade-source summary').click();
        await game.locator('[data-arcade-code]').fill(command);
        await game.getByRole('button', { name: 'Step preview', exact: true }).click();
        await expect(game.locator('.trace-state')).toContainText(expected);
        await expect(game.locator('.trace-source [aria-current=step]')).toHaveCount(1);
        await game.getByRole('button', { name: 'Replay preview', exact: true }).click();
        await expect(game.locator('.trace-state')).toContainText(expected);
        expect(requests).toEqual([]);
        await audit(page);
        if (template === 'terminal-quest') {
            await page.locator('[data-theme-toggle]').focus();
            await page.keyboard.press('Enter');
            await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'true');
            await audit(page);
        }
    });
}

test('Intermediate practice is independently enterable while its later levels stay locked', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill('browser_tracklearner');
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await expect(page).toHaveURL(/\/$/);
    await page.getByText('Choose a skill track', { exact: true }).click();
    await page.getByRole('link', { name: /Intermediate practice/ }).click();
    await expect(page).toHaveURL(/\?track=intermediate$/);
    await page.locator('[data-tower-continue]').click();
    await expect(page).toHaveURL(/\/tower\/26$/);
    const game = page.locator('[data-arcade-game]');
    await game.getByRole('button', { name: 'Flip input 1', exact: true }).click();
    await expect(game.locator('.trace-source')).toContainText('input[1] = NOT input[1]');
    await game.getByRole('button', { name: 'Step preview', exact: true }).click();
    await expect(game.locator('.trace-state')).toContainText('TRUE AND TRUE AND FALSE = FALSE');
    await expect(game.locator('[data-arcade-output]')).not.toContainText('saved');
    for (const width of [1440, 390]) { await page.setViewportSize({ width, height: 1000 }); await audit(page); }
    const response = await page.goto('/tower/27');
    expect(response.status()).toBe(403);
});
