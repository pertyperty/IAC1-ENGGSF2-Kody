# Cohesive card and layout extension — 25 November 2026

Extends the approved [page hierarchy](page-hierarchy-audit-2026-11-23.md) and
[shared UI polish](shared-ui-polish-2026-11-24.md) primitives (`--radius-card`,
`--radius-pill`, `--elev-1/2`, semantic top/left tint accents, rounded filter
panels, and elevated row cards) in `resources/css/design-system.css` to the
remaining public, learner, creator, and staff pages.

## Scope

- **Included (via shared primitives in `resources/css/design-system.css`):**
  - **Library & Bookmarks:** `.journey-card` tinted-top cards, metadata grid, and elevated `.saved-learning-list` rows.
  - **Welcome, Contact & Legal:** `.welcome-paths` and `.support-topics` tinted-top cards with icon badges, `.welcome-points` chips, framed `.welcome-trial`, `.support-hero`, and readable `.legal-page` surface.
  - **Weekly lobbies, Leaderboards & Results:** `.quest-hero`, `.quest-lobby-columns`, `.weekly-archive-grid`, `.leaderboard-row` / `.weekly-score-row`, and semantic top-3 rank pills.
  - **Wallet, Earnings & Finance:** left-striped `.wallet-summary-card` and `.finance-workspace` liability cards, tinted `.wallet-package` cards, rounded `.wallet-transaction` rows, and framed `details.lesson-note` disclosures.
  - **Notifications, Account & Creator Profiles:** elevated `.notification-item` cards with unread accent stripe, framed `.profile-overview` and `.creator-profile` sections, and pill `.profile-actions`.
  - **Course & Module detail:** elevated `.journey-next` and contextual `.lesson-note` panels.
  - **Creator Studios, Curriculum & Tower Studio/List:** `.creator-steps` cards, curriculum `.dashboard-panel` cards, `.tower-editor-card` cards, and `.tower-level-list` rows with pill status badges.
  - **Staff & Admin Workspaces:** `.staff-workspace .workspace-grid` tinted cards, `.account-result` rows, unified rounded filter cards (`.governance-filters`, `.report-filters`, `.review-inbox-filter`), `.site-management-grid` panels, and left-striped `.report-insight` stat tiles.
- **Explicitly excluded (owned separately):**
  - Challenge views/editor (`resources/views/challenges/*`, `resources/views/weekly/play.blade.php`), challenge-specific CSS/JS (`.challenge-editor*`, `.quest-play-columns`), local `.env`, provider configuration, and live Judge0 submissions.

## Guardrails

- All new rules reside in `resources/css/design-system.css` (no extra override stylesheet).
- Uses semantic light/dark CSS tokens only, preserves 44px touch targets, keyboard `:focus-visible`, `prefers-reduced-motion: reduce`, and narrow-viewport (`<= 600px` / `320px`) `min-width: 0; max-width: 100%` containment.
