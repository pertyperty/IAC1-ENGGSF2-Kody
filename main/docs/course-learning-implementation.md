# Course learning and archiving — B01/B03/B04/D08

The subsequent [completion milestone](platform-completion-implementation.md)
adds owner-approved optional sequential paths, explicit reading completion and
consolidated dashboards. Existing enrollments remain open; reading grants no
streak or Contributor eligibility credit.

## Approved decisions

The owner approved these amendments in chat on 2026-10-03:

- B03/B04's Published-and-Active content preconditions mean Published content
  with a verified Active account. The content dictionaries have no Active state.
- D08 uses owning Instructor authorization, Published as archive-eligible and
  Policy A: existing enrollees retain access and can continue progress. This
  resolves the Contributor actor conflict and the undefined retain/lock policy.
- Currently authored courses offer free enrollment in this release. Paid
  enrollment stays unavailable until pricing and the KodeBit ledger exist.

The original approved source files have not been rewritten. Individual module
archiving still follows D03: archived modules cannot be opened or completed,
including from an archived course with retained enrollment.

## Delivered behavior

`/learn/courses` provides paginated, literal title/description/category search
over current approved Published course metadata. Guests can browse; course
details and enrollment redirect to authentication. Learner, Contributor and
Instructor accounts can enroll; Moderator/Admin do not acquire learner access
through these routes (B03/B04 actor lists).

Course details show the reviewed outline. Joining requires an explicit CSRF POST
with the displayed revision ID. A changed publication rejects stale confirmation.
Enrollment snapshots the approved course revision and its ordered module
revisions; existing enrollment is returned idempotently on a repeated request.
Publication replacements affect future enrollment, preserving already enrolled
lesson content and order. An unavailable module blocks new enrollment.

`/learn/courses/mine` lists the authenticated user's journeys, linked from the
play dashboard and Learning. Every outline, lesson and assessment request freshly
checks the account, current session and enrollment. Slot lookup is scoped to
that enrollment's course revision; another course's slot or a newer outline's
slot cannot be used to obtain content. Course archiving permits enrolled access;
Draft/Deleted courses and Archived/Deleted modules remain unavailable.

Opening a lesson records its first/last access. Reopening shows saved assessment
clearance and the same approved template. The browser's unfinished program is
not autosaved. Course game/quiz endpoints validate the pinned template on the
server, save the first successful assessment clearance and invoke Gamification's
shared daily-activity writer. Replays can qualify another Manila day. Shared
module/day/kind uniqueness prevents duplicate streak credit across standalone
and course play. Course completion does not clear starter ladder levels or grant
XP, grades, ranks, KodeBits or earnings. Lessons without assessments remain
readable and revisitable; reading alone is not a verified streak activity.

The owning Instructor confirms course archiving in the studio. The transaction
rechecks authorization and record version, archives the course and records an
audit. Publication pointers, course/module revisions, assignments, enrollments,
progress and notifications survive. Archived courses leave public browsing,
new enrollment and review queues; the owner's editor becomes read-only with
saved previews. No restore or deletion is implemented.

## Ownership, integrity and deployment

Content owns `CourseLearning` enrollment/access and `CoursePublishing` archiving.
Gamification owns deterministic assessment validation and daily streak writes.
Its shared approved-revision writer is internal: Content authorizes the pinned
lesson before invoking it. Standalone completion still requires the current
published module revision. Administration owns durable audit recording.

One additive migration creates `course_enrollments` and `course_module_progress`.
Unique user/course grants prevent duplicate enrollment. Composite foreign keys
bind enrollment to its own course revision and progress to assignments in that
same revision. Duplicate slot progress is prohibited; completion timestamps and
validated input must be present together. Foreign keys restrict deletion and
indexes support recipient lists and references.

Transactions lock fresh account first, course second, and modules in ascending
ID order for enrollment. Access/completion takes the account, course and lesson
module locks in that order. Archiving/publication share the course lock, so a
grant is either committed before archiving or rejected afterward. Locks and
durable uniqueness protect overlap; audit failure rolls back enrollment.
All private reads use no-store, Blade escapes content, protected mutations use
CSRF and independent throttles, and numeric routes reject malformed IDs.
Client ownership, payment, eligibility and completion claims are ignored.

Deploy with normal migrations, asset build and Laravel caches. No new package,
provider secret, job or scheduler setting is needed. Keep enrollment/progress
tables during application rollback. Real SMTP, Judge0, payment providers,
production NFRs and remote CI are outside this verification.

## Verification

Feature tests cover public filtering/search, login redirects, explicit and
idempotent grants, eligible roles, ownership and cross-course slot attempts,
stale publications, unavailable modules, pinned historical lessons, server
game/quiz results, retained archived access, private resumes, CSRF, rollback and
PostgreSQL constraints. Real overlapping PostgreSQL requests prove one enrollment
and audit, and one assessment clearance/activity/streak. Final local verification:
278 PostgreSQL tests with 1,949 assertions passed; 112 cached-configuration,
route and view tests with 889 assertions passed; all 13 frontend tests passed.
Pint, Vite production build and diff checks passed. All thirteen migrations run
on clean disposable PostgreSQL databases through the concurrency suite. Remote
CI, authenticated browser visual checks and production deployment are unverified.

## Next implementation prompt

Implement versioned coding-challenge authoring and review (C01/C02/C05) for the
approved Python/Java/C++ list, with hidden test isolation, bounded execution
settings, auditable ownership and stable revisions. Keep all learner code
execution behind the future Judge0 adapter. Resolve the challenge lifecycle
conflict was subsequently resolved by the owner: Draft/Published/Archived/Deleted
challenge lifecycle with separate revision review states, and fresh moderation
for published replacements. See [challenge studio](challenge-studio-implementation.md)
for the resulting authoring/review milestone. Judge0 limits/provider IDs still
require verification against the configured provider.
