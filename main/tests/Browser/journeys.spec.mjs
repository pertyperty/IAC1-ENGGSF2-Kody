import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { readFileSync } from 'node:fs';
import { spawnSync } from 'node:child_process';

const manifest = JSON.parse(readFileSync(process.env.KODY_BROWSER_MANIFEST, 'utf8'));
function fixture(operation, email) {
    const result = spawnSync(process.env.KODY_TEST_PHP, ['tests/Browser/support/fixture.php', operation, email], { encoding: 'utf8', windowsHide: true, timeout: 30_000 });
    expect(result.status, result.stderr).toBe(0);
    return result.stdout;
}
async function login(page, role) {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(`${role}@browser.example.test`);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: 'Sign in', exact: false }).click();
    const creatorOrStaff = ['instructor','moderator','administrator','navinstructor','navmoderator','navadministrator','navcontributor','toweradmin'].includes(role);
    await expect(page).toHaveURL(creatorOrStaff ? /\/dashboard$/ : /\/$/);
    if (!creatorOrStaff) await page.goto('/dashboard');
}
async function audit(page) {
    await page.evaluate(async () => {
        await Promise.all(document.getAnimations().filter(animation => animation.effect?.getComputedTiming().iterations !== Infinity)
            .map(animation => animation.finished.catch(() => {})));
    });
    const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
    expect(result.violations.map(({ id, nodes }) => ({ id, targets: nodes.map(node => ({ target: node.target, detail: node.failureSummary })) }))).toEqual([]);
    const overflow = await page.evaluate(() => ({ width: window.innerWidth, scroll: document.documentElement.scrollWidth,
        elements: [...document.querySelectorAll('body *')].filter(element => element.getBoundingClientRect().right > window.innerWidth + 1)
            .slice(0, 15).map(element => `${element.tagName}.${element.className}`) }));
    expect(overflow.scroll, JSON.stringify(overflow)).toBeLessThanOrEqual(overflow.width + 1);
}
async function accountLink(page, name) {
    await page.locator('.account-menu > summary').click();
    await page.getByRole('navigation', { name: 'Account navigation', exact: true }).getByRole('link', { name, exact: true }).click();
}

test('navigation hides on downward scroll and returns by upward scroll, hover and keyboard without trapping menus', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 800 });
    await page.goto('/');
    const shell = page.locator('[data-scroll-navigation]');
    const header = page.locator('.app-header');
    await page.screenshot({ path: 'storage/app/browser-results/polished-landing.png' });
    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Skip to content' })).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page.locator('#main-content')).toBeFocused();
    await expect(header.getByRole('link', { name: 'Kody home', exact: true })).toBeVisible();
    await expect(page.locator('[data-tower-stop]')).toHaveCount(25);
    await page.mouse.move(700, 600);
    await page.mouse.wheel(0, 650);
    await expect(shell).toHaveClass(/navigation-hidden/);
    await expect(page.getByRole('button', { name: 'Show navigation' })).toBeVisible();
    await page.mouse.wheel(0, -100);
    await expect(shell).not.toHaveClass(/navigation-hidden/);
    await page.mouse.wheel(0, 150);
    await expect(shell).toHaveClass(/navigation-hidden/);
    await page.mouse.move(10, 4);
    await expect(shell).not.toHaveClass(/navigation-hidden/);
    await page.mouse.move(700, 600);
    await page.mouse.wheel(0, 150);
    await expect(shell).toHaveClass(/navigation-hidden/);
    await page.getByRole('button', { name: 'Show navigation' }).focus();
    expect(await page.getByRole('button', { name: 'Show navigation' }).evaluate(element => {
        const box = element.getBoundingClientRect();
        return element.contains(document.elementFromPoint(box.x + box.width / 2, box.y + box.height / 2));
    })).toBe(true);
    await page.keyboard.press('Enter');
    await expect(shell).not.toHaveClass(/navigation-hidden/);
    await expect(header.getByRole('link', { name: 'Kody home' })).toBeFocused();
    await page.mouse.wheel(0, 100);
    await expect(header).toBeInViewport();
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await audit(page);
});

