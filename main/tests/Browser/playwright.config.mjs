import { defineConfig, devices } from '@playwright/test';
import { resolve } from 'node:path';

if (process.env.KODY_BROWSER_ISOLATED !== '1' || !/^kody_browser_[a-f0-9]{32}$/.test(process.env.DB_DATABASE || '')) {
    throw new Error('Run npm run test:browser to provision an isolated testing database.');
}
export default defineConfig({
    testDir: '.', testMatch: '*.spec.mjs', fullyParallel: false, workers: 1,
    timeout: 60_000, expect: { timeout: 10_000 }, retries: 0,
    reporter: [['list'], ['html', { outputFolder: resolve(import.meta.dirname, '../../storage/app/browser-report'), open: 'never' }]],
    outputDir: resolve(import.meta.dirname, '../../storage/app/browser-results'),
    use: { baseURL: process.env.KODY_BROWSER_BASE_URL, serviceWorkers: 'block', screenshot: 'only-on-failure', trace: 'off' },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
