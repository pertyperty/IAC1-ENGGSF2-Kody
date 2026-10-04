# Kody implementation map

Start here for current scope, detailed evidence and the next useful improvements.
This index consolidates the feature summaries previously repeated in README.
It does not replace approved requirements, decisions or the implementation records.
Last reviewed: 2026-10-04.
The [completion roadmap](platform-completion-roadmap.md) tracks the latest owner
request; [its implementation record](platform-completion-implementation.md) covers
expanded quizzes, longer course paths, dashboards and curriculum preparation.
[Sustainability analysis](sustainability-analysis.md) records provisional business
values and pilot costs separately from approved financial behavior.

## How to read the documentation

- [Product direction](game-first-product-direction.md), AGENTS.md and the approved
  SRS/SDD establish authority. [Architecture](architecture.md) defines conventions.
- The records below describe delivered slices, authorization, persistence,
  tests and limits. A document named `plan` may contain implemented scope; read
  its implementation/evidence section rather than inferring status from its name.
- [Deferred features](deferred-features.md) is the single register for owner-deferred
  functionality and production verification. No credentials or business values
  are requested again merely because work continues.
- [Operations](deployment-operations.md) owns deployment/recovery preparation and
  rehearsal evidence. README owns local setup and common commands.

## Delivered slices and boundaries

| Area / use cases | Current implementation | Detailed records |
| --- | --- | --- |
| Account registration / A01–A02 | Validated registration, private Instructor credentials, Unverified activation, bounded resend and transactional delivery. | [Registration and verification](account-implementation-plan.md) |
| Sign-in and recovery / A03–A05 | Progressive lockouts, replacement-session consent, POST logout and generic recovery; approved Archived reactivation. | [Login](login-implementation.md), [recovery/profile](recovery-profile-implementation.md) |
| Personal accounts / A06–A07 | All-role self editing; password-confirmed sensitive changes; participant archival. Deletion covers accounts without authored content after active evaluations finish. | [Editing](profile-editing-implementation.md), [archival](account-archival-implementation.md), [deletion](account-deletion-plan.md) |
| Role applications / A09–A10, G05 | Private credential history, one Pending role application, rejection/resubmission and staff review; Contributor eligibility uses distinct validated achievements. | [Instructor review](creator-review-implementation.md), [Contributor application](contributor-application-plan.md) |
| Game-first journey / B01–B02, B05, partial E01/E05/E06 | Landing trial, saved hub, three starter levels and Manila daily streaks. Game clearance unlocks the next level; quiz wins qualify daily activity. Browser trials/previews never grant progress. | [Play](play-implementation.md), [guided journeys](creator-learner-journey.md) |
| Customizable assessments / D01–D02, G08–G10 | Garden, Pixel Studio, Number Machine, Sort Lab, virtual Terminal Quest and practice quizzes; typed creator data, server replay and immutable preset snapshots. Twelve lesson starters, two curriculum plans, 1-10-question quizzes and 2-6 choices with immutable snapshots. | [Arcade](arcade-templates-implementation.md), [garden designer](garden-designer-implementation.md), [presets](game-presets-implementation.md) |
| Module and course authoring / D01–D09 | Owned drafts, moderated new/replacement publication, revision-pinned course composition, creator archival and protected deletion. Retained dependencies block deletion. | [Studio](creator-studio-implementation.md), [courses](course-composition-implementation.md), [deletion](content-deletion-implementation.md) |
| Learning / B03–B04 | Authenticated module discovery and assessment filters; free course enrollment, pinned lessons, saved assessment/reading progress, optional sequential paths and consolidated dashboards. Reading grants no streak or Contributor credit. Archived courses retain existing enrollment access; archived modules remain unavailable. | [Discovery](module-discovery-implementation.md), [course learning](course-learning-implementation.md) |
| Coding quests / C01–C07, B07 | Reviewed challenge authoring, hidden tests, revision-pinned durable submissions and concurrency-safe three-attempt limits. Judge0 adapter and fake-provider tests exist; live execution is disabled. | [Challenge studio](challenge-studio-implementation.md), [submissions](challenge-submission-implementation.md) |
| Weekly play / partial E02–E04 | Manila Sunday windows, future scheduling, immutable started events, separate three-attempt budgets and private verified-result history. Live execution depends on deferred Judge0 setup. | [Weekly events](weekly-challenge-plan.md) |
| Reactions / B10 | One replaceable/removable Like, Helpful or Favorite after server-recorded authorized use and current access; courses additionally require enrollment. | [Feedback](content-feedback-implementation.md) |
| Staff governance / G01–G06 | Protected account search/enforcement, Moderator appointments/removal, support corrections, content withdrawal/restoration, session revocation and durable audit/notice records. | [Accounts](account-governance-implementation.md), [appointments](moderator-appointments-implementation.md), [support corrections](support-profile-corrections-implementation.md), [withdrawal](content-withdrawal-implementation.md) |
| Reports / partial G07 | Read-only filtered account/content/learning/submission aggregates; no financial or reward totals. | [Report definitions](system-reports-implementation.md) |
| Help / B11, G11–G13 | Public Active FAQ search/categories and Administrator authoring with version checks/audit. | [Help](faq-implementation.md) |
| Google identity / A03/A06 integration slice | Password-confirmed explicit linking of existing accounts; no email auto-linking/new Google accounts. Implemented but disabled pending owner-deferred live setup. | [Google identity](google-authentication-implementation.md) |
| Shared interface / cross-cutting NFRs | Blue/grey light and dark themes, persisted header toggle, keyboard bypass/focus, reduced motion, responsive forms/navigation and unsaved quiz preview. | [Interface implementation](interface-implementation.md) |

