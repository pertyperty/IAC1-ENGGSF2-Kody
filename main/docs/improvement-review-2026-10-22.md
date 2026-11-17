# Improvement and deferred-feature review

Follow-up, 25 October 2026: the [simple learning implementation](simple-learning-and-creator-credibility.md)
delivers DEV-01, OPS-01, QA-01 automation, LEARN-01 and the typed retry-feedback
part of LEARN-02, alongside the owner's UI and creator-credibility amendment.
Human/device/live-provider evidence and unresolved legal facts remain outstanding.
The entries below retain their original assessment context.

Reviewed 22 October 2026 at `df2b6e3`. This is an assessment and proposed backlog,
not approval of new business policies or evidence of a production deployment.
The [implementation map](implementation-status.md) owns delivered scope; the
[deferred register](deferred-features.md) remains the activation register.

The highest-value next work is reliable development verification, public-launch
requirements, operational visibility and stronger learning support. Another
large Tower expansion is lower priority: the current reference curriculum already
contains 80 levels and 111 stages across three tracks.

## Evidence and limits

- Inspected registration validation/action/template, Tower progression and quiz
  feedback, curriculum publication records, provider configuration/readiness,
  health checks, Administrator operations summaries, browser configuration and CI.
- Ran `php artisan kody:setup-status`. In this local configuration Google and
  Judge0 are disabled, SendGrid is not selected for verification, and Xendit's
  activation gate is incomplete. The queue is configured as `database`; production
  HTTPS/debug prerequisites are incomplete. These are local configuration facts,
  not live provider or production observations.
- The default `npm run test:games` failed before five PHP-backed test files could
  run: PHP startup warnings polluted their JSON output. With the working PHP 8.4
  directory prepended to this process's PATH and `KODY_TEST_PHP` set explicitly,
  **63 tests passed, zero failed**. No system PHP configuration was edited.
- Did not rerun the backend, browser or restore suites, inspect current launch
  publications in the application database, or contact external providers. Earlier
  CI totals remain historical evidence in the delivery records.

## Work needed before public launch

### LAUNCH-01: Published legal documents and recorded acceptance — P0

**Confirmed implementation gap, A01.** Registration has no Terms field or
acceptance persistence. See [validation](../app/Http/Requests/Account/RegisterAccountRequest.php),
[registration transaction](../app/Actions/Account/RegisterAccount.php) and
[registration form](../resources/views/account/register.blade.php).
The [legal publication record](legal/review-and-publication.md) already identifies
the required behavior; the short drafts are unpublished.

First resolve legal operator/postal contact, eligibility, geography, retention
and reviewed text. Then publish immutable versions, show an unchecked required
Terms acknowledgment, validate the current server-owned version, and persist
minimal acceptance evidence atomically with registration. Decide the treatment
of existing users and material changes. Keep the Privacy notice distinct from
optional consent. Do not invent retention purges while those rules are unresolved.

Acceptance: missing acknowledgment, forged/stale versions and registration
rollback are tested; the accepted text remains retrievable. Content approval is
a prerequisite; a checkbox alone does not complete this work.

### LAUNCH-02: Reviewed launch courses — P0 for a learning launch

**Editorial/publication work remains in the records.** The Tower is installed
curriculum; the two course plans and eleven lesson slots are separate editable
examples. The [launch review pack](launch-curriculum-review.md) does not establish
that attributed courses have actually been published in the target environment.

Have designated creators customize modules and staff approve them, then compose
and approve the courses through the existing workflow. Audit current target
publications first to avoid duplicating material. Prioritize Beginner material
and a small Intermediate continuation before increasing volume.

Acceptance: named ownership, approved pinned revisions, working lesson order,
correct/incorrect assessment checks, accessible media and tested continuation.
This requires editorial execution, not another publishing subsystem or automatic
production seeding.

### LAUNCH-03: Staging and release evidence — P0

**Implemented preparation, incomplete environmental proof.** Follow
[operations](deployment-operations.md) and the
[acceptance checklist](requirements-acceptance-audit.md): supervised workers and
scheduler, HTTPS/IAM/encryption, private storage, managed restore, alerts, measured
load/latency and application rollback. Measure against the unchanged 500-user
SRS target and stricter 99.9% availability target; a small pilot is not proof.

