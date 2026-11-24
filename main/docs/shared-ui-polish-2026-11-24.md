# Shared UI polish — 24 November 2026

This pass refines the shared styles after the [page hierarchy audit](page-hierarchy-audit-2026-11-23.md). Keep new visual rules with the shared components in `resources/css/design-system.css`.

| Area | What to look for |
| --- | --- |
| Navigation, controls, and forms | Clear current-page styling, a visible keyboard focus indicator, distinct primary and secondary actions, and readable fields and placeholders in both themes. |
| Modules, Courses, and Challenges | Consistent title, metadata, creator attribution, and destination placement. Use the named link to open an item; the card does not cover other links. |
| Progress, Library, and course trails | Keep Continue in view ahead of supporting stats. At phone widths, give progress links two columns and remove the decorative Tower image from the continuation panel. Retain text labels for progress and completion. |
| Tower and Focus play | Make the next and cleared levels distinct on the map. Keep play controls and game behavior intact. Keep the map's curved lanes with reduced motion and suppress hover movement. |
| Sign-in, account, Help, and staff workspaces | Use common spacing and form styles. Give FAQ controls room to wrap, and preserve the existing staff and creator workflows. |

The [previous visual comparison](page-hierarchy-audit-2026-11-23.md) documents the Codecademy catalog, Exercism's learning and practice sections, and GOV.UK's explicit action labels. This pass keeps those patterns. The [workspace notes](interactive-workspaces.md) document the game comparisons.

The Chromium layout sweep uses seeded accounts for guest, learner, instructor, contributor, moderator, and administrator views at 320, 768, 1101, and 1440 pixels. It saves representative phone and desktop screenshots under `storage/app/browser-results/`. The public light and dark accessibility checks, staff controls, first play, quizzes, and workspace scenarios complement the width checks. The learner dashboard regression also checks the two-row progress strip and 44px link targets at 320px. Focus play and quizzes were also exercised in Chromium, Firefox, and WebKit. These fixtures cover representative routes and states; records with unusual content and production data still need spot checks.
