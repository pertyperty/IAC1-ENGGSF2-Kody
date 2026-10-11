# Page guides, quest lobbies and mixed quizzes — 2026-10-11

This owner-approved follow-up covers B01/B02/B05, B11, D01/D02, G08–G10,
E02/E05 and shared interface behavior. It changes presentation and typed quiz
authoring, without changing role eligibility, admission, attempts or rewards.
The [implementation map](implementation-status.md) remains the master index.

## Shared branding, guides and controls

`config/branding.php` selects the head-only Kody logo and ICO/PNG/Apple icons.
The full-body mascot remains the companion and result illustration. All sources
and replaceable derivatives live in `public/images/kody/`; exact built-in
imagegen prompts and export sizes are in [asset provenance](../public/images/kody/README.md).
The standalone error layout also includes the shared favicon partial.

The server supplies the current route name to `page-guide.js`. Tours introduce
the current page heading and explain its visible controls before shared menus.
Help, games, lessons, course paths, creator/staff editors, reviews, accounts,
wallet, leaderboards and authentication each have relevant tips. Unlisted routes
fall back to their own forms/content rather than Tower guidance. Hidden controls
are omitted. Skip, replay, Escape, modal focus and reduced motion remain intact;
guides never submit forms or modify progress.

Original SVG symbols replace decorative emoji in templates, mission data and
generated game controls. Icons are decorative alongside labels, or use explicit
accessible names on compact controls. Text punctuation and programming syntax
retain their meaning. The Tower target button scrolls smoothly to the current
floor, while initial positioning and reduced-motion navigation are immediate.
Small flowers, trees, clouds, satellites and planets fade between ascent zones.
No new animation loops, paid gates or altitude rewards were introduced.

## Arcade and quests

Practice Arcade presents eight illustrated cabinets, including Quiz Lab, with an
active cabinet and a full-width practice station. Responsive layouts keep controls
usable on narrow screens. Cabinet art is original editable SVG; no third-party
game code or asset dependency was added. Practice remains local and grants no
saved completion, XP, streak or KodeBits. Creator previews share these boundaries.

Weekly quests use the existing world illustration, clear time/attempt information,
separate activity cards, a problem/participation split and a dedicated weekly
leaderboard at `/weekly/leaderboard`. Only **Published** weekly result sets appear
in that archive. Draft/provisional rankings stay private; the existing staff
publication, competition-rank tie rules and reward policy remain authoritative.
Lifetime learning XP stays on `/leaderboards` with a link to the weekly archive.

Coding and weekly quests explicitly show **Live code execution: setup pending**
while Judge0 is disabled/unverified. Disabled submission controls explain the
boundary and link to the Arcade. They neither execute source nor consume an
attempt. These are coding-submission activities; Arcade games are not silently
substituted into a weekly event. Live provider setup remains in the
[deferred register](deferred-features.md).

## Automatically graded quiz template

`choice-quiz` remains the stable template identifier. Version 1 single-question
and version 2 multi-question MCQ instances continue to render and grade. Version
3 supports a mixture of the following types within 1–10 questions:

| Type | Creator fields | Automatic completion |
| --- | --- | --- |
| `choice` | 2–6 distinct choices with stable IDs and one correct ID | Exact correct choice; true/false uses two choices |
| `blank` | 1–6 accepted short answers, up to 100 characters each, case sensitivity toggle | Exact accepted answer after whitespace normalization; letter case follows the toggle |
| `matching` | 2–6 distinct left/right pairs with stable IDs | Every left item selects its paired right item |

Question IDs use `a-z`, digits, underscore and hyphen, up to 30 characters.
Prompts allow 500 characters, feedback 1,000, and choice/pair labels 300.
Blank answers collapse ASCII spaces/tabs/newlines/form-feed/vertical-tab and trim
surrounding spaces; case-insensitive comparison is the example/default setting.
No fuzzy scoring, partial credit, essay grading or manual submission queue exists.
Creators can supply accepted synonyms explicitly. All questions must be correct;
missing/extra answers, nested payloads and unsupported/executable fields fail.

`QuizQuestions` owns server validation and grading. The shared frontend renderer
and checker provide immediate practice feedback; the server validates the pinned
assessment before recording eligible completion. Existing idempotent completion,
first-completion XP, streak and reaction authorization remain unchanged. Tower
stages use the same grader, with streak credit only after the final stage and no
additional Tower XP/KodeBits.

Creator Studio and the Administrator preset workshop support all three types,
accepted-answer/pair editing and previews. Quiz Lab is a selectable original
example which does not automatically save or publish. Published module revisions,
course-pinned assessments and managed preset instances remain immutable during
draft changes. Published revisions still require the existing staff approval.

## Deployment and verification

Migration `2026_10_11_000003_expand_mixed_quiz_preset_versions` expands the existing
PostgreSQL preset constraint for version 3 quizzes. It also fixes omission of
Logic Switchboard, String Forge and Memory Trail from that constraint, making
every already-supported Arcade template persistable as a preset. No historical
snapshot is rewritten. Rollback refuses to narrow the constraint while an
expanded snapshot exists; keep the expansion during an application rollback.
No packages, provider calls, secrets or owner-account changes are required.

Verification covers invalid/ambiguous questions, exact grading, saved completion
idempotency, pinned edits/preset copies, all Arcade presets, rollback protection,
private weekly drafts and published rankings. Browser checks exercise page-specific
guides, wide/mobile lobbies, quiz editing/preview/persistence and learner completion,
plus keyboard/modal focus and automated accessibility/overflow checks. Build and
frontend parity checks accompany these. The PR records final CI evidence; existing
backend risky-test warnings must not be described as warning-free verification.
Live execution and managed cloud operations still require later setup/rehearsal.
