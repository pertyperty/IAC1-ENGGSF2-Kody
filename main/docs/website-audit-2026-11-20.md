# Website audit — 20 November 2026

Implementation follow-up: [learner stabilization](learner-stabilization-2026-11-20.md).
The findings below describe the audited pre-fix revision.

## Decision and baseline

**Kody is not ready for a blanket integration or public-launch sign-off.** Complete a focused stabilization batch, review and integrate the candidate into main, then begin an isolated Judge0 trial. Further game engines, floors or progression systems are not needed for this next step. No provider was activated and no application code was changed during this audit.

Audited checkout: `fix/learning-workspace-consolidation`, commit `44baa828a441b15e6cc1aef8538707fa3e52215c`. Fetched `origin/main`: `192a115af5ab4588668fdc6237156845fc2ae695`. Candidate ancestry is absent from main. PR [#53](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/pull/53) remains open and draft; its passing checks do not mean it has merged. Review and merge through the normal owner-controlled process, then verify main CI before creating an integration branch from main.

Environment: Windows development checkout, PHP 8.4, production Vite build, isolated PostgreSQL on loopback port 51629 and disposable browser databases. The owner's `kody` database on port 5432 was not migrated or reset. Reviewer: automated source review and browser evidence; this is not a human usability, curriculum, legal or operational sign-off.

## Coverage and limits

The inventory contains 196 registered routes, 57 controller files, 173 Blade views, 70 feature-test files and 20 browser spec files. Review followed shared components and representative workflows across these families. It does not claim every possible account, retained revision, device or data state was manually exercised.

| Area | Evidence and remaining concern |
| --- | --- |
| Public pages, Help and account entry | Current browser journeys cover registration/legal links, verification and recovery, Help editing and responsive destinations. Real inbox delivery and production text/operator review remain unproved. |
| Tower and Arcade | All seven Arcade execution/reset flows, selected-track continuation, advanced Tower publication, bounded desktop scenes and startup without `crypto.randomUUID` pass. Mobile goal/action hierarchy still needs work; reference solutions do not establish assessment quality. |
| Modules, Courses and Library | Publication, progression, enrollment, filtering and bookmark workflows pass. Bookmarks preserve authorization and reward rules. Remaining wording and text-editor syntax are inconsistent. |
| Profiles and creator credibility | Public attribution, preview, private qualification requests and review guards have automated coverage. Public identity remains distinct from private contact and credential data. Staged storage/erasure evidence is still required. |
| Authoring and staff | Current sessions cover authoring, review, filters, FAQ preview, account tools and preset usage. Preset creation remains reachable despite the intended browse/edit scope. |
| Progress, notifications and navigation | Owned notification filters, Library and sidebar-scroll tests pass. One short-screen/touch navigation test timed out in the full run and needs reconciliation below. |
| Finance and operations | Local reports and disabled-checkout journeys pass; runtime providers remain disabled. This does not validate live payment, payout, dispute, mail, queue or incident operation. |

## Findings and acceptance criteria

### 1. Candidate changes have not reached main — release baseline blocker

The fetched ancestry check confirms that `44baa82` is not an ancestor of main. Avoid another integration branch built on an older interface and database baseline. Acceptance: PR review complete, merge confirmed by ancestry, and main CI passing at the merged revision. This audit does not authorize an automatic merge.

### 2. Short-screen navigation verification is not clean — stabilization blocker

The fresh full Chromium run passed 100 of 101 tests. `tests/Browser/journeys.spec.mjs:74` timed out after 60 seconds while testing a 1440×600 touch context resized to 320×740, scrolling, revealing the mobile workspace and following Help. A targeted rerun also timed out. Its trace identifies the stalled action: clicking `[data-navigation-reveal]`. The button initially passes visibility/stability checks, but the signed-in header intercepts pointer events; the button then becomes hidden and the click retries until timeout. Cleanup reports the closed-context error. This establishes a repeatable failure in this automated interaction, rather than a clean full-suite result; physical-device impact still needs verification. Relevant interaction code is `resources/js/scroll-navigation.js`, with reveal/header stacking in `resources/css/workspace.css` and `usability.css`.

Acceptance: reconcile reveal visibility, header positioning and pointer targeting through the scroll/layout transition; distinguish application behavior from automation timing without forcing clicks or weakening navigation assertions. Repeat the affected journey and an appropriate surrounding suite.

### 3. Mobile games put the goal and Run too far down — high learner priority

The current Number Machine capture at 390×900 shows the scene first, Goal around y715, pseudocode around y865 and Run around y1170. Learners cannot see the primary action on entry. Desktop independently scrolling controls are substantially better and pass their dedicated check.

Evidence: `resources/views/learning/arcade-game.blade.php:6` places the scene before the briefing/program; mobile sizing is in `resources/css/simplify.css`. Acceptance: on narrow screens, introduce the goal before the scene and compact the initial scene/editor so inputs and Run are discoverable together. Preserve keyboard access and an accessible stacked layout. Check 320/390px widths, short desktops, long programs, errors and all game types; do not solve this with a fixed overlay covering content.

### 4. Preset creation is hidden rather than removed — acceptance mismatch

`routes/web.php:251–252` still exposes GET `/manage/game-presets/create` and POST `/manage/game-presets`. `app/Policies/GamePresetPolicy.php::create` permits active verified Administrators; `Administration/GamePresetController::create/store` and `GamePresetsTest` retain creation. This is an authorized feature, not privilege escalation, but it incompletely implements the requested browser for editing existing presets and viewing usage.

Acceptance: remove creation entry points and align authorization, request handling and tests with browse/edit behavior. Preserve existing presets, pinned revisions, usage lists and history. The prior consolidation record explicitly retained compatibility; resolve that remaining mismatch instead of treating the hidden link as completion.

### 5. Terminology and editable syntax still compete — medium learner priority

Visible examples include “Lesson” in `resources/views/learning/course-lesson.blade.php:4`, “Lessons” in `app/Services/Administration/ReviewInbox.php:19`, and “quest” in several errors from `app/Services/Challenges/ChallengeSubmissions.php`. The contributor application still says “fellow explorers” and “Keep exploring.” These messages obscure the actual item or next action.

The game list displays readable pseudocode, while “Edit as text” accepts the older command format. For example, a displayed arithmetic expression differs from the parser's `add 3` instruction. `resources/js/games/learning-trace.js` formats the display; `arcade-games.js` retains the validated command parser. Acceptance: use Modules, Courses and Coding challenges consistently in visible text; make the editable format explicit or support a tested round trip to the learner-facing representation. Preserve server replay and the validated transport format; do not introduce arbitrary source execution for Arcade games.

### 6. Contributor qualification has a deferred dependency — disclose it clearly

`app/Services/Account/ContributorEligibility.php` requires account age of 30 days, 25 distinct validated Modules and 50 distinct passed Coding challenges. While Judge0 is disabled, Tower clears do not supply those challenge passes. “Keep exploring” does not explain this limitation.

Acceptance: explain the unavailable challenge-credit path beside the eligibility progress until standard execution is enabled. Preserve the eligibility thresholds and distinct-completion rules; do not fabricate credit. This is a reason to prioritize Judge0 later, not to bypass eligibility now.

### 7. Human, editorial and operational evidence remains open — public-release blockers

The [release evidence index](release-evidence-index.md) still lacks a designated staging candidate, named primary/backup owners, curriculum sign-off and a human pilot. Reference solutions demonstrate solvability, not whether the games teach or assess the intended concept. Observe a small voluntary pilot covering first play, wrong answers, retry, returning continuation, module/course discovery and creator/staff work. Record confusion and concept transfer, then correct the observed problems.

Before public release, also review operator/eligibility/retention facts in Terms and Privacy, material changes for existing accounts, production Administrator provisioning and recovery, private storage/version erasure, queue/scheduler supervision, alerts, ingress/TLS, restore/rollback and target-device accessibility. `CreateLocalAdministrator` intentionally refuses production; do not repurpose it as an undocumented bootstrap. Local HTTP/debug configuration is development configuration, not evidence of a production vulnerability. See the evidence index for exact staged requirements.

## Verification

| Check | Result / limit |
| --- | --- |
| Frontend | 70 tests passed in this audit. |
| Production assets | Vite production build passed before browser verification. |
| Dependency audits | Composer locked audit: no advisories or abandoned packages; npm audit: zero vulnerabilities. Both exited 0. |
| Seed curriculum | Read-only `kody:tower-curriculum-audit --seed-only`: 80 floors, 111/111 reference stage solutions verified. Does not audit retained owner-authored revisions or learning effectiveness. |
| Fresh full Chromium | 100 passed, one navigation timeout; 15.5 minutes. All seven Arcade run/reset journeys passed. |
| Targeted navigation | Failed again after 60 seconds. Trace shows header interception of the reveal-button click followed by the button becoming hidden; no passing rerun is claimed. |
| Existing exact-head CI | [Run 44745657860](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/actions/runs/44745657860), head `44baa82`, verify job 136320161420 succeeded. Includes backend/cached checks, scoped static analysis, Chromium/accessibility, cross-engine learning and populated upgrade/restore. These suites were not all rerun locally for this audit. |
| Provider status | Setup-status inspection: Judge0 and Google disabled; SendGrid not selected; payment gates incomplete. No credentials requested or flags changed. |

The supplied worker example now includes a dedicated `code-execution` consumer. Prior dependency and queue-example findings should not be repeated as still-unfixed defects. Admission checks reject unavailable execution before consuming an attempt. These controls still need real deployment evidence later.

Local diagnostic logs and screenshots are ignored/private under `storage/app/private/website-audit-2026-11-20/` and `audit-20261120-*`. Curriculum output includes answers and must remain private. These artifacts are local evidence and will not travel with a Git-only report.

## Visual comparison

Inspected actual desktop screenshots of [Flexbox Froggy](https://flexboxfroggy.com/) and [CSS Diner](https://flukeout.github.io/) during this audit, alongside current Kody desktop/mobile captures. Both make the immediate goal and program area easy to locate near the scene and separate reference material from primary interaction. This supports improving Kody's goal → program → result hierarchy. Their advertisements, decorative styling and amount of reference text are not prescriptions for Kody. Public exercise completion was not tested, and screenshots do not establish teaching effectiveness.

Local captures: `reference-flexbox-froggy.png`, `reference-css-diner.png`, `kody-number-machine-desktop.png`, `kody-number-machine-mobile.png`, plus the original navigation timeout screenshot/context, in the private evidence directory above.

## Next work and integration boundary

1. **Learner stabilization PR:** reconcile the navigation timeout; improve mobile goal/input/Run ordering across Arcade and Tower; align visible terminology and editable instruction guidance. Verify representative wrong-answer, retry, long-program and keyboard/touch states.
2. **Preset scope PR:** finish browse/edit/usage-only behavior, preserving historical revisions and authorization. This can join the stabilization PR if kept small and independently reviewed.
3. **Editorial and readiness record:** review a small beginner path and run the human pilot; designate the candidate environment and operational owners. Complete the public-release evidence before admitting real users.

After the stabilization batch is reviewed and integrated with passing main CI, a restricted Judge0 trial is reasonable; a full public-launch sign-off is not a prerequisite for isolated sandbox testing. Verify provider language IDs, limits, network restrictions, dedicated worker processing, correct/wrong answers, compile/runtime errors, timeouts, outage/retry behavior, deduplication, attempt budgets and spend controls. Keep submissions and hidden test outputs private. Use controlled accounts and no unrestricted public activation during this trial.

Real mail verification/recovery must work before public registration. Google sign-in remains optional. Payments, payouts and competitions require separately reviewed contracts, failure/reconciliation tests and operating ownership. This audit grants no activation approval and requests no tokens.