This is a scope map, not a claim that every original use case or NFR is complete.
Notifications and background effects remain owned by their feature records;
provider-fake verification is not live delivery/execution evidence.

## Improvements found and addressed in this review

1. Hardcoded green surfaces and missing dark mode: shared semantic colors now
   cover both layouts, including creator/staff/account views and game controls.
2. Creator quizzes needed a save before trying edits: a separate unsaved preview
   now validates prompts and uses safe text nodes without a completion endpoint.
3. Hidden quiz/preset/video inputs remained enabled: inactive fields are now
   excluded from browser submission while their typed values remain available.
4. Overlapping quiz completion requests: a busy guard protects the interaction,
   disables controls temporarily and permits safe retry after an uncertain result.
5. Verification/recovery fragments on an already-open page were missed: initial
   load and `hashchange` now erase the secret before a single POST.
6. Preview feedback incorrectly suggested streak qualification: local quiz
   previews now explicitly disclose that answers do not save progress.
7. Narrow staff reports and uneven mobile controls: shared wrapping, wide staff
   shells, readable inputs and minimum control sizes address these layout flaws.

## Remaining verification and business decisions

- Variable-length quiz authoring, multi-question server completion, sequential
  creator paths, reading completion and dashboards are implemented in
  [the completion milestone](platform-completion-implementation.md).
- Exercise long content, keyboard authoring and error/retry paths across actual
  Safari, Firefox and mobile devices. Local checks use one Chromium surface.
- Measure staging catalog, dashboard, lesson and worker load against SRS targets.
- Publish customized launch curriculum through designated creators and staff;
  editable plans are not automatically published course records.
- Finalize provisional financial values, cap/tie/rounding/funding contracts and
  creator-deletion retention; see the deferred register and sustainability analysis.
Latest local verification: 1,012 PostgreSQL tests / 7,422 assertions, 574 cached
regressions / 4,419 assertions and 44 frontend tests passed. The cached pass
includes the subsequent rollback-guard and staff-review regressions. See the
[completion evidence](platform-completion-implementation.md#verification)
for browser observations and their limits. These local results do not substitute
for remote CI or production staging checks.