Acceptance: retained evidence for the deployed revision/environment, named
responders, timed recovery and rollback, measured queue/provider latency and
tested alert delivery. Provisioning and live setup remain deferred.

## Implementations that can be prepared independently

### DEV-01: Consistent PHP selection for tests — P1, small

**Reproduced tooling defect.** PowerShell resolves the Herd PHP wrapper; Node's
direct `php` subprocess selects Herd Lite on this machine. The latter emits
`Unable to load dynamic library 'pdo_pgsql'` and `pgsql` warnings before JSON.
[Arcade tests](../tests/Frontend/arcade-games.test.mjs) and
[Garden designer tests](../tests/Frontend/garden-designer.test.mjs) hard-code
`php`; other files already support `KODY_TEST_PHP`.

Use one subprocess helper honoring the explicit executable across PHP-backed
frontend tests. Add an early runtime/JSON diagnostic with captured stderr and
document the Windows invocation. Fix runtime selection instead of filtering
warnings out of fixture output.

Acceptance: every PHP-backed file uses the chosen executable; a broken runtime
produces an actionable failure; the same 63 tests pass locally and in CI.

### OPS-01: Worker and scheduler heartbeats — P1, medium

**Missing operational signal, not an observed outage.**
[Readiness](../app/Http/Controllers/HealthController.php) checks only `SELECT 1`.
The [Administrator summary](../app/Services/Administration/SiteManagement.php)
already reports queue depth/age, ready/reserved/delayed work and failures; it
does not prove a worker is alive when no jobs exist. Provider flags show enabled
configuration, not recent successful service use.

Add a scheduler heartbeat and a small queued probe, with last-success timestamps
and stale/unknown states in the protected operations summary. Keep public
liveness lightweight and avoid provider calls on page requests. Reuse current
queue reporting rather than building a second dashboard.

Acceptance: stopped scheduler and stopped worker are distinguishable, an empty
queue is not shown as proof of health, alerts are bounded, and private state
stays Administrator-only. No provider activation is needed to build the probes.

### QA-01: Firefox/WebKit and device verification — P1, medium

**Confirmed coverage gap.** [Playwright configuration](../tests/Browser/playwright.config.mjs)
declares only Chromium, and [CI](../../.github/workflows/ci.yml) installs only
Chromium. Responsive Chromium viewports are valuable but do not cover Safari or
Firefox behavior.

Start with a smaller Firefox/WebKit smoke matrix for signup/verification,
Tower track continuation, mixed quizzes, keyboard forms and private resource
viewing. Keep the full Chromium suite. Include real Safari/iOS/Android and
screen-reader sessions in release acceptance; WebKit automation does not replace
device evidence.

Acceptance: repeatable isolated runs with retained failure evidence; fullscreen
fallbacks, focus restoration and media controls are checked across engines.

### LEARN-01: Personal review queue and concept progress — P1, medium

**Proposed product enhancement.** Existing Tower clearances, stage history and
replay are implemented. A clearance is not a diagnosis of what a learner has
retained. Provide an owned list of concepts to revisit, initially through an
explicit “Review later” action and existing validated completion history.

Keep revision provenance and current-access checks on every link. Show completed,
available and queued-for-review states without a mastery score or fabricated
earlier clears. Replaying uses current reward/streak rules; queuing an item
itself grants nothing. This can begin without collecting failed answers.

Acceptance: cross-user isolation, stale/withdrawn-content filtering, useful empty
states and keyboard/mobile operation. A placement diagnostic is separate optional
scope requiring validation, not a prerequisite for this feature.

### LEARN-02: Better remediation and teaching evidence — P2, medium

**Proposed enhancement to existing feedback.** Mixed quizzes already show feedback
per question; games already have hints and Step/Replay. Current quiz feedback
uses the same explanation for wrong and correct choices in
[the grader](../resources/js/games/quiz-types.js). Add optional typed feedback for
specific misconceptions and a second worked example rather than replacing these
working features with a new generic tutorial.

