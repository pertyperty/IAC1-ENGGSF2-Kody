# Completion roadmap implementation

Use cases: B01, B03–B05, C01–C02, D01–D02, D05–D07 and G08–G10;
cross-cutting authoring, accessibility and integration preparation.

## Creator quizzes

The shared module/preset editor now accepts 1–10 questions with 2–6 choices each,
explicit correct answers and required feedback. Add/remove/reorder controls keep
stable identifiers, announce changes and disable invalid boundary actions.
One-question quizzes retain `choice-quiz` version 1. Multi-question quizzes use
version 2 with a typed `questions` collection. All questions must be correct for
a validated practice win. These remain public-answer practice assessments, not
secure academic grading. Local previews have no completion endpoint.

`QuizAuthoring` validates collections and checks completion for both formats.
HTTP validation rejects unknown fields, duplicate question/choice IDs, duplicate
trimmed choice text, out-of-range collections and answers outside their question.
The learner submits either a single `answer` or an exact question-ID `answers`
map. Partial, wrong or extra answers cannot produce a win. Existing ownership,
session, access, review and streak checks remain in their services.

Migration `000029` allows version-2 **quiz** preset snapshots while retaining the
other version-1 template constraints. Existing preset revisions/module snapshots
remain unchanged when a preset is edited. Deploy code that understands both
versions before permitting authoring. An older application release cannot safely
read new quizzes; migration rollback refuses retained version-2 presets.

## Longer course paths and reading

Owner-approved on 2026-10-04: creators may select optional sequential paths;
existing enrollments preserve access. Each course revision now stores a
`sequential` boolean. New enrollments copy it from the approved revision; existing
rows default to `false`. Revisions/enrollments keep their policy through later
edits and moderation, so authors cannot change a learner's path mid-course.

`CourseLearning` rechecks every earlier assignment's persisted completion before
opening or completing a sequential lesson. Completion of the same module outside
this enrollment does not silently clear its assignment. User locking serializes
enrollment visits/completions; PostgreSQL foreign keys and uniqueness retain the
existing pinned revision/assignment integrity. Creator/staff previews stay separate
from learner entitlement. Archived/withdrawn-module and staff-block rules apply
before path access. Course trails label locked steps and show real progress.
Staff review explicitly displays the proposed access policy. Withdrawal takes
precedence over locked-step labels, so a withdrawn lesson title stays hidden.

An assessment-free lesson requires an authorized opening followed by an explicit,
CSRF-protected `Mark as read` action. Reading completion is idempotent, audited and
atomic; the stored input marker is `{"kind":"reading"}`. It advances only course
progress. It creates no activity day, starter-level clearance, streak or XP.
Contributor eligibility explicitly excludes reading markers from course credit;
existing validated game/quiz history remains eligible. Assessment lessons cannot
be cleared through the reading endpoint. Migration `000030` adds the two booleans
without changing prior progress or enrollments. Rollback refuses retained
sequential policies or reading history; use a compatible application release.

## Consolidated dashboards

The saved play hub includes four recent owned enrollments and completion counts,
six recent authorized course visits, five confirmed coding attempts, three updates
and the unread count. Instructor summaries include owned modules, courses and
quests; Contributor summaries include quests. Publication/pending/draft counts
come from the latest revision. Staff retain their notices without participant
course panels. No financial/reward values are implied by these summaries.

Queries are bounded, use eager-loaded pinned course revisions and aggregate lesson
counts, and do not retrieve source code or hidden test data. Withdrawn courses
use a generic retained-progress notice. Recent lesson links omit unavailable
modules/courses; protected destination routes remain the authority for subsequent
access. The dashboard is private, non-cacheable and makes no progress writes.

## Curriculum preparation

Two original course plans contain eleven ordered lesson slots: introductions,
sequence/loop/condition games, four data/terminal games and two three-question
capstones. Twelve reusable lesson examples include the prior single-question
starter. The Instructor-only curriculum page opens unsaved editable examples and
course metadata; it does not auto-save, import, publish, approve or enroll.
Staff must review customized modules before course composition, then review the
course. This library prepares launch content; actual published curriculum still
depends on the designated creators and reviewers completing that workflow.

