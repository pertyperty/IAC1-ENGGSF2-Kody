import { expect } from '@playwright/test';

// Fixtures may retain an active session from an earlier browser context.
// Exercise the actual takeover screen rather than bypassing session protection.
export async function finishSignIn(page) {
    await expect(page).toHaveURL(/\/(?:dashboard|tower\/\d+|login\/confirmation)?$/);
    if (page.url().endsWith('/login/confirmation')) {
        await page.getByRole('button', { name: 'End previous session and continue', exact: true }).click();
    }
    await expect(page).toHaveURL(/\/(?:dashboard|tower\/\d+)?$/);
    // Most returning-account journeys continue through the full page navigation.
    // Focused play itself has dedicated default-entry coverage.
    if (await page.locator('[data-focus-default]').count()) {
        await page.getByRole('button', { name: 'Exit focus', exact: true }).click();
        await expect(page.locator('.focus-game-shell')).toHaveCount(0);
    }
}