test('workspace navigation sticks on short desktops and touch layouts; expanded mobile menus stay reachable', async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width: 1440, height: 600 }, isMobile: true, hasTouch: true, reducedMotion: 'reduce' });
    try {
        const page = await context.newPage();
        await context.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
        await login(page, 'stickylearner');
        await page.goto('/learn/sequences');
        await page.evaluate(() => window.scrollTo(0, 500));
        await expect(page.locator('.app-rail')).toBeInViewport();
        await expect.poll(async () => (await page.locator('.app-rail').boundingBox()).y).toBeGreaterThanOrEqual(0);
        await expect.poll(async () => (await page.locator('.app-rail').boundingBox()).y).toBeLessThan(160);
        await page.setViewportSize({ width: 320, height: 740 });
        await page.goto('/learn/sequences');
        await page.evaluate(() => window.scrollTo(0, 450));
        const workspace = page.locator('.mobile-workspace');
        await expect(workspace.locator('summary')).toBeInViewport();
        await workspace.locator('summary').click();
        await expect(workspace).toHaveAttribute('open', '');
        await audit(page);
        await workspace.getByRole('link', { name: 'Help center', exact: true }).click();
        await expect(page).toHaveURL(/\/help$/);
        await audit(page);
    } finally { await context.close(); }
});
async function solveGarden(page, saved = true) {
    const game = page.locator('[data-coding-game]');
    for (const direction of ['right', 'right', 'up', 'right', 'right']) await game.getByRole('button', { name: `Add ${direction}`, exact: true }).click();
    await game.getByRole('button', { name: 'Run my code', exact: true }).click();
    await expect(game.locator('[data-game-success]')).toBeVisible();
    if (saved) await expect(game.locator('[data-game-feedback]')).toContainText('saved');
}
test.beforeEach(async ({ page }) => {
    // Acceptance tests cannot accidentally visit or contact a live provider.
    await page.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
});

test('clear back buttons, dismissible errors and persistent progress work through account and mobile navigation', async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('link', { name: 'Back to home', exact: false }).click();
    await expect(page).toHaveURL(/\/$/);
    await page.goto('/login');
    await page.getByLabel('Email or username').fill('missing@browser.example.test');
    await page.getByLabel('Password', { exact: true }).fill('BrowserStrong12!');
    await page.getByRole('button', { name: 'Sign in', exact: false }).click();
    const error = page.locator('.toast-error');
    await expect(error).toBeVisible();
    await error.getByRole('button', { name: 'Dismiss notification' }).focus();
    await page.keyboard.press('Enter');
    await expect(error).toHaveCount(0);
    await login(page, 'usabilitylearner');
    const strip = page.locator('[data-progress-strip]');
    await page.mouse.move(700, 700);
    await page.mouse.wheel(0, 600);
    await expect(strip).toBeInViewport();
    await page.goto('/account/edit');
    await expect(strip).toHaveCount(0);
    await page.getByRole('navigation', { name: 'Page navigation' }).getByRole('link', { name: 'Back to my account', exact: false }).click();
    await expect(page).toHaveURL(/\/account$/);
    await expect(strip).toBeVisible();
    await page.setViewportSize({ width: 320, height: 740 });
    const menu = page.locator('.mobile-workspace');
    await menu.locator('summary').click();
    await page.keyboard.press('Escape');
    await expect(menu).not.toHaveAttribute('open', '');
    await expect(menu.locator('summary')).toBeFocused();
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/learner-progress-mobile.png' });
    const notify = () => page.evaluate(() => document.dispatchEvent(new CustomEvent('kody:feedback', {
        detail: { message: '<img src=x onerror=alert(1)> Saved safely', kind: 'success' },
    })));
    await notify();
    const notice = page.locator('.toast-success');
    await expect(notice).toContainText('<img src=x onerror=alert(1)>');
    await expect(notice.locator('img')).toHaveCount(0);
    await notify();
    await expect(notice).toHaveCount(1);
    await notice.getByRole('button', { name: 'Dismiss notification' }).click();
    await notify();
    await expect(notice).toBeVisible();
});