Three original beginner coding problems cover addition, parity and countdown,
each selectable for Python, Java or C++. Examples include visible samples and
hidden zero/negative/boundary checks. Their authoring page retains role restrictions
and no-store responses. Outputs are checked against deterministic reference rules;
actual language execution still requires the deferred Judge0 sandbox.

## Easier later provider setup

The named `sendgrid` mailer uses SMTP with the existing secure-account mail
boundary, required TLS, authenticated username and a 10-second timeout. It is not
selected automatically. `SENDGRID_API_KEY` is a private config input and never
sent to browser JavaScript. The `kody:setup-status` command reports sanitized local
presence checks, performs no provider calls and cannot enable anything. See
[integration setup](integration-setup.md). Live setup remains owner-deferred.

The owner's draft Philippine-peso packages, 65/35 net split, tie rule and 4% cap
are recorded separately in [sustainability analysis](sustainability-analysis.md).
They remain provisional; purchases, earnings and rewards await finalized rules.

## Verification

Focused PostgreSQL checks cover preserved quiz/preset snapshots, malformed author
input, all-question completion/idempotency, sequential enrollment compatibility,
reading authorization/audit rollback and exclusion from Contributor credit,
dashboard isolation/bounded queries, curriculum selectors/draft saves and
secret-free setup/SMTP construction. Frontend tests cover 2–6 choices, complete
answer sets, reordered stable IDs and malformed authoring.

### Repeatable browser acceptance scenarios

Run against isolated test data with provider fakes. Never create production
credentials or bypass the normal creator/staff review workflow for a test.

| Scenario | Required observations |
| --- | --- |
| Creator quiz authoring | Add a third choice, select it, move it up, then add/reorder a second question. The correct answer stays attached to its choice. Preview, save a draft and reopen: order and answers persist; preview saves no learner progress. |
| Sequential course | Enroll in a reviewed course containing a reading lesson followed by an assessment. The later lesson is locked; opening the first does not clear it. Mark as read unlocks the second and records only course progress. |
| Assessment completion | Submit incomplete and wrong answer sets, then all correct answers. Only the complete correct set clears the lesson and qualifies daily activity. Repeating completion creates no duplicate activity. |
| Enrollment compatibility | Approve a sequential replacement for a previously open course. Existing enrollments retain their pinned open path; new enrollments snapshot the reviewed sequential policy. |
| Account isolation | A second learner cannot open another enrollment or see its progress/attempts/notices. Withdrawn content is unavailable even through saved links. |
| Responsive dashboard | Check 320, 390, 768 and 1280 CSS-pixel widths, both themes and long titles. No horizontal page overflow; actions remain readable and reachable. |
| Keyboard navigation | Tab to the visible Skip to content link, activate it and verify focus reaches main content. Quiz edit/reorder controls preserve usable focus and announce updates. |
| Later provider setup | Run `kody:setup-status`; output contains no secrets and makes no provider call. Actual mail, OAuth, code evaluation and supervised operations require the separate staging guide. |

The local browser pass verified quiz reorder/preview/draft reload, explicit
reading unlock, a three-question win and persisted dashboard progress. At all
four widths the dashboard had no observed horizontal overflow. Light/dark
preferences persisted; keyboard bypass focused `main-content`. This pass also
corrected an oversized primary action and uneven dashboard panels. The disposable
browser database, temporary fixtures and preview server were removed afterward.

The full PostgreSQL pass completed 1,012 tests / 7,422 assertions before the
additional rollback-guard and staff-review regressions were added. Cached
configuration/routes/views passed 574 tests / 4,419 assertions, including those
regressions and the withdrawn locked-title correction. The first cached attempt
exposed a local harness mismatch: its hashing driver was not set to bcrypt as in
CI. Matching CI's explicit `HASH_DRIVER=bcrypt` resolved it without changing the
application or weakening the regression. Caches were cleared after both runs.
All 44 frontend tests, Pint, the Vite production build and workflow
YAML validation passed. Dashboard tests verify four bounded enrollment results
with at most eight queries; this is query-growth evidence, not an SRS load result.
Actual Safari/Firefox/devices, staging latency/concurrency, HTTPS, provider
delivery, cloud supervision and managed restoration remain owner-deferred.
