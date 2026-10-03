# Creator adventures and publication

## Approved decisions

The owner approved both recommendations on 2026-10-03: only approved Instructors
author learning modules; Contributors author coding challenges. This resolves
D01's contradictory Contributor/Instructor precondition in favor of its Actor,
D02 and the module dictionary. Moderator/Administrator approval is required for
new modules and revisions of published modules. This amends D02's direct
publication option and supplies the publication policy for G06.

The owner also approved treating Published as D03's archive-eligible state,
resolving its undefined Active label against the module dictionary. Archived
modules disappear from Learning and learner access; their revisions, validated
activity and audit history remain.

## Delivered scope

- D01: Instructor studio at `/create`, owned drafts with title (150 characters),
  description, plain text/code lesson, Article/Interactive/Video formats and
  optional assessments. Interactive requires an assessment. Video uses an HTTPS
  link opened with noreferrer/noopener; no embed, remote fetch or file upload.
- D02: each save creates a new revision. Last approved content stays live during
  editing, review and rejection. Submit the saved draft separately; pending drafts
  cannot be changed. Feedback appears in the studio; rejection allows a new draft.
- G06 publication subset: Moderator/Admin queue, playable saved preview,
  approve/reject with required rejection feedback, version checks and audit.
  Approval atomically selects the exact current revision for Learning. Flagging,
  reports, removal, challenge/course moderation are future work.
- D03: owning Instructor confirms archive, with current version and fresh
  session checks. Archived adventures are read-only in the owner's studio;
  their pending revisions leave the review queue. No restore transition is added.
- F05 noncritical subset: review decisions for Active authors arrive in a
  private paginated Updates inbox using Laravel's database notification channel.
  Delivery is durable at commit; marking read is owned and idempotent. Critical
  financial/role email and user notification preferences are separate work.
- Game-first amendment: creators configure title, instructions, hint and learning
  feedback on one of three approved garden trails; mechanics/objectives are
  snapshotted as typed version-1 data. Quizzes configure two choices, the correct
  choice and explanation. Creator uploads never become executable code or HTML.
- Learning lists only current approved Published metadata, with literal-text
  search and pagination. Guests must sign in for lessons. Published game/quiz wins
  are replayed/checked on the server against the exact published revision and
  qualify the existing Manila daily streak. They do not clear the built-in ladder,
  issue grades, XP, ranks or KodeBits. Preview wins stay local.

Description/content limits (5,000/50,000 characters), template text bounds and
route throttles are operational request limits, not invented scoring rules.
Lessons and practice answer keys are visible to signed-in learners; graded
assessment/hidden answer delivery is not implemented.

## Ownership, schema and security

Content owns `ModulePublishing`; Administration authorizes publication and owns
durable audits. Notifications owns the noncritical delivery boundary. Gamification
reuses one daily-activity writer for built-in and creator adventures.

Two additive migrations create `learning_modules`, `module_revisions` and the
Laravel `notifications` table. Module lifecycle is Draft/Published/Archived/Deleted;
revision review lifecycle is Draft/Pending/Approved/Rejected. Foreign keys,
positive versions/revision numbers, unique revision numbers, status/type checks
and a composite publication pointer protect PostgreSQL invariants. Notifications
have recipient foreign keys and unique recipient/revision review delivery.

Mutations lock fresh accounts before the module; reviews lock account IDs in
ascending order. Policies and current session fingerprint/expiry are rechecked
under locks. Versions prevent stale saves/reviews/archive. Content writes,
publication, audits and in-app delivery share the transaction. Owner and
publication IDs always come from the server. HTML is escaped in lessons, previews,
catalog and inbox. CSRF, independent throttles, private no-store responses,
bounded inputs and current-revision completion checks apply.

No new package, provider secret, seed credential or external service is required.
Deployment runs normal additive migrations and rebuilds assets/caches. Do not
drop these tables on rollback; the prior application can coexist with them.

## Verification

Module feature tests cover ownership, allowed/disallowed roles, typed fields,
escaping, video scheme checks, stale writes, queue transitions, live revision
preservation, rejection, inactive authors, rollback, PostgreSQL publication
constraints, archive confirmation/history, private notifications, literal search,
pagination and completion outcomes. Real overlapping PostgreSQL tests prove one
new draft, one publication/audit/notification and one daily activity per race.
Existing account and streak tests remain part of the full suite. Frontend tests
check Unicode bounds and game/quiz mechanics including PHP/browser agreement.
Final verification: 215 PostgreSQL tests with 1,431 assertions passed; cached
configuration/routes/views: 76 tests with 542 assertions passed; 13 frontend
tests passed. Pint, Vite production build, CI YAML validation and diff whitespace
checks passed. All ten migrations execute on clean databases in concurrency
tests. Remote CI/deployment and authenticated browser visual checks remain
unverified.

## Subsequent scope and next prompt

Owned Instructor course drafts, ordered reusable approved modules, pinned
previews, reviews and noncritical updates are now implemented in the subsequent
[course composition slice](course-composition-implementation.md). The owner
approved extending publication review to courses. Follow that document's next
prompt for enrollment/access and the remaining archive policy decisions.

Private media upload/storage, deletion, creator module placement in the ladder,
graded quizzes, rewards, paid access and notification preferences remain separate
features. Module deletion must protect learner activity and course/challenge
references. Courses do not yet grant learner enrollment/access or issue rewards.