Use a human pilot before drawing learning-effectiveness conclusions. If aggregate
Tower difficulty/drop-off analytics are later desired, define event meaning,
retention, participant notice and minimum reporting groups first. Browser-only
failures are self-reported practice signals, never authoritative achievement data.
Avoid storing raw code/answers just to count retries.

Acceptance: old immutable quiz versions remain usable, notes are editable and
escaped, wrong/correct paths are tested, and human findings are kept distinct
from automated reference-solution checks.

### QA-02: Incremental static analysis — P2, medium

Composer provides Pint and tests; no static analyzer is configured. Introduce
Laravel-aware PHP analysis for touched code and a reviewed baseline, then expand
coverage. Evaluate JavaScript linting separately. This is maintainability work,
not an assertion that existing passing tests are inadequate or that a new tool
has already found defects.

Acceptance: pinned compatible dependencies, a reproducible CI command and useful
checks without mass suppressions or unrelated refactors.

### OPS-02: Release evidence index — P2, medium

[The launch check](../app/Console/Commands/LaunchReadiness.php) intentionally checks
configuration and a staging attestation; it does not verify reviewed legal text,
published curriculum or recent operational evidence. Keep that scope clear.
Add a separate release checklist/index connecting each required artifact to its
environment, Git SHA, reviewer and date. Later automate individual checks where
evidence exists, without silently promoting a config flag into live verification.

Acceptance: incomplete/unknown requirements stay visible and block only their
appropriate release scope; optional Google login or numeric ratings do not block
an otherwise valid password-based pilot.

## Deferred features: checked disposition

1. **SendGrid:** transport, OTP and durable notices exist; live setup remains
   deferred. Authenticated sender/domain, real delivery/failure tests and
   supervised delivery are needed before normal public email-based onboarding.
   Highest provider priority for a public pilot.
2. **Judge0:** adapter, queued evaluations, preflight and attempt safeguards exist;
   live execution is disabled locally. Verify the private plan, compiler IDs,
   limits, timeout/error behavior, billing and measured feedback before enabling
   code submission. Important for coding quests, weekly play and genuine challenge
   passes used in Contributor eligibility; Tower simulators do not replace it.
3. **Xendit:** ledger, checkout status/receipts, reservations, refund and
   reconciliation code exist. Merchant/channel/fee/tax contracts and sandbox
   success/failure/reversal/timeout proof plus staging operations remain required.
   Schedule after the free learning pilot is dependable.
4. **Google identity:** explicit linking/sign-in code exists but is disabled.
   Callback/consent/session verification remains. Optional convenience; password
   sign-in can launch without it.
5. **AWS/staging:** local runbooks and restore evidence exist; managed deployment,
   restore, supervision, monitoring and capacity evidence are outstanding.
6. **Terms/Privacy:** unresolved content plus missing acceptance implementation;
   tracked alongside deferrals, but this is a real public-launch requirement.
7. **Learning-effectiveness/placement:** Expert content is already delivered.
   Human effectiveness evidence remains; diagnostic placement and secure exams
   are separate possible features, not promised capabilities.
8. **Actual devices/capacity/availability:** verification work remains even after
   adding more automated browser engines.
9. **Numeric ratings:** optional future policy/implementation. Existing
   Like/Helpful/Favorite reactions satisfy the adopted B10 slice.
10. **Direct Tower/preset rewards and paid Tower prerequisites:** separate policies
    remain deferred. Keep current free floors and existing module/weekly economy
    rules; do not treat earlier pricing, general XP, payouts or creator-erasure
    work as still unimplemented.

## Suggested sequence

1. DEV-01, then OPS-01 and QA-01: improve confidence and operational visibility
   while external setup stays deferred.
2. LEARN-01 plus a small human pilot: improve retention and identify confusing
   existing lessons before authoring another large expansion.
3. Resolve LAUNCH-01 content decisions and verify LAUNCH-02 publications; implement
   legal acceptance once reviewed versions exist.
4. When live setup is resumed, stage operations and SendGrid first, Judge0 next,
   Xendit after the learning pilot. Google remains optional.

This review updates navigation/current-scope documentation only. The backlog is
ready for scoped implementation; no new policies are adopted by this assessment.
