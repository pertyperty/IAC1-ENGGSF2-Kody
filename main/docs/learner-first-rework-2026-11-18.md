# Learner-first rework

The owner's 18 November request pauses additional third-party/API implementation
until the learning product is ready. Judge0 remains the first planned later
integration. This batch changes account registration, learner presentation and
assessment authoring; it does not activate any provider, add floors or introduce
another progression system.

## Public comparison and observed behavior

Reviewed the running public projects in a browser on 18 November, rather than
inferring their interaction from source alone:

| Reference | Observed interface/behavior | Application to Kody |
| --- | --- | --- |
| [Flexbox Froggy](https://flexboxfroggy.com/) | A visible CSS editor sits beside the frog/target world; the task and supporting property explanation share one main area. Entering a property changes the world. | Keep the program and result visible together, with one primary task. Its long first-level explanation is not a word-count target for Kody. |
| [CSS Diner](https://flukeout.github.io/) | A short task sits above the visual target and code area, with selector reference beside it. Entering `plate` and submitting advanced the session from level 1 to level 2, which asks for bento boxes. | Use a concrete success condition, feedback near the attempted action and an explicit next activity. Keep explanations secondary. |

Reference screenshots and Kody's final screenshots are retained as local
verification artifacts, not copied into product assets. The comparison report is
`storage/app/private/learner-first-comparison.html`; the browser captures are in
`storage/app/private/learner-first-evidence/`. The earlier baseline was inspected
during the work; the final comparison report uses public-reference/current screens. These examples inform interaction
choices, not claims that their accessibility, pedagogy or design is uniformly good.
Do not copy their artwork, curriculum or branding.

## Delivered behavior

- Practice opens directly into one task with a compact concept selector, rather
  than an eight-card lobby above the activity. The goal, program and result have
  distinct places. Pseudocode stays visible and bounded as instructions accumulate.
- Movement controls name their actions. Run, Reset, Hint, Step, Replay and Restart
  have visible labels. Decorative forward-arrow SVGs are removed from text actions
  throughout the views; meaningful SVG navigation/game icons remain. No text glyph
  is introduced as an icon substitute.
- Shared reading/authoring/help surfaces use whitespace and separators instead of
  repeated nested boxes. Inputs, game cells and meaningful feedback keep visual
  boundaries. Automatic character-limit paragraphs are now opt-in; native limits,
  field labels, explicit guidance and error links remain.
- Lessons show their explanation before the assessment. The module studio is a
  sequential lesson/assessment flow. New lessons start with a guided assessment;
  custom worlds and prior saved activities remain editable through Custom activity.
  Access settings stay available but secondary.
- Course creation, enrollment and saved continuation use lesson/course labels:
  Course summary, Order the lessons, Add lesson, Enroll in course and Next lesson.
  Text-only authoring actions no longer append decorative plus signs. Unsaved
  game previews refresh the task and contextual hint from the selected activity.
- Ten original tasks cover movement sequences, repeated bodies, conditional
  collection, coordinates, assignments, indexed swaps, file inspection, Boolean
  logic, string pipelines and stacks. Each has a reference program, a bounded
  objective and a short concept-transfer question. Quiz Lab now asks learners to
  trace and apply concepts rather than only recall a definition.
- Guided tasks constrain the available instructions where useful: the number
  task cannot be solved with arbitrary one-step addition, and the stack task
  requires manipulating TOP instead of reconstructing the target with PUSH.
  A working program opens the concept check. Correct program and concept answer
  are both replayed on the server before eligible completion is recorded.
- Retry/hint feedback stays inline. Arcade feedback describes the actual unmet
  condition (value, position, cell, text or stack), rather than only saying to try
  again. Existing saved-result continuation remains available.

The task library is not a secure examination: formative answer keys are public,
as they already are for practice quizzes. It does not establish mastery or prove
alignment with arbitrary lesson prose. Instructors must select a task that matches
their lesson, and reviewers must check that alignment. Human learning-effectiveness
and enjoyment testing remain necessary.

## Registration terms

The owner explicitly authorized original Terms and Conditions and supplied the
University of Baguio address. The address was cross-checked against the
[university contact page](https://ubaguio.edu/contact-us/). The published text names
the Kody project team and uses the address for project correspondence; it does not
claim university endorsement or invent a corporate identity. The
[NPC Data Privacy Act text](https://privacy.gov.ph/data-privacy-act-/), particularly
the purpose/proportionality/retention principles, informed the minimal evidence
and notice wording; this is not a compliance certification or a liability waiver.

Public dated Terms and Privacy pages are linked from registration and footers.
Registration requires an initially unchecked Terms agreement, validates the
current version at both HTTP and action boundaries, and records server time and
version in the same transaction as the account and verification delivery. Privacy
is a notice, not marketing consent. No IP or user-agent acceptance record is added.
The accepted fields are not mass assignable and are hidden from model serialization.
Legacy accounts retain null acceptance; registration does not fabricate consent
or silently force a new existing-user agreement policy.

Migration `2026_11_18_000001_record_registration_terms_acceptance.php` adds nullable
paired version/time columns and a PostgreSQL consistency constraint. Its rollback
refuses to discard retained acceptance. Its check and column removal hold a table
lock in one transaction so concurrent registration cannot slip between them.
Run it before serving the new registration
code; do not migrate the owner's development database as a verification shortcut.
If application code must roll back, retain these columns and evidence. Pause new
registration while running an older release that cannot record this agreement.
Future terms must use a new immutable dated page/version. Production operator,
retention, minor-handling and legal review remain release work; this change does
not certify those organizational decisions.

## Preservation and verification

Existing module/game snapshots are not rewritten, including Tower stages and
custom scenarios. Guided rules/checks are copied into new immutable lesson
revisions; changing the library later cannot relax a published task. Server
replay, role/session/access checks, reward uniqueness, locking and audit remain.
Previewing a task creates no learner activity. Financial history/support stays
accessible, and no package or external contract is changed.

Verification was performed by the coding agent on 18 November 2026 on Windows,
PHP 8.4.27, Node 22.20.0 and a disposable loopback PostgreSQL 18.6 cluster.
The final application/test source is commit
`64a51ee14c005a6efc4aeee1b989bba02a8ea3de`; the later documentation commit
records its results. The owner's development database was not migrated or reset.

| Check | Local result |
| --- | --- |
| Frontend suite | 65 tests passed, including reference/hostile browser-server parity for all ten guided tasks. |
| Scoped static analysis | All 32 scoped PHP files passed at level 5 with the database deliberately unavailable; no baseline or blanket ignores. |
| Formatting / dependencies | Full Pint, strict Composer validation, platform requirements and Composer audit passed. |
| Production builds | Main and Office bundles passed. |
| Backend full first run | 1,378 cases: 1,371 passed, seven failed, 10,763 assertions. Six stale wording expectations and one quiz fixture were corrected. This is not a claim of a clean final full-suite run. |
| Affected backend rerun | 163 passed / 1,164 assertions, including every failed case, registration, publication and assessment checks. |
| Later course/registration/assessment rerun | 117 passed / 946 assertions after the course wording follow-up. |
| Rollback follow-up | 44 registration/assessment tests passed / 235 assertions, including safe empty rollback and refusal when acceptance is retained. |
| Cached configuration/routes | 236 passed / 1,486 assertions. Private test cache paths were removed afterward. |
| Broad rebuilt Chromium run | 74 workflows: 72 passed; a stale Tower quiz-action label and an unfocusable scrolling explanation failed. Both were corrected and verified below. No clean final full-browser-run claim. |
| Final affected Chromium workflows | All 26 passed, including all game previews, guided loop/stack authoring, no-write practice, registration, credentials, Office resources, staff pages and Expert completion. A separate Tower authoring/publication rerun also passed. |
| Cross-engine smoke | All 12 passed across Chromium, Firefox and WebKit: Tower list/track continuation, bounded pseudocode, Help/registration access and keyboard scrolling of long activity explanations. |
| Populated upgrade/restore | Both integrity checks and the 85-table restore passed; local restore took 5,845 ms. Legacy users acquired no acceptance record. |
| npm security audit | Still fails on the inherited picomatch advisory: six high-severity dependency entries, registry latest 4.0.7, no patched release. No suppression, incompatible downgrade or audit-threshold change. |

Ignored logs under `storage/app/private/learner-rework-*` and the screenshot
comparison are local development evidence. The initial browser pass found a real
terminal contrast regression and an outdated Tower preview selector, plus stale
copy/control expectations and a missing sign-in wait in the new capture test.
The broader rebuilt run exposed the scrolling explanation and quiz-action label;
the final affected runs verify their corrections. Goals, explanations and trace
feedback now remain keyboard reachable when their bounded text areas overflow.
The broad run used `14576ad`; the quiz action was corrected in `8dffcbb` and its
isolated workflow passed. The final 26-workflow and cross-engine runs verify the
keyboard correction in `64a51ee`. The 44-test rollback follow-up verifies
`6f9e673`. Subsequent commits only update documentation.

These checks are not human curriculum approval, deployed capacity, a remote CI
result or production readiness. The inherited picomatch audit gate remains
tracked in [static analysis](static-analysis-2026-11-18.md#npm-advisory-gate).

### Scope and remaining work

This batch supports A01 registration, B03/B05 learning and assessment, D01-D07
authoring/publication and the shared learning interface. It preserves their
existing admission, review and reward rules. The 80 Tower floors and existing
custom activities retain their saved content; the new guided library is an
authoring/practice option rather than a silent curriculum replacement.

Before expanding content or activating Judge0, observe a small human pilot:
find and resume a Tower floor, explain and repair a failed program, transfer the
concept to the follow-up question, and author/review one matching lesson. Record
where learners hesitate, whether they can explain the result and what needs to
change. A correct reference program or an automated browser pass cannot answer
those questions. Named curriculum ownership, production legal/retention decisions,
real-device accessibility and staged operations remain in the
[deferred register](deferred-features.md) and
[release evidence index](release-evidence-index.md).
