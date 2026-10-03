# Versioned creator courses

## Approved decisions and traceability

The owner approved Moderator/Administrator review for new courses and published
course revisions on 2026-10-03. Approved Instructors author courses. Owned modules
may be reused across courses; each course revision pins specific approved module
revisions. This replaces the module dictionary's single optional `course_id`
representation with ordered revision-specific assignments.

D05/D06 metadata and edit scope (the source incorrectly labels Create/Edit Course
D01/D02) and intended D07 assignment (its source heading incorrectly says D09)
are implemented. G06's publication subset and noncritical F05 notifications also
cover courses. This composition milestone did not include lifecycle removal,
enrollment or financial effects. The subsequent [course learning milestone](course-learning-implementation.md)
implements free B03/B04 enrollment/access and D08 archiving. The subsequent
[content deletion milestone](content-deletion-implementation.md) implements D09
only without retained dependencies. Financial effects remain unavailable;
owner/reviewer previews do not grant learner access.

The owner separately resolved SRS 3.1.1 versus 3.4: Version 1 code challenges
support Python, Java and C++ only. `config/challenges.php` records that scope.
No Judge0 integration or compiler IDs are claimed by this configuration.

## Behavior

Instructors use `/create/courses` to create course drafts, add metadata and select
their approved Published adventures in order. The picker searches published
titles, shows up to 200 recent matches and preserves existing selections. Drafts
may be empty while being composed; submission requires at least one adventure.
Blank picker slots are omitted and positions are stored consecutively starting
at 1. Duplicate modules are rejected within a revision; reuse across owned courses
is allowed. Maximum 100 modules, 5,000 description characters and 10,000 duration
hours are operational request bounds. Dictionary constraints enforce title 150,
category 50, Beginner/Intermediate/Advanced and positive duration.

Saving a revision snapshots the selected modules' current approved versions.
Creators see these versions and can try the saved course outline. Existing
published course content and order remain pinned during edits, review, rejection
and later standalone module publication. Pending course revisions cannot be
edited. Rejection requires feedback and allows saving a replacement draft.

Moderator/Admin review at `/manage/courses` verifies the saved outline and its
adventures. Submission and approval recheck module ownership/Published status
and approved pinned revisions. If a module was archived, approval is blocked and
the author must revise the course. Review never silently substitutes a newer
module revision. Approvals atomically select the reviewed course revision and
send an owned Updates inbox notification for an Active author. Preview wins do
not save streaks or grant XP, grades, ranks, KodeBits or enrollments.

Course titles are unique within a category, using PostgreSQL lower/trim identity
keys. Draft courses reserve their current title/category; Published courses retain
the approved identity during edits. Renames reserve the new identity on approval,
with database uniqueness protecting concurrent collisions. Conflicts are shown
as validation errors without SQL details. Superseded history does not reserve old
titles permanently. This normalization is an engineering implementation of the
dictionary uniqueness rule, not a category taxonomy or scoring decision.

## Architecture and persistence

Content owns `CoursePublishing`; course policies reuse the established Instructor
author and Moderator/Admin reviewer eligibility. Shared `ReviewPublicationRequest`
validates module/course decisions. Administration owns audit recording and
Notifications owns noncritical in-app delivery. No new dependency is added.

Two additive migrations create `learning_courses`, `course_revisions` and
`course_revision_modules`, plus a unique course review notification index.
Foreign keys preserve references; composite foreign keys prevent cross-course
publication pointers and mismatched module revision assignments. Versions,
revision numbers, durations and positions are positive; enums are checked;
revision numbers, module assignments and positions are unique.

Writers lock fresh account rows first, course second, and selected modules in
ascending ID order. Review locks account IDs in ascending order and rechecks
policy/session fingerprint/expiry under lock. Versions reject stale writes.
Metadata, ordered assignments, audit, publication and inbox writes are atomic.
Server identity owns authorship/publication; client owner/status/price fields
are ignored. Read routes enforce ownership/reviewer permissions and scope preview
slot IDs to the requested course revision. Text is escaped, private responses use
no-store, and mutations have CSRF and independent throttles. Canonical numeric
course route IDs avoid PostgreSQL invalid-ID queries.

Deployment uses normal additive migrations, builds assets and caches; no new
provider secrets or queue configuration are needed. Preserve the new tables on
application rollback. Do not run down migrations against production data.

## Verification

Feature tests cover course metadata, order, ownership/role checks, invalid
assignments, reused pinned modules, escaped feedback, pending/stale writes,
availability/author rechecks, sanitized uniqueness conflicts, rollback,
PostgreSQL constraints, private updates, picker search, CSRF and throttles.
Real overlapping PostgreSQL edits/reviews prove one replacement revision and one
publication/audit/notification. Existing account/module/progression tests remain
in the full suite. Final local verification: 252 PostgreSQL tests and 1,689
assertions passed; 88 cached-config/route/view tests and 642 assertions passed;
13 frontend tests, Pint, Vite build, diff checks and CI YAML validation passed.
All twelve migrations execute on clean PostgreSQL test databases. Remote CI,
authenticated browser visual checks and deployment remain unverified.

## Next implementation prompt and required decisions

Build the learner course catalog, free enrollment/access and resumable progress
(B01/B03/B04), pinning enrollment to approved course revisions and reusing the
server-validated game/quiz activity writer. Paid enrollment must use a durable
ledger and atomic access grants when the financial layer is implemented.

These decisions were approved on 2026-10-03: Published content with verified
Active accounts resolves B03/B04; D08 uses owning Instructor authorization,
Published as archive-eligible and Policy A (retained access). Current authored
courses may offer free enrollment; paid enrollment remains unavailable until
pricing and the ledger exist. The implementation is documented in
[course learning](course-learning-implementation.md).