test('staff workspace, account filters and password controls stay accessible in both themes and narrow layouts', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Password', { exact: true }).fill('BrowserStrong12!');
    await page.getByRole('button', { name: 'Show password', exact: true }).click();
    await expect(page.getByLabel('Password', { exact: true })).toHaveAttribute('type', 'text');
    await page.getByRole('button', { name: 'Hide password', exact: true }).click();
    await expect(page.getByLabel('Password', { exact: true })).toHaveAttribute('type', 'password');
    await login(page, 'administrator');
    await expect(page.getByRole('heading', { name: 'Help good learning happen.' })).toBeVisible();
    for (const width of [1280, 390, 320]) {
        await page.setViewportSize({ width, height: 900 });
        for (const dark of [false, true]) {
            const toggle = page.getByRole('button', { name: 'Dark mode', exact: true });
            if ((await toggle.getAttribute('aria-pressed') === 'true') !== dark) await toggle.click();
            await audit(page);
            await accountLink(page, 'Workspace');
            await expect(page.getByRole('heading', { name: 'Keep the community safe' })).toBeVisible();
            await page.getByLabel('Username', { exact: true }).fill('no_such_player');
            await page.getByRole('button', { name: 'Apply filters', exact: true }).click();
            await expect(page.getByRole('heading', { name: 'No accounts found' })).toBeVisible();
            await page.getByRole('link', { name: 'Clear filters', exact: true }).click();
            await audit(page);
            await accountLink(page, 'My account');
            await audit(page);
            await accountLink(page, 'Dashboard');
        }
    }
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.screenshot({ path: 'storage/app/browser-results/staff-workspace.png' });
    await page.getByRole('button', { name: 'Sign out', exact: true }).click();
    await expect(page).toHaveURL(/\/login$/);
});

test('A01 A02 B03 B05: guest trial, registration, verified play, reading and course assessment persist exactly once', async ({ page }) => {
    const email = 'newplayer@browser.example.test';
    await page.goto('/welcome');
    await expect(page.locator('[data-coding-game]')).toBeVisible();
    await audit(page);
    await solveGarden(page, false);
    await page.goto('/register');
    await page.getByLabel('First name').fill('Browser');
    await page.getByLabel('Last name').fill('Player');
    await page.getByLabel('Username').fill('browser_newplayer');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByLabel('Confirm password', { exact: true }).fill('BrowserStrong12!');
    await audit(page);
    await page.getByRole('button', { name: 'Create account' }).click();
    await expect(page).toHaveURL(/\/email\/verify/);
    const token = fixture('verify-token', email);
    await page.goto(`/email/verify#token=${encodeURIComponent(token)}`);
    await expect(page.getByRole('heading', { name: 'You’re verified' })).toBeVisible();
    await expect(page).not.toHaveURL(/#/);
    await login(page, 'newplayer');
    await expect(page.getByRole('heading', { name: 'Your mission board' })).toBeVisible();
    await page.setViewportSize({ width: 320, height: 900 });
    for (const dark of [false, true]) {
        const toggle = page.getByRole('button', { name: 'Dark mode' });
        if ((await toggle.getAttribute('aria-pressed')) !== String(dark)) await toggle.click();
        await audit(page);
    }
    await page.screenshot({ path: 'storage/app/browser-results/mission-board-mobile.png', fullPage: true });
    await page.setViewportSize({ width: 1280, height: 900 });
    expect(JSON.parse(fixture('snapshot', email))).toMatchObject({ xp: 0, levels: 0 });
    await page.goto('/learn/sequences');
    await solveGarden(page);
    expect(JSON.parse(fixture('snapshot', email))).toMatchObject({ xp: 20, levels: 1, streak: 1 });
    await expect(page.locator('[data-progress-xp]')).toHaveText('20');
    await expect(page.locator('[data-progress-streak]')).toHaveText('1');
    await expect(page.locator('[data-progress-level]')).toHaveText('0 / 25');
    await expect(page.locator('.toast-success')).toBeVisible();
    await page.locator('.toast-success').getByRole('button', { name: 'Dismiss notification' }).click();
    await expect(page.locator('.toast-success')).toHaveCount(0);
    const course = manifest.courses['first-programs'];
    await page.goto(`/learn/courses/${course.id}`);
    await page.getByRole('button', { name: 'Join this journey' }).click();
    await page.goto(`/learn/courses/${course.id}/modules/${course.slots[0]}`);
    await page.getByRole('button', { name: 'Mark as read' }).click();
    expect(JSON.parse(fixture('snapshot', email))).toMatchObject({ xp: 20, completed_lessons: 1 });
    await page.locator('[data-journey-next]').click();
    await expect(page).toHaveURL(new RegExp(`/modules/${course.slots[1]}$`));
    expect((await page.locator('[data-coding-game]').boundingBox()).width).toBeGreaterThan(800);
    await solveGarden(page);
    await expect(page.locator('[data-journey-next]')).toHaveAttribute('href', new RegExp(`/modules/${course.slots[2]}$`));
    await expect(page.locator('[data-journey-count]')).toHaveText('2 of 5 adventures completed.');
    await page.locator('[data-course-journey]').scrollIntoViewIfNeeded();
    await page.screenshot({ path: 'storage/app/browser-results/saved-course-next-step.png' });
    await page.reload();
    await solveGarden(page);
    expect(JSON.parse(fixture('snapshot', email))).toMatchObject({ xp: 60, xp_awards: 2, completed_lessons: 2, streak: 1 });
    await audit(page);
});

test('tower guest trials unlock locally and level four invites registration on desktop and mobile', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('[data-tower-stop="2"] a')).toHaveAttribute('aria-disabled', 'true');
    await page.locator('[data-tower-stop="1"] a').click();
    await solveGarden(page, false);
    await expect(page.locator('[data-tower-victory]')).toBeVisible();
    await page.locator('[data-tower-next]').click();
    await expect(page.locator('[data-tower-stop="2"] a')).toHaveAttribute('aria-disabled', 'false');
    await page.locator('[data-tower-stop="2"] a').click();
    const loop = page.locator('[data-coding-game]');
    for (const direction of ['right', 'up', 'right']) await loop.getByRole('button', { name: `Add ${direction}`, exact: true }).click();
    await loop.locator('[data-game-repeat]').check(); await loop.locator('[data-game-run]').click();
    await expect(page.locator('[data-tower-victory]')).toBeVisible();
    await page.locator('[data-tower-next]').click();
    await page.locator('[data-tower-stop="3"] a').click();
    await page.locator('[data-game-conditional]').check(); await solveGarden(page, false);
    await page.locator('[data-tower-next]').click(); await expect(page).toHaveURL(/\/welcome$/);
    await page.goto('/'); await page.setViewportSize({ width: 390, height: 844 });
    await audit(page); await page.locator('[data-tower-stop="4"] a').click();
    await expect(page).toHaveURL(/\/welcome$/);
});

