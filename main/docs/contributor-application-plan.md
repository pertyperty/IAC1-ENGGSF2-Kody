# Contributor applications — A09 / G05 implementation

Account Management owns Learner application admission; Administration and
Governance owns Moderator/Administrator decisions. A09's G03 cross-reference is
inconsistent with the named G05 Approve Contributor Requests workflow. Use G05
for review; G03 is account suspension.

## Approved requirements decisions

A09 requires an account at least 30 days old and “25 modules / 50 challenges.”
DDT-Contributor-Request-Information independently requires at least 25 completed
modules and 50 completed challenges. The owner approved both thresholds, with
distinct server-validated module completions and passed challenges. Repeated
revisions/weekly plays count once. Account age uses elapsed full days from the
stored registration timestamp; all three requirements must hold.

The owner approved rejected resubmissions with every prior decision and private
credential preserved, one Pending Contributor request per user, and only one
Pending role application (Contributor or Instructor) at a time.

## Approved text constraints

The owner approved 500-character limits for request_message and moderator_feedback,
with matching database columns and validation. This resolves the dictionary's
varchar(255) versus maximum-500-character conflict.

## Implemented workflow

Verified Active Learners apply at `/account/contributor-application`. The page
shows their server-computed milestones and private application history. Admission
requires a message, optional HTTP(S) portfolio URL, confirmed details and a private
PDF/JPEG/PNG supporting credential within the existing configurable upload limit.
The server never fetches the portfolio URL. User/role/status/counter/path inputs
are prohibited. Files receive generated names; private paths stay hidden.

ContributorEligibility unions standalone module assessment wins and completed
course assignments by module identity. Reading a lesson, guest/practice ladder
wins and failed assessments do not qualify. Passed durable coding submissions
are distinct by challenge identity across revisions and weekly contexts. No live
Judge0 credential is needed to view/apply; sandbox activation remains deferred,
so reaching the challenge milestone in live use depends on that later setup.

Account locks serialize admission with both role-application writers and deletion.
The latest submitted application ID rejects stale forms. Each resubmission makes
a new row; rejection feedback and old private files remain intact. A PostgreSQL
partial unique index protects one Pending Contributor request. Cross-kind Pending
exclusion uses the shared user lock in both writers. Applications do not grant roles.

Moderator/Administrator review lives at `/manage/contributors`. Policies protect
lists, details, credential downloads and decisions; downloads are audited,
attachment-only and non-cacheable. Sorted account locks, fresh reviewer sessions,
application versions and Pending-state checks prevent duplicate/stale decisions.
Approval rechecks current verified Active Learner eligibility, grants Contributor
and records the decision atomically. Rejection preserves the existing role.
The owner can view history after approval. New applications are Learner-only.

Submission queues durable notices to current verified Active Moderators/Admins.
Both outcomes queue an applicant notice. UUID-only database jobs, delivery rows
and domain state commit together. Workers recheck access/status before delivery;
in-app notices use durable IDs and are not duplicated on retry. SMTP failure
records retry state and does not roll back approval. Accepted mail followed by a
crash cannot guarantee exactly-once external email; do not claim it. Exhausted
jobs remain visible through failed jobs and need operator retry.

Deletion removes application rows, cascaded delivery rows and associated staff
notices, and queues every supporting file through encrypted account-file erasure.
Queued notices become harmless when the application disappears. Rejected history
is preserved until approved account deletion; creator deletion remains deferred.

## Migration, verification and deployment

`2026_10_03_000017_create_contributor_applications.php` adds application/history
rows, lifecycle/threshold constraints, the Pending index and notice deliveries.
No existing application is modified by the migration. Its down migration refuses
to discard existing application history; application rollback preserves schema.
Run safe migrations, database queue workers and the existing mail configuration.
Private credentials must remain outside disposable releases and public storage.
No new dependency, secret or monetary effect is introduced.

Focused PostgreSQL tests cover boundaries, actual validated course/standalone
clearances, repeat deduplication, role/status/session restrictions, private files,
500-character Unicode text, history, notifications, deletion, rollback and actual
competing same/cross-kind admissions and reviews. The full PostgreSQL suite passed
531 tests / 3,746 assertions; 32 focused tests / 219 assertions passed. All twenty
migrations execute on clean test databases. Cached configuration/routes/views
passed 106 application/profile/deletion tests / 804 assertions. Pint, 16 frontend
tests, Vite build and diff checks passed. Remote CI, live mail/storage/Judge0 and deployment
are unverified; this workflow alone does not claim privacy-compliance certification.

## Next implementation prompt

G01/G03/G04 account governance now implements the owner's approved hierarchy:
Moderators manage Learners/Contributors/Instructors; Administrators may also manage
Moderators. Block self-enforcement and Administrator targets in this release,
protecting the last Administrator. Implement fresh authorization, versioned locked
Active/Suspended transitions, immediate session revocation, durable audit and queued
notices. Preserve committed coding evaluation and unrelated user/content data.
See [governance scope](account-governance-implementation.md). Next resolve G02
assignment/demotion hierarchy and administrative field scope before general editing.
Keep owner-deferred purchases, Judge0 activation and rewards unavailable.
