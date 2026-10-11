// Route names come from the server. Guides explain existing controls; they never invoke them.
const guide = (selector, title, message) => [selector, title, message];
const game = [
    guide('[data-arcade-world], [data-game-board], [data-practice-quiz]', 'Meet the challenge', 'Read the objective and inspect the starting state. A quiz asks you to choose an answer; games let you build and test an idea.'),
    guide('[data-arcade-controls], [data-game-controls], .game-controls', 'Build your idea', 'Use the controls or the program editor to prepare your answer. Changing your program is a preview, not a saved win.'),
    guide('[data-arcade-run], [data-game-run], [data-practice-quiz] button[type="submit"]', 'Try, adjust, then run', 'Run checks your answer. Published lessons save eligible results only after server validation; arcade practice stays local.'),
];
const lesson = [
    guide('.lesson-reading, .lesson-text', 'A place to learn', 'Read the explanation at your own pace. Reading and opening resources do not award play rewards.'),
    guide('[data-resource-open]', 'Keep your resources beside you', 'View opens a private resource alongside your lesson. Enlarge it for more room, or download a copy when permitted.'),
    ...game,
];
const editor = [
    guide('.editor-jumps', 'Build in manageable steps', 'This pinned step menu moves between editor sections. Changing sections does not save or publish your work.'),
    guide('#lesson-edit', 'Make the idea clear', 'Give your lesson a title, explain the idea and add resources. Write for someone discovering this topic for the first time.'),
    guide('#assessment-edit', 'Give the idea a playground', 'Choose a game, quiz or preset and customize its scenario. Preview play never saves learner progress.'),
    guide('#quest-checks', 'Check the important cases', 'Sample tests explain the problem. Hidden tests check edge cases and remain private to creators and reviewers.'),
    guide('main .studio-form button[type="submit"], main form button:not([type="button"])', 'Save before sharing', 'Save your draft with the form action. Publication and review use their own explicit actions; this tutorial submits nothing.'),
];
const catalog = [
    guide('.catalog-search', 'Find your next discovery', 'Search and filter the catalog, then open a matching item. The catalog shows saved progress when available.'),
    guide('.module-card, .course-card, .journey-card', 'Choose a path that fits', 'Each card describes its topic and access. Completion reflects your saved lesson or course history; browsing grants no progress.'),
];
const profiles = [
    { match: /^(home|tower\.index)$/, steps: [
        guide('[data-tower-stop="1"] .tower-node', 'One discovery at a time', 'Choose an unlocked floor to play. Guests can try floors 1–3; level 4 invites you to join.'),
        guide('[data-tower-present]', 'Find your next step', 'Jump to your current floor. The arrows explore higher and lower floors without changing your progress.'),
    ] },
    { match: /^tower\.(show|preview)/, steps: [guide('.tower-stage-tabs', 'One stage at a time', 'Follow this floor’s stages. Clear the current stage to unlock the next; the final validated stage clears the floor.'), ...game] },
    { match: /^arcade$/, steps: [guide('.arcade-cabinets', 'Choose your game cabinet', 'Every cabinet teaches a different coding idea. Choose one to load its practice station; no tokens or attempts are used.'), guide('#arcade-station', 'Your practice station', 'This is the selected game. Experiment freely; these local results never grant XP, KodeBits or saved lesson progress.'), ...game] },
    { match: /^(modules\.show|course-learning\.lesson|learning\.show|courses\.preview)$/, steps: lesson },
    { match: /^(learning\.catalog|course-learning\.catalog|challenges\.catalog)$/, steps: catalog },
    { match: /^course-learning\.mine$/, steps: [guide('.journey-cards', 'Your journeys, at a glance', 'See saved progress, enrollment and completion dates, then continue an available lesson.'), guide('.journey-card-actions', 'Pick up where you left off', 'Continue opens the next currently available lesson. Completed and unavailable lessons remain clearly distinguished.')] },
    { match: /^course-learning\.show$/, steps: [guide('.course-trail, .lesson-note', 'Explore the lesson trail', 'Review the course’s pinned lessons and progress. Sequential paths unlock in order; opening a lesson rechecks access.'), guide('main form', 'Join this learning path', 'Enrollment is an explicit action. Check any listed price and requirements before confirming.')] },
    { match: /^(studio|courses|challenges)\.(create|edit)$/, steps: editor },
    { match: /^(studio|courses|challenges)\.index$|^curriculum\./, steps: [guide('.studio-wayfinding', 'Your creator workspace', 'Choose the creator tool for your current role. Drafts, previews and published learning content have separate actions.'), guide('.studio-library-grid, .review-list', 'Find your next draft', 'Open an owned item to edit it. Review status tells you whether changes are still drafts or ready for learners.')] },
    { match: /^game-presets\./, steps: [guide('[data-preset-editor]', 'A reusable playground', 'Choose existing game rules or an automatically checked quiz, then provide its typed prompts and answers. Saving preserves previously copied module instances.'), guide('.studio-preview', 'Try the saved preset', 'This local preview uses the saved version. It does not grant learner progress or rewards.'), guide('.review-list', 'Find a game template', 'Open a preset to edit its next version or retire it from new use. Existing lesson references stay intact.')] },
    { match: /^tower-studio\./, steps: [guide('#tower-prompts legend', 'Shape this Tower floor', 'Edit the story and stages, check source notes and try the saved preview. Publishing preserves earlier cleared floors.'), guide('.tower-editor-card', 'Choose the floor to shape', 'Open a floor to edit its versioned content. Existing learner clears stay intact when content changes.')] },
    { match: /^weekly-events\.index$/, steps: [guide('[data-weekly-current]', 'This week’s shared adventure', 'Open the current quest to read its rules. Each event has three separate attempts and closes at the displayed Manila time.'), guide('[data-weekly-scores-link]', 'A leaderboard for each week', 'Weekly rankings use published verified scores, separately from lifetime learning XP. Unpublished results stay private.'), guide('[data-weekly-attempts]', 'Your weekly attempts', 'Revisit confirmed attempts and their evaluation status. Leaving the page does not cancel a committed evaluation.')] },
    { match: /^(weekly-events\.show|challenges\.show)$/, steps: [guide('[data-quest-problem], .challenge-case', 'Understand the coding quest', 'Read the task, formats, constraints and public examples. These programming challenges use the isolated execution provider.'), guide('[data-quest-participation]', 'Make your move', 'Check the remaining attempts and execution availability. Confirming a solution uses an attempt; practice-game links use none.'), guide('[data-execution-pending]', 'Execution setup is pending', 'Live code evaluation is unavailable until provider setup is completed. You can read the task or visit Practice Arcade in the meantime.')] },
    { match: /^(weekly-leaderboards\.index|weekly-results\.(show|review))$/, steps: [guide('[data-weekly-archive]', 'Choose a completed week', 'Open a published event to see that week’s scores and confirmed rewards. Draft results are visible only in authorized staff review.'), guide('[data-weekly-rankings]', 'Verified weekly scores', 'Each player’s best terminal evaluation determines the position. Equal scores share a rank; speed and spending do not break ties.')] },
    { match: /^weekly-studio\./, steps: [guide('.catalog-search', 'Plan the right week', 'Choose a Sunday and find an approved quest. Started events remain immutable; future weeks may be prepared separately.'), guide('main .studio-form', 'Confirm the weekly rules', 'Review the pinned quest and rules before explicit confirmation. This guide does not schedule or replace anything.')] },
    { match: /^leaderboards\./, steps: [guide('.leaderboard-list', 'Climb by learning', 'First-time eligible validated achievements earn XP. Equal XP shares a position; these positions are separate from weekly quest scores.')] },
    { match: /^wallet\./, steps: [guide('.wallet-summary h2', 'Know what is available', 'Your wallet distinguishes available and reserved KodeBits. Purchases become available after payment setup.'), guide('.wallet-columns', 'Follow the recorded changes', 'Check packages and transaction history. A browser return from payment never proves that KodeBits were credited.')] },
    { match: /^(earnings|finance)\./, steps: [guide('main .content-panel, main .lesson-note', 'Follow the financial record', 'Read the recorded status, amounts and available actions. Sensitive changes require their existing confirmation and authorization.'), guide('main form', 'Check before confirming', 'Verify the amounts and supporting details before submitting. This guide never moves money or changes a financial record.')] },
    { match: /^account\.show$/, steps: [guide('.profile-picture-link', 'Make your profile yours', 'Choose your picture to open its secure editor. Personal details and security tools are grouped beside your profile.'), guide('.profile-details', 'Your account at a glance', 'Review your personal details, role and status. Role elevation follows the separate application and review process.')] },
    { match: /^account\.edit$/, steps: [guide('#profile-picture', 'Your picture, your choice', 'Choose a JPEG or PNG and confirm the upload. Images are processed privately and never change your account role.'), guide('main .studio-form', 'Keep your details current', 'Edit your personal fields. Email and password changes require your current password and can end your session.')] },
    { match: /^account-governance\./, steps: [guide('.governance-filters', 'Find the right account', 'Filter by username, role or status, then inspect account history before acting. This guide makes no account changes.'), guide('main .studio-form', 'Review before taking action', 'Only the actions authorized for your staff role are available. Check the target, reason and confirmation carefully.')] },
    { match: /^(module-reviews|course-reviews|challenge-reviews|instructor-reviews|contributor-reviews|content-withdrawal)\./, steps: [guide('.review-list', 'Open the item to review', 'Inspect the submitted version and relevant history. Decisions apply to that saved version, not unsaved creator edits.'), guide('main form', 'Make an explicit decision', 'Use the authorized review action and required feedback. The tutorial never approves, rejects or withdraws content.')] },
    { match: /^help\.index$/, steps: [guide('.help-search', 'Find a helpful answer', 'Search or choose a topic. Expand a guide to read its answer, or open it on its own page.'), guide('.help-group', 'Answers grouped by topic', 'Choose the topic you need. You can replay this page’s tutorial from the Kody button.')] },
    { match: /^help\.show$/, steps: [guide('main .lesson-text, main article', 'Read the complete answer', 'This page contains the selected guide. Return to Help to search or browse other topics.')] },
    { match: /^notifications\./, steps: [guide('main .review-list, main article', 'Your updates in one place', 'Open an update to read its details. Linked destinations recheck current access, including after role changes.')] },
    { match: /^login/, steps: [guide('input[name="identifier"]', 'Email or username', 'Sign in with your email or exact-case username. Use account recovery if you cannot remember your password.'), guide('main form', 'Sign in securely', 'Fill in your credentials and submit when ready. A session confirmation may appear if you are already signed in elsewhere.')] },
    { match: /^(register|recovery|verification|contributor-application|instructor-application)/, steps: [guide('main form', 'Complete this account step', 'Follow the labels and inline feedback. Required confirmations and verification protect your account; this tutorial submits no information.')] },
];

