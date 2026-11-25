# Sitewide Blade UI polish — 25 November 2026

Follows PR #62 (`1fe85b0`) and builds on `origin/main` (`577be4c`) with targeted fixes for the sidebar bottom gap, login/auth shell alignment, and missed Blade/CSS surfaces while keeping challenge and weekly play views/CSS/JS untouched.

## Summary of changes

- **Sidebar bottom gap (`resources/css/design-system.css`):**
  - Made signed-in non-map pages (`body:not(.tower-map-page):has(.app-frame.has-workspace)`) full-viewport flex columns so `.app-frame.has-workspace` stretches cleanly to the footer with no floating footer or mismatched height.
  - Removed the `3rem` outer top margin on `.site-footer` and grounded `.account-footer` when preceded by `.app-frame.has-workspace`, keeping bottom content spacing inside `<main>`.
  - Added a continuous `var(--rail-width)` surface + `1px` border column backdrop on `.app-frame.has-workspace` at `>= 1101px` so the sidebar background and divider run uninterrupted from header to footer at every scroll position in both collapsed and expanded rail states.
- **Login & auth shell redesign (`resources/views/account/login.blade.php`, `resources/css/design-system.css`):**
  - Updated `login.blade.php` (`account-shell-auth`) with a centered Visual System v2 card (`border-block-start: 4px solid var(--accent)`, `--radius-card`, `--elev-2`), contained mascot emblem and `.overline` pill (eliminating the negative-margin clipping mascot), explicit `<label for>` + `.auth-field` matching `<x-password-field>`, secondary Google OAuth styling with divider, 44px-tall auth links, and a soft callout `.access-support` card.
  - Aligned `.eyebrow` pills, `.secondary-link` 44px touch targets, `.password-control` button styling, and full-width CTAs across sibling account/auth views (`confirm-login`, `recover`, `reset-password`, `verify-email`, `verification-result`, `google`, `archive`, `delete`).
- **Sitewide Blade surface audit:**
  - `resources/views/account/instructor-review.blade.php`: fixed premature `</section>` tag that placed qualification notes, badge link, and history outside `.review-page.page-width`, and replaced `?` separator typo with `·`.
  - `resources/views/account/edit-profile.blade.php`: replaced nonexistent `.secondary-button` class on Remove picture with `button button-secondary` and switched validation alert from `.notice` to `.form-errors`.
  - `resources/views/account/confirm-login.blade.php`: added `button button-secondary` to the unstyled Cancel sign-in button.
  - `resources/views/account/contributor-reviews.blade.php`: upgraded to `account-shell-wide`, `.overline`, `.review-list`, and `<x-empty-state>` matching `instructor-reviews.blade.php`.
  - `resources/views/creators/edit.blade.php`: added `account-shell-wide` so the side-by-side `.profile-preview-layout` fits on desktop, and applied `.secondary-link` to the back link.
  - `resources/css/design-system.css`: aligned `.form-errors`/`.studio-errors` semantic error border/contrast, styled `.account-form input[type="file"]` and `.checkbox-label`/`.review-check`, elevated `.preset-browser`, Help answer/FAQ editor panels, profile preview panels, `.kody-error`, weekly studio cards, non-challenge `.content-reactions`, pagination pills, mobile `<= 600px` containment, and explicit `:focus-visible` outlines.

## Exclusions preserved

- Untouched: `resources/views/challenges/*`, `resources/views/weekly/play.blade.php`, `.challenge-editor*`, `.quest-play-columns`, `challenge-editor.js`, `challenge-status.js`, Desktop checkout, Docker edits, provider configuration, port-5432 database/tests, and live Judge0 submissions.