test('learner username sign-in lands on the tower and saved wins refresh the deliberate HUD', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill('browser_towerlearner');
    await page.getByLabel(/^Password/).fill('BrowserStrong12!');
    await page.getByRole('button', { name: 'Sign in', exact: false }).click();
    await expect(page).toHaveURL(/\/$/);
    await expect(page.locator('.header-username')).toContainText('browser_towerlearner');
    await page.locator('[data-tower-stop="1"] a').click(); await solveGarden(page);
    await expect(page.locator('[data-progress-level]')).toContainText('1 / 25');
    await expect(page.locator('[data-tower-victory]')).toBeVisible();
    await page.reload(); await page.getByRole('link', { name: 'Level map', exact: false }).click();
    await expect(page.locator('[data-tower-stop="1"]')).toHaveClass(/is-cleared/);
    await expect(page.locator('[data-tower-stop="2"]')).not.toHaveClass(/is-locked/);
    await page.setViewportSize({ width: 320, height: 740 }); await audit(page);
});

test('Administrator rail pulls and pins labels; tower drafts preview without publishing and preserve changes', async ({ page }) => {
    await login(page, 'toweradmin');
    const rail = page.locator('[data-navigation-rail]');
    const railLinks = rail.locator('.workspace-nav a');
    await expect(rail.locator('.nav-icon svg')).toHaveCount(await railLinks.count());
    await expect(rail.locator('.nav-label')).toHaveCount(await railLinks.count());
    for (const icon of await rail.locator('.nav-icon').all()) {
        expect((await icon.boundingBox()).width).toBeGreaterThanOrEqual(22);
    }
    const tower = rail.getByRole('link', { name: 'Tower studio', exact: true });
    await tower.hover();
    await expect.poll(async () => (await tower.boundingBox()).width).toBeGreaterThan(200);
    await rail.locator('[data-rail-toggle]').click();
    await expect(page.locator('html')).toHaveAttribute('data-rail', 'expanded');
    await tower.click(); await page.locator('.tower-editor-card').first().click();
    await page.getByText('Customize stage 1', { exact: true }).click();
    await page.getByLabel('Stage title', { exact: true }).fill('Draft garden adventure');
    await page.getByRole('button', { name: 'Try unsaved stage', exact: true }).click();
    const preview = page.locator('.tower-draft-preview');
    await expect(preview.getByRole('heading', { name: 'Draft garden adventure' })).toBeVisible();
    await expect(preview.locator('[data-completion-url]')).toHaveCount(0);
    for (const direction of ['right', 'right', 'up', 'right', 'right']) await preview.getByRole('button', { name: `Add ${direction}`, exact: true }).click();
    await preview.getByRole('button', { name: 'Run my code', exact: true }).click();
    await expect(preview.locator('[data-game-success]')).toBeVisible();
    await page.screenshot({ path: 'storage/app/browser-results/tower-stage-editor.png', fullPage: true });
    await page.getByRole('button', { name: '+ A quick idea check', exact: true }).click();
    const quizStage = page.locator('.tower-stage-summary').last();
    await quizStage.getByText('Customize stage 2', { exact: true }).click();
    await quizStage.getByLabel('Question', { exact: true }).fill('Can instruction order change the result?');
    await quizStage.getByRole('button', { name: 'Try unsaved stage', exact: true }).click();
    const quizPreview = quizStage.locator('.tower-draft-preview');
    const editorUrl = page.url();
    await quizPreview.getByRole('radio').first().check();
    await quizPreview.getByRole('button', { name: 'Check my idea', exact: true }).click();
    await expect(quizPreview.locator('[data-quiz-feedback]')).toContainText('You got it');
    await expect(page).toHaveURL(editorUrl);
    await expect(page.locator('[name="record_version"]')).toHaveValue('1');
    await audit(page);
    await page.getByRole('button', { name: 'Publish level revision', exact: true }).click();
    await expect(page.locator('.toast')).toContainText('Tower revision published.');
    await page.reload(); await expect(page.locator('html')).toHaveAttribute('data-rail', 'expanded');
    await page.setViewportSize({ width: 390, height: 844 }); await audit(page);
});