export function isGuideTargetVisible(element) {
    return Boolean(element && element.getClientRects().length && !element.closest('[hidden]')
        && (!element.checkVisibility || element.checkVisibility()));
}

export function buildPageGuide(doc, route) {
    const main = doc.querySelector('main');
    const heading = main?.querySelector('h1');
    const name = heading?.textContent?.trim() || doc.title.replace(/\s*[—·].*$/, '') || 'this page';
    const local = profiles.find(profile => profile.match.test(route))?.steps ?? [
        guide('main form', 'Your next action on this page', 'Follow this form’s labels and inline help. Review its confirmation before submitting; the guide makes no changes.'),
        guide('main .review-list, main .content-panel, main article, main section h2', 'Explore the page details', 'Read the information and choose an available action. Access and saved state still follow your account’s current permissions.'),
    ];
    const definitions = [
        ['main h1', name, `You’re on ${name}. Let’s look at the controls and information available here.`],
        ...local,
        guide('.mobile-workspace > summary, [data-navigation-rail] [data-rail-toggle]', 'Your workspace within reach', 'Use the menu to visit the destinations available to your account. Pin the desktop rail if you prefer visible labels.'),
        guide('[data-theme-toggle]', 'Make yourself comfortable', 'Switch light and dark themes. The Kody button replays the guide for the page you are currently viewing.'),
    ];
    const seen = new Set();
    const steps = definitions.flatMap(([selector, title, message]) => {
        const target = [...doc.querySelectorAll(selector)].find(isGuideTargetVisible);
        if (!target || seen.has(target)) return [];
        seen.add(target); return [{ target, title, message }];
    });
    return { name, steps };
}
