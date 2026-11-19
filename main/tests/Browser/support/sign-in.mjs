import { expect } from '@playwright/test';

// Fixtures may retain an active session from an earlier browser context.
// Exercise the actual takeover screen rather than bypassing session protection.
export async function finishSignIn(page) {
    await expect(page).toHaveURL(/\/(?:dashboard|login\/confirmation)?$/);
    if (page.url().endsWith('/login/confirmation')) {
        await page.getByRole('button', { name: 'End previous session and continue', exact: true }).click();
    }
    await expect(page).toHaveURL(/\/(?:dashboard)?$/);
}