test('D01 D05 D07: creator draft, staff review, course composition and learner publication use the real UI', async ({ browser }) => {
    const creator = await browser.newContext();
    const staff = await browser.newContext();
    const learner = await browser.newContext();
    try {
        const authorPage = await creator.newPage(); const reviewerPage = await staff.newPage(); const learnerPage = await learner.newPage();
        for (const context of [creator, staff, learner]) await context.route('**/*', route => new URL(route.request().url()).origin === process.env.KODY_BROWSER_BASE_URL ? route.continue() : route.abort());
        await login(authorPage, 'instructor');
        await authorPage.goto('/create/modules/new?example=programming-check');
        await authorPage.getByLabel('Adventure title').fill('Browser review adventure');
        await authorPage.getByLabel('Add PDF, DOCX or PPTX resources').setInputFiles({ name: 'browser-notes.docx', mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', buffer: Buffer.from(fixture('office-fixture'), 'base64') });
        await authorPage.getByLabel('Video or media link (optional)').fill('https://youtu.be/dQw4w9WgXcQ');
        await audit(authorPage);
        await authorPage.getByRole('button', { name: 'Save draft', exact: true }).click();
        await expect(authorPage).toHaveURL(/\/create\/modules\/\d+$/);
        const moduleId = new URL(authorPage.url()).pathname.split('/').at(-1);
        await authorPage.getByRole('button', { name: 'Submit saved draft for review' }).click();
        await login(reviewerPage, 'moderator');
        await reviewerPage.goto(`/manage/modules/${moduleId}`);
        await audit(reviewerPage);
        await reviewerPage.getByRole('button', { name: 'Approve and publish' }).click();
        await authorPage.goto('/create/courses/new');
        await authorPage.getByLabel('Course title').fill('Browser reviewed journey');
        await authorPage.getByLabel('The big idea').fill('An original programming knowledge check.');
        await authorPage.getByLabel('Category', { exact: true }).fill('Programming basics');
        await authorPage.getByRole('combobox', { name: 'Adventure 1', exact: true }).selectOption(moduleId);
        await authorPage.getByRole('button', { name: 'Save course draft', exact: true }).click();
        await expect(authorPage).toHaveURL(/\/create\/courses\/\d+$/);
        const courseId = new URL(authorPage.url()).pathname.split('/').at(-1);
        await authorPage.getByRole('button', { name: 'Submit saved course for review' }).click();
        await reviewerPage.goto(`/manage/courses/${courseId}`);
        await reviewerPage.getByRole('button', { name: 'Approve course publication' }).click();
        await authorPage.goto('/updates');
        const courseNotice = authorPage.locator('.lesson-note').filter({ hasText: 'Browser reviewed journey' });
        await expect(courseNotice.getByRole('link', { name: 'Open current details' })).toHaveAttribute('href', new RegExp(`/create/courses/${courseId}$`));
        await courseNotice.getByRole('button', { name: 'Mark as read' }).click();
        await expect(authorPage.locator('[data-flash-toast]')).toContainText('Update marked as read');
        await expect(authorPage.locator('.lesson-note').filter({ hasText: 'Browser reviewed journey' }).getByText('Read', { exact: true })).toBeVisible();
        await audit(authorPage);
        await login(learnerPage, 'learner');
        await learnerPage.goto(`/learn/courses/${courseId}`);
        await expect(learnerPage.getByRole('heading', { name: 'Browser reviewed journey' })).toBeVisible();
        await learnerPage.getByRole('button', { name: 'Join this journey' }).click();
        await audit(learnerPage);
        await learnerPage.locator(`a[href*="/learn/courses/${courseId}/modules/"]`).first().click();
        await learnerPage.getByText('Read the lesson before you play', { exact: true }).click();
        await expect(learnerPage.locator('.lesson-video iframe')).toHaveAttribute('src', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
        await learnerPage.locator('.resource-card').getByRole('link', { name: 'View', exact: true }).click();
        await expect(learnerPage.getByText('Browser notes: instruction order matters.', { exact: true })).toBeVisible();
        await audit(learnerPage);
        const downloaded = learnerPage.waitForEvent('download');
        await learnerPage.getByRole('link', { name: 'Download original', exact: false }).click();
        expect((await downloaded).suggestedFilename()).toBe('browser-notes.docx');
        await learnerPage.getByRole('link', { name: 'Back to this lesson', exact: false }).click();
        await expect(learnerPage.locator('[data-practice-quiz]')).toBeVisible();
    } finally { await Promise.all([creator.close(), staff.close(), learner.close()]); }
});

test('empty catalogs offer clear next actions on narrow screens in both themes', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 740 });
    for (const dark of [false, true]) {
        for (const [path, title, action] of [
            ['/learn', 'No adventures found', 'See all modules'],
            ['/learn/courses', 'No journeys found', 'Explore adventures'],
            ['/challenges', 'No coding quests found', 'Explore adventures'],
        ]) {
            await page.goto(`${path}?q=zzzz-no-matching-content`);
            const toggle = page.getByRole('button', { name: 'Dark mode' });
            if ((await toggle.getAttribute('aria-pressed')) !== String(dark)) await toggle.click();
            const empty = page.locator('.catalog-empty');
            await expect(empty.getByRole('heading', { name: title })).toBeVisible();
            await expect(empty.getByRole('link', { name: action })).toHaveAttribute('href', /\/learn$/);
            await audit(page);
            await empty.getByRole('link', { name: action }).click();
            await expect(page).toHaveURL(/\/learn$/);
            await expect(page.locator('.module-card').first()).toBeVisible();
        }
    }
});

