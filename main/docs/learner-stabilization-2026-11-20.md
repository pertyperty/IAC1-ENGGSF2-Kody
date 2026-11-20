# Learner stabilization — 20 November 2026

This follows the [website audit](website-audit-2026-11-20.md) at candidate `44baa82`.
It addresses the next agreed stabilization batch. Third-party activation remains
paused; review/merge into main, human curriculum/usability work and staged
operating evidence remain separate gates.

## Navigation and play

The mobile reveal failure involved the browser scrolling an absolutely positioned
button into view: in the diagnostic, document scroll changed from 450px to 90px,
restoring the header and hiding the target before activation. The reveal is now
anchored to the viewport. Automatic hover reveal and its empty hover edge are
removed; scroll-up restoration, keyboard focus and explicit Show navigation remain.
The regression hovers the real reveal before clicking, without forced events.

Games place the goal above the workspace. On mobile, the bounded program and Run
come before the scene, so learners can start without scrolling through an empty
visualization. Garden Undo and step-reordering controls live in Edit steps below
the primary action; keyboard editing remains available. The desktop scene stays beside the independently scrolling controls;
How it works stays inside that pane so expanding it does not push the scene below
the viewport. Text editing now says Edit commands as text and identifies Command
reference as its accepted syntax. Readable pseudocode and validated server replay
retain their existing formats and semantics.

Current mobile captures were compared with the CSS Diner and Flexbox Froggy
screenshots collected during the audit. The useful convention is an immediately
visible goal and program input, with secondary reference separate. This is layout
evidence, not a human learning-effectiveness result. Local captures remain ignored
under `storage/app/private/learning-consolidation-evidence/` and
`storage/app/private/website-audit-2026-11-20/`.

## Presets and wording

The website no longer offers creation through either a form or a mutation route:
GET `/manage/game-presets/create` returns 404 and POST `/manage/game-presets`
returns 405. The controller exposes browsing, editing, version history, usage and
retirement. Update requests require a bound existing preset. Internal service
creation remains for provisioning and isolated fixtures; no new provisioning UI,
route or command is introduced. Immutable revisions, locked updates, audit records
and reward rules remain intact. Browser authoring verification now edits a seeded
existing preset, rather than creating one through a removed screen.

Visible preset/course/review labels use Module, and coding-challenge errors name
the actual activity. Contributor eligibility explains that evaluation is unavailable
when Judge0 is disabled and that Tower clears do not supply challenge passes. This
does not alter thresholds or award synthetic credit.

## Verification

Local verification on the changeset, using the production build and disposable
PostgreSQL on loopback port 51629:

- 70 frontend tests pass; production Vite build passes.
- 56 focused backend tests pass with 366 assertions (preset guards, validation,
  immutable revisions, mutation/session protection and contributor application).
- Pint and the existing scoped PHPStan configuration pass.
- 12 cross-engine browser executions pass: preset editing, desktop stationary
  scenes, mobile goal/program/Run ordering at 320/390/900px and bounded Garden
  editing, across Chromium, Firefox and WebKit.
- Six additional cross-engine executions pass for viewport-fixed navigation and
  Garden keyboard/Undo editing with accessible button sizing. Three Garden
  executions repeat the earlier workflow with the expanded editing assertions.
- The original short-screen/touch Chromium navigation journey passes after the
  fixed-position correction. Seven Arcade run/concept-check/reset journeys passed
  earlier in this batch; final full workflow coverage remains a CI gate.

The broad local backend run was interrupted in favor of the focused checks above;
it produced no final result and is not counted as passing. Full backend, cached
configuration, complete Chromium/accessibility and upgrade/restore verification
must pass on the pushed PR revision in CI. The last two Tower/support label-only
changes were source-reviewed after the browser runs; no additional browser run is
claimed for those text substitutions. No provider, migration or owner database
change was needed. The disposable PostgreSQL process was stopped after local checks.

Earlier diagnostic failures remain in private logs and are not counted as passing
runs. The audit remains a snapshot of the pre-fix revision, with its original
failures preserved. Named human curriculum review, a voluntary learning pilot and
staged operational sign-offs remain outstanding before public launch.
