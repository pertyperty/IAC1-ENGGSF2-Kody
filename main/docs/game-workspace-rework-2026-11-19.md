# Game workspace follow-up ? 19 November 2026

The owner requested a clearer game UI and registration Terms that stay in the
current tab. This batch supports A01, learner assessment presentation in B04/B05,
and the shared D01/D02/E01 preview. It changes presentation and local controls;
server admission, published revisions, interpreters and rewards stay authoritative.

## Implemented

- Terms and Privacy links on registration navigate in the same tab. Reading either
  link does not check the agreement box or record acceptance. The accepted legal
  version and timestamp policy is unchanged.
- Each game has one always-visible pseudocode list beside its game state. Arcade
  rows expose removal; Garden rows retain selection, reordering and keyboard
  deletion. Text entry is optional. The list scrolls internally at a fixed height,
  so adding instructions does not move Run farther down the page.
- Garden shows movement instructions as text, the loop header explicitly shows
  `REPEAT 2 TIMES`, and conditional moves show their collection rule. The same
  source highlights during Run and step previews. Loop headers are excluded from
  instruction indexes so keyboard reordering keeps focus on the intended step.
- Run, Reset, Hint and the result stay together. The optional Step/Replay/Restart
  tools and state inspection move under **Step through program**. Empty previews
  disable their controls. The instruction counter remains an instruction counter.
- State labels distinguish Not run, Preview, Running, Goal reached and Needs
  changes. Editing clears old success feedback and any prior concept answer.
  Local previews still make no completion request or reward claim.
- Pixel painting uses a palette and keyboard-accessible canvas cells rather than
  duplicate coordinate/color forms. The optional text editor still accepts the
  typed instructions. Small screens show a compact reference beside a canvas
  with 44px minimum cells, including lessons and authoring previews.
- Stacks display vertically, with TOP marked on both current and target stacks.
  Their pseudocode no longer describes a right-hand visual top. String spaces
  use a readable label; empty strings no longer say ?Empty stack.?
- The practice selector opens under **Change activity** and retains an explicit
  Open activity action. Game state can scroll by keyboard when content is long.
  Decorative companions leave the play area clear without rewriting preferences;
  the header guide remains available. Existing SVG icons and native disclosure
  indicators remain; no decorative text-symbol buttons were added.

## Comparison and visual review

Fresh desktop screenshots were captured from [Flexbox Froggy](https://flexboxfroggy.com/)
and [CSS Diner](https://flukeout.github.io/) on 19 November. Their visible code and
spatial relationship between task, input and game state informed the workspace.
Kody retains its explicit server-checked Run and concept checks; the comparison
is a layout reference, not evidence that Kody teaches as well as either project.
No assets, code, curricula or distinctive visual identity were copied.

Kody captures cover seven arcade scenes at 1280px, 390px and 320px, Garden on the
Tower, the dark stack scene and a saved-lesson pixel game at 320px. They were
reviewed directly, including the populated pseudocode editor. The original
number-game capture provides the reliable pre-change baseline; the attempted
`command-garden` playground URL was invalid and is not Garden baseline evidence.
Ignored artifacts live in `storage/app/private/game-workspace-evidence/` and
`storage/app/private/game-workspace-*.log`. Browser accounts are synthetic.

## Verification

Application/test revision: `b2a9ccc695754b003bee5c583b56a6d29efb713b`.
Environment: Windows, PHP 8.4.27, Node 22.20.0, PostgreSQL 18.6; 19 November 2026.
Review: coding-agent source and screenshot review, not human learning sign-off.

- All 65 frontend cases passed.
- All 120 selected backend cases passed, with 799 assertions, covering registration
  acceptance, guided assessments, arcade/puzzle replay, Tower and presets.
- All 30 selected cross-browser checks passed in Chromium, Firefox and WebKit.
  These cover same-tab navigation, fixed program/Run geometry, row focus, no-write
  previews, concept-answer handling, authoring and shared accessibility checks.
- All 28 selected Chromium workflows passed, including every arcade scene at all
  three widths, dark theme, explicit activity switching, lesson-editor previews,
  loop-header keyboard reordering, saved review lists and account workflows.
- Two additional Chromium journeys passed: server-saved Garden/pixel/sort/terminal
  play with preview isolation, retry and duplicate-reward assertions; and wide/
  mobile arcade results. Initial assertions expected the old preview sentence;
  they now check the explicit Preview label while retaining reward assertions.
- Full Pint, `git diff --check` and both production builds passed.

The first browser run exposed a real 52px Garden Run shift from a legacy CSS rule
that hid all-disabled edit tools, plus insufficient terminal-label contrast.
Both application defects were corrected and the final runs are clean. No
accessibility, replay or reward assertion was disabled to make the checks pass.

Only disposable test databases were migrated. The owner's database was not reset
or migrated. No new dependencies, migrations, environment settings or API work
are required. Deploy the complete rebuilt Vite manifest and chunks together.
Remote CI, full backend, audits and restore drills were not rerun for this UI batch.

## Delivery and remaining work

The [parent integration PR #50](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/pull/50)
brings the already-reviewed page-loading and creator
batches to main; their earlier PRs were merged into feature branches. The game
workspace remains a separate child PR. Neither PR is merged by this task.

This closes the same-tab link and shared game workspace batch. It does not prove
that games are sufficiently challenging or educational. Next work should inspect
lesson-to-assessment alignment, observed learner confusion and difficulty with a
named curriculum reviewer and a short human pilot. Real-device and screen-reader
sessions, platform-wide copy/feedback review and staged operating evidence remain
open in the [deferred register](deferred-features.md) and
[release index](release-evidence-index.md). Third-party/API work remains paused;
Judge0 is first only when the owner resumes integrations.