test('Shared interface: keyboard navigation, light/dark themes, narrow layouts and disabled-provider wallet', async ({ page }) => {
    await page.goto('/');
    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Skip to content' })).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page.locator('#main-content')).toBeFocused();
    for (const width of [1280, 390, 320]) {
        await page.setViewportSize({ width, height: 900 });
        for (const dark of [false, true]) {
            const toggle = page.getByRole('button', { name: 'Dark mode' });
            if ((await toggle.getAttribute('aria-pressed')) !== String(dark)) await toggle.click();
            await expect(toggle).toHaveAttribute('aria-pressed', String(dark));
            await audit(page);
            await page.reload();
            await expect(page.getByRole('button', { name: 'Dark mode' })).toHaveAttribute('aria-pressed', String(dark));
        }
    }
    await login(page, 'administrator');
    await page.goto('/wallet');
    await expect(page.getByText('105 KB', { exact: false })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Continue to GCash' })).toHaveCount(0);
    await audit(page);
});

test('B05: four creator arcade assessments and a multi-question quiz save validated wins on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 900 });
    await login(page, 'arcadelearner');
    const programs = { 'pixel-studio': 'paint 1 1 mint\npaint 2 2 peach', 'number-machine': 'add 3\nmultiply 2',
        'sort-lab': 'swap 1 2\nswap 2 3', 'terminal-quest': 'ls\ncat hello.txt\ncp hello.txt release.txt\ncat release.txt' };
    for (const [slug, program] of Object.entries(programs)) {
        await page.goto(`/learn/modules/${manifest.modules[slug]}`);
        await page.locator('.arcade-source > summary').click();
        await page.getByLabel('Your program', { exact: true }).fill(program);
        await audit(page);
        await page.getByRole('button', { name: 'Run program' }).click();
        await expect(page.locator('[data-arcade-output]')).toContainText('saved');
        await audit(page);
    }
    await page.goto(`/learn/modules/${manifest.modules['programming-check']}`);
    for (const label of ['The result must always stay the same', 'A loop around the three steps', 'Check a condition before collecting']) {
        await page.getByRole('radio', { name: label, exact: true }).check();
    }
    await page.getByRole('button', { name: 'Check my idea' }).click();
    await expect(page.locator('[data-quiz-feedback]')).toContainText('try again');
    expect(JSON.parse(fixture('snapshot', 'arcadelearner@browser.example.test'))).toMatchObject({ xp: 160, xp_awards: 4 });
    await audit(page);
    await page.getByRole('radio', { name: 'The robot may end up somewhere different', exact: true }).check();
    await page.getByRole('button', { name: 'Check my idea' }).click();
    await expect(page.locator('[data-quiz-feedback]')).toContainText('saved');
    await audit(page);
    expect(JSON.parse(fixture('snapshot', 'arcadelearner@browser.example.test'))).toMatchObject({ xp: 200, xp_awards: 5, streak: 1 });
});

