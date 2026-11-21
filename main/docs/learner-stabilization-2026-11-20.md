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

## CI follow-up, 21 November

Run 44761620252 on `439ef5e` passed backend tests, cached configuration/routes,
dependency audits, frontend tests/build, formatting and scoped static analysis.
Its Chromium suite failed in three places; later engine and recovery steps were
skipped, so that run does not establish full verification.

- The shared briefing was split into a goal and optional reference, but quizzes
  omitted the reference include. Restore How it works for quizzes, including the
  pinned Expert Tower teaching notes. Keep the existing answer/reward validation.
- The older navigation journey still expected automatic hover restoration.
  Exercise explicit Show navigation activation after hover instead, then move
  focus out of the header before checking downward-scroll hiding and keyboard
  restoration. Focused navigation must remain reachable.
- The sidebar reload test changed its position using a synthetic scroll while a
  final font-layout restoration could still be pending. Use real wheel input,
  which already cancels pending restoration, and verify the exact saved interior
  position after reload. Keep link-navigation and maximum-range checks intact.

Follow-up local verification passed four Chromium journeys: navigation reveal,
Expert Tower quiz completion, advanced Tower teaching-note authoring, and sidebar
link/reload persistence. Sidebar persistence also passed in Chromium, Firefox and
WebKit. Four navigation-rail unit tests, Pint and `git diff --check` passed.
Logs are private `ci-439ef5e-focused.log` and `ci-439ef5e-sidebar-engines.log`.
The new revision still requires full CI, including the previously skipped engine
and recovery stages. No provider activation, migration or owner-data change was
performed; the disposable PostgreSQL cluster was stopped after verification.

## First-play follow-up, 21 November

PR #53 was merged as `16b3093` on main. This follow-up starts from that fetched
main tree. The workspace also contains unrelated Docker/deployment changes; those
are not part of this learning-experience changeset.

### Experience changes

- First verified Learner login now enters the first available Tower game. A
  server-owned session hint is recorded during successful authentication and
  consumed once at home. It neither saves a skill preference nor awards progress.
  Returning accounts keep their current landing behavior. `/tower/start` reuses
  selected-track continuation; existing game admission still runs before play.
- Choose track opens the skill picker when requested, including during a level.
  Beginners can try the first game immediately; experienced learners can choose
  Intermediate or Expert without fabricated easier-level clears.
- On mobile, the goal and board precede the controls. This replaces the earlier
  program-before-scene layout, which separated interactive canvases/switches from
  their inputs. Desktop stationary scenes and independently scrolling controls
  remain. Programs stay bounded as instructions are added.
- Pixel Studio keeps its palette beside the canvas. Sorting and switch tasks use
  their keyboard-accessible board buttons instead of duplicate position controls.
  Guided command choices show pseudocode while retaining their validated transport
  values. Parameter fields appear only for operations that need a value.
- Short first-action guidance distinguishes adding instructions from executing
  them. Run is disabled for an empty program. Terminal commands are added before
  explicit Run, matching the other games. Fixed-command tasks also guard direct
  board actions against unsupported commands.
- Tower pages put secondary descriptions, reward rules and related links in About
  this level. Mobile play uses a smaller header and avoids duplicate game titles;
  Practice relies on the existing Play navigation instead of another Home button.
  Shared mobile filters, form text, button spacing and footer spacing are improved.

### Evidence and remaining work

Compared the actual desktop Flexbox Froggy interface with Kody desktop/mobile captures.
The useful behavior is immediate access to the task, state and editable program;
its branding, automatic execution and advertising are not copied. The new Kody
captures are private under `storage/app/private/first-play-2026-11-21/`.

The first broad local browser batch passed 13 workflows and caught two test
assumptions: every game having a command dropdown, and login always ending at the
map. Those tests now exercise each game's real controls and accept the admitted
Tower destination while retaining session-takeover confirmation.

The focused backend run passed 85 tests with 601 assertions, including first
login, selected-track continuation, role restrictions and existing login/session
security. Seventy frontend tests, the production build, Pint and scoped PHPStan
passed. Fresh Composer locked audit found no advisories/abandoned packages; npm
audit reported zero vulnerabilities. These are local checks, not deployed evidence.

The broad local cross-engine batch passed 36 executions covering first actions,
all seven Arcade run/concept-check/reset flows, mobile ordering, stationary desktop
scenes and first-login/Expert track choice. Screenshot review then exposed palette
clipping that click automation had scrolled past. After moving colors outside the
bounded state scroller, 12 focused cross-engine executions passed; six further
first-action/Garden executions passed after improving cell-label legibility.
These counts include repeated workflows and are not distinct learner studies.
Mobile Help, Module/Course catalogs, Coding challenges, registration and login also
passed the local Chromium accessibility/overflow audit. Logs are private
`first-play-*.log`; full candidate CI remains required.

The final map/level picker separation passed five backend routing tests with 79
assertions and three newcomer browser executions across all engines. Verification
used disposable PostgreSQL on port 51629; it was stopped afterward. Owner data was
not migrated or reset. No credentials or provider settings were changed.

Before provider work, review this candidate's complete CI, including cached
routes, all Chromium workflows and recovery checks. A short observed human pilot
must still validate whether first-time learners identify the next action, recover
from mistakes and explain the taught concept. Automated completion cannot prove
that games are fun or instruction is effective. Curriculum difficulty and learning
transfer remain editorial work, rather than a reason to add another engine.

There is no missing API credential blocking this work. Keep integrations disabled.
After owner review, Judge0 is the first intended restricted trial; verify languages,
limits, isolated workers, compile/runtime errors, wrong answers, timeout/outage
handling, deduplication and attempt/spend limits. Public release still needs named
operations/backup owners, staged mail, private storage, alerts, restore evidence
and reviewed operator/legal facts. Payments and Google remain separate releases.

### Candidate CI browser follow-up, 21 November

PR #54 at `9a87f15` failed the Chromium step in CI run `44883163008`.
Backend tests, cached configuration/routes, dependency audits, formatting,
scoped analysis and asset builds passed before that failure. Cross-engine and
recovery steps were skipped, so this run does not verify them.

The uploaded browser evidence identified four outdated assertions: the new-account
journey and both role-review journeys assumed login always ended at the map;
the guided-command test expected raw transport commands as option labels.
Role-review tests now use the shared sign-in helper, including real session
confirmation. The learning journey accepts an admitted Tower game for learners
while retaining the staff dashboard assertion. The guided-command test checks
explicit pseudocode labels separately from unchanged accepted command values
and continues rejecting instructions outside each task's allowed set.

No application behavior, authentication protection, reward checks or provider
settings changed in this follow-up. Complete candidate CI remains required.


The complete local Chromium suite passed all 106 tests after these corrections,
including all four failed workflows, onboarding, seven game types, publishing,
private resources, moderation, accessibility and responsive-layout checks.
Verification used the disposable PostgreSQL cluster on port 51629. The private
log is `storage/app/private/pr54-ci/browser-full.log`; the cluster was stopped
afterward. JavaScript syntax checks and the focused diff whitespace check passed.
No PHP, frontend asset or dependency changes required rebuilding or repeating
backend tests in this test-only repair. Fresh CI will rerun those gates and the
cross-engine and recovery steps skipped by the failed candidate.
