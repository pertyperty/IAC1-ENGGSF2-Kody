# Creator workflow clarity

This focused continuation supports C01/C02 challenge authoring and G08-G10
activity-preset administration. Shared form guidance also supports the existing
lesson/course editors. Content and Coding Challenge Management retain ownership
of publication; Administration retains preset management. No provider integration,
new game, progression rule or schema change is included.

## Changes

- The challenge library/editor consistently say **coding challenge** and
  **test case**. Headings and actions describe creating, saving and submitting a
  saved draft. Examples remain unsaved until the creator explicitly saves.
- Challenge writing and test cases come before expandable concept tags, execution
  limits and access settings. Closed settings remain enabled and submitted with
  their existing values. Native details work without JavaScript.
- Challenge and preset forms reuse the lesson editor's open layout, readable
  labels and input boundaries. Test cases use spacing and separators. Challenge
  section links remain at least 15px on narrow screens, and Save actions stay in
  normal flow so they cannot cover editing fields.
- Presets use direct names such as **Activity type**, **Create preset** and
  **Retire preset**. A short expandable explanation replaces the introductory
  policy paragraph. The one-option reward selector becomes the same fixed
  `Deferred` input; server validation still rejects alternate reward modes.
- Removing a test case focuses the next row, or the preceding row when removing
  the last. Removing the only row remains blocked. Remaining values and field
  names survive renumbering.
- Native validation reveals collapsed ancestor panels and focuses the first
  invalid field after layout. The deferred focus is necessary in WebKit. Server
  errors reveal their panels on return; their links reopen panels before focusing
  the field. Ambiguous errors across separate forms remain unlinked.

No controls for execution limits, pricing, prerequisites, hidden cases, archival
or version history were removed. Existing authorization, immutable revisions,
withdrawals, concurrency checks, exact input/output text, private cases and
reward policies are unchanged. Previews still save no progress or rewards.

## Public project comparison

[Moodle's question workflow](https://docs.moodle.org/503/en/Building_Quiz) separates
question creation, save and preview actions. Its
[settings guide](https://docs.moodle.org/503/en/Quiz_settings) places secondary
controls behind disclosure. Kody follows those interaction patterns without
adopting Moodle's grading or exam configuration.
[Exercism's exercise-authoring guidance](https://exercism.org/docs/building/tracks/practice-exercises)
separates instructions, tests and supporting metadata; this informed keeping the
problem and test cases ahead of optional metadata.

The previously captured public Flexbox Froggy and CSS Diner screenshots were
compared with fresh 1280px/390px Kody captures: keep the immediate task/action
hierarchy visible, and use space rather than several nested panels. Those public
captures are useful for broad layout only; their blurred text cannot establish
typography quality or an authoring workflow. Kody's own labels, touch targets,
focus and overflow were checked directly.

Fresh Moodle image/PDF downloads were blocked or timed out. No fresh Moodle
screenshot comparison or interaction test is claimed. The published guide remains
a workflow reference, not proof of Kody's usability. Reference artifacts are not
bundled into the application.

Ignored evidence lives in `storage/app/private/creator-clarity-evidence/` and
`storage/app/private/creator-clarity-*.log`. The Kody screenshots come from the
isolated harness; they contain synthetic accounts only. The earlier public
screenshots remain in `storage/app/private/learner-first-evidence/`.

## Verification

Application and test source: `deee1e8391de0d355023f859f949092b9e2765be`.
Local environment: Windows, PHP 8.4.27, Node 22.20.0 and PostgreSQL 18.6,
19 November 2026. Code/screenshot review was performed by the coding agent;
human curriculum and release reviewers remain unassigned.

- All 65 frontend tests passed on the final JavaScript.
- All 152 selected backend cases passed with 1,136 assertions, covering challenge,
  preset, lesson and course publication, validation, ownership and retained history.
- The final nine authoring checks passed across Chromium, Firefox and WebKit.
  They exercise row deletion/focus in both directions, the last-row guard,
  collapsed native errors, returned server errors and error links, whitespace
  preservation after save, local previews without writes, narrow/desktop
  accessibility/overflow, readable section links and non-overlaying Save actions.
- Six additional Chromium learner/editor workflows passed: selected-track
  continuation, unsaved-change protection, learner visual audit, concept-answer
  behavior, command references and guided assessment save/preview. This run
  preceded the final challenge-only section-link font adjustment.
- Full Pint, `git diff --check` and both production builds passed.

Initial browser failures exposed a saved-text label selector and a WebKit
mouse-focus test assumption. After correcting those tests, the collapsed-field
case also exposed WebKit's real post-disclosure focus problem. The application
now defers focus until layout; the final nine-check run is clean. Assertions
were retained, and no check or validation rule was disabled to make it pass.

Only disposable test databases were migrated. The owner's development database
was not migrated/reset. No live providers were called. Remote CI, the full
backend suite, dependency audits and restoration drills were not rerun for this
focused batch. Those broader/candidate gates remain separate.

## Next work and release limits

This closes the two form-focus defects and the challenge/preset presentation
batch. It does not complete the platform-wide UX review. Next useful work is:

1. Review one complete learner journey with humans: choose a track, complete a
   lesson, explain the concept and resume saved progress. A named curriculum
   reviewer should approve the actual beginner revisions used in the pilot.
2. Consolidate repeated feedback in the shared shell: saving currently produces
   both a persistent update receipt and a toast. Preserve accessible feedback and
   existing financial/action receipts when choosing a consistent presentation.
3. Continue focused copy/layout checks on remaining staff/review screens and
   incremental static analysis. Measure reporting reads before changing financial
   aggregation; preserve reconciliation coverage.

Production legal/retention review, actual-device and screen-reader sessions,
named operational owners, staged restoration/alerts and candidate CI remain in
the [release index](release-evidence-index.md) and
[deferred register](deferred-features.md). Local automation cannot sign them off.
Third-party/API implementation remains paused; Judge0 is first when the owner
resumes it. No environment changes or migrations are required for this batch;
deploy the complete rebuilt Vite manifest and chunks together.