for (const [role, destination, link] of [
    ['learner', '/learn', 'Learning catalog'], ['contributor', '/create/challenges', 'Quest studio'],
    ['instructor', '/create', 'Module studio'], ['moderator', '/manage/modules', 'Module reviews'],
    ['administrator', '/manage/accounts', 'Community'],
]) {
    test(`${role} workspace has focused navigation and usable desktop/mobile surfaces in both themes`, async ({ page }) => {
        await login(page, `nav${role}`);
        for (const width of [1440, 320]) {
            await page.setViewportSize({ width, height: 1000 });
            const nav = page.getByRole('navigation', { name: width === 1440 ? 'Your workspace' : 'Mobile workspace', exact: true });
            if (width === 320) await page.locator('.mobile-workspace > summary').click();
            await expect(nav.getByRole('link', { name: 'Module studio', exact: true })).toHaveCount(role === 'instructor' ? 1 : 0);
            await expect(nav.getByRole('link', { name: 'Quest studio', exact: true })).toHaveCount(['instructor', 'contributor'].includes(role) ? 1 : 0);
            await expect(nav.getByRole('link', { name: 'Weekly planning', exact: true })).toHaveCount(role === 'moderator' ? 1 : 0);
            await expect(nav.getByRole('link', { name: 'Accounting', exact: true })).toHaveCount(role === 'administrator' ? 1 : 0);
            await nav.getByRole('link', { name: link, exact: true }).click();
            await expect(page).toHaveURL(new RegExp(`${destination}$`));
            for (const dark of [false, true]) {
                const toggle = page.getByRole('button', { name: 'Dark mode', exact: true });
                if ((await toggle.getAttribute('aria-pressed') === 'true') !== dark) await toggle.click();
                await audit(page);
            }
            if (width === 1440) await page.screenshot({ path: `storage/app/browser-results/${role}-workspace.png` });
            await page.locator('.account-menu > summary').click();
            await expect(page.getByRole('navigation', { name: 'Account navigation' })).toBeVisible();
            await page.keyboard.press('Escape');
            await expect(page.locator('.account-menu')).not.toHaveAttribute('open', '');
            await expect(page.locator('.account-menu > summary')).toBeFocused();
        }
        // Follow every policy-filtered workspace destination, not just the first role card.
        await page.setViewportSize({ width: 1440, height: 900 });
        const destinations = await page.getByRole('navigation', { name: 'Your workspace', exact: true }).locator('a').evaluateAll(links => links.map(link => link.getAttribute('href')));
        test.setTimeout(180_000);
        await page.setViewportSize({ width: 320, height: 900 });
        for (const destination of destinations) {
            const response = await page.goto(destination);
            expect(response.status(), destination).toBe(200);
            await expect(page.locator('#main-content')).toBeVisible();
            await audit(page);
        }
    });
}

test('interactive games edit and preview locally; confirmed runs save through the existing server rules', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.setViewportSize({ width: 1440, height: 1000 });
    await login(page, 'interactive');
    await page.goto('/learn/sequences');
    const garden = page.locator('[data-coding-game]');
    await expect(garden.locator('[data-game-board]')).toHaveAttribute('aria-label', /Path tiles/);
    let saves = 0;
    const completion = await garden.getAttribute('data-completion-url');
    page.on('request', request => { if (request.method() === 'POST' && request.url() === completion) saves++; });
    await garden.focus();
    for (const key of ['ArrowRight', 'ArrowUp', 'ArrowRight', 'ArrowRight', 'ArrowRight']) await page.keyboard.press(key);
    await garden.getByRole('button', { name: 'Step 2: up', exact: true }).focus();
    await page.keyboard.press('Alt+ArrowRight');
    await expect(garden.getByRole('button', { name: 'Step 3: up', exact: true })).toBeFocused();
    await garden.getByRole('button', { name: 'Add left', exact: true }).click();
    await garden.getByRole('button', { name: 'Step 6: left', exact: true }).focus();
    await page.keyboard.press('Delete');
    expect(saves).toBe(0);
    expect(JSON.parse(fixture('snapshot', 'interactive@browser.example.test'))).toMatchObject({ xp: 0, levels: 0 });
    await page.route(completion, route => route.fulfill({ status: 503, contentType: 'application/json', body: '{}' }), { times: 1 });
    await garden.getByRole('button', { name: 'Run my code', exact: true }).click();
    await expect(garden.locator('[data-game-feedback]')).toContainText('wasn’t saved');
    await expect(garden.getByRole('button', { name: 'Add right', exact: true })).toBeEnabled();
    expect(JSON.parse(fixture('snapshot', 'interactive@browser.example.test'))).toMatchObject({ xp: 0 });
    await garden.getByRole('button', { name: 'Run my code', exact: true }).click();
    await expect(garden.locator('[data-game-feedback]')).toContainText('saved');
    expect(saves).toBe(2);
    expect(JSON.parse(fixture('snapshot', 'interactive@browser.example.test'))).toMatchObject({ xp: 20, levels: 1 });
    await audit(page);
    await page.screenshot({ path: 'storage/app/browser-results/interactive-garden.png' });
    await page.setViewportSize({ width: 320, height: 1000 });
    await page.goto(`/learn/modules/${manifest.modules['pixel-studio']}`);
    await page.getByRole('button', { name: 'Paint column 1, row 1; currently blank', exact: true }).click();
    await page.getByRole('button', { name: 'Paint with peach', exact: true }).click();
    await page.getByRole('button', { name: 'Paint column 2, row 2; currently blank', exact: true }).click();
    await expect(page.locator('[data-arcade-preview-status]')).toContainText('Preview only');
    expect(JSON.parse(fixture('snapshot', 'interactive@browser.example.test'))).toMatchObject({ xp: 20 });
    await audit(page);
    await page.getByRole('button', { name: 'Run program' }).click();
    await expect(page.locator('[data-arcade-output]')).toContainText('saved');
    expect(JSON.parse(fixture('snapshot', 'interactive@browser.example.test'))).toMatchObject({ xp: 60 });
    await page.goto(`/learn/modules/${manifest.modules['sort-lab']}`);
    for (const position of [1, 2, 2, 3]) await page.getByRole('button', { name: new RegExp(`^Select position ${position},`) }).click();
    await page.getByRole('button', { name: 'Run program' }).click();
    await expect(page.locator('[data-arcade-output]')).toContainText('saved');
    await audit(page);
    expect(JSON.parse(fixture('snapshot', 'interactive@browser.example.test'))).toMatchObject({ xp: 100 });
    await page.goto(`/learn/modules/${manifest.modules['terminal-quest']}`);
    const prompt = page.getByLabel('kody@workspace $', { exact: true });
    for (const command of ['ls', 'cp hello.txt release.txt', 'cat release.txt']) {
        await prompt.fill(command); await prompt.press('Enter');
        await expect(prompt).toBeEnabled();
    }
    await expect(page.locator('[data-arcade-output]')).toContainText('saved');
    await prompt.press('ArrowUp'); await expect(prompt).toHaveValue('cat release.txt');
    await prompt.press('ArrowDown'); await expect(prompt).toHaveValue('');
    await audit(page);
    expect(JSON.parse(fixture('snapshot', 'interactive@browser.example.test'))).toMatchObject({ xp: 140, xp_awards: 4 });
});
