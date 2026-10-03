# A10 instructor / creator approval

Implements A10's review slice, fed by private Pending applications from A01.
Instructors are Kody's learning content creators. Subsequent Content milestones
implement module/course authoring and publication. A06 first applications and
rejected resubmissions from existing accounts are implemented in the
[account editing follow-up](profile-editing-implementation.md), with retained
credential versions and decisions. Registration and review now share that history.

The owner approved Pending/Approved/Rejected dictionary labels, rejection
preserving Learner access, Moderator/Administrator review (A10 actors rather
than the dictionary's Admin-only control), and format checks plus manual
credibility review without an invented institutional-domain list or Invalid
account status. Registration already validates email, institution, specialization,
actual credential MIME and size.

## Authorization and persistence

InstructorApplicationPolicy protects list/detail/download/review routes. Current
account session middleware rejects expired, replaced or restricted sessions.
The action locks users in consistent ID order and then the application. It
rechecks reviewer permissions/session and Pending state/record version. Reviewers
cannot review their own application. Approval requires verified Active Learner
or Contributor access and grants only Instructor. Rejection preserves the
applicant's role and lifecycle status. No administrator account is seeded.

Migration `2026_10_03_000005_add_creator_review_and_audit` adds review metadata,
version, audit history and durable notification deliveries. It is additive.
Role change, decision, audit, pending delivery and database job commit atomically;
queue failure rolls all back. Feedback is bounded to 255 characters and required
for rejection. Concurrent/stale decisions cannot overwrite a completed review.

Audit writes store IDs/event/minimal transition context, omitting credentials,
storage paths and document contents. Downloads also create audit events. This is
durable history, not a claim of tamper-proof storage or production retention.

## Documents and notifications

Credentials stay on private Laravel storage with generated names. Authorized
downloads reject public disks/path traversal and use attachment/no-store/nosniff
headers. Paths are never rendered. The applicant sees only their own status and
escaped feedback. Review lists are paginated with eager-loaded applicants.

Jobs contain only a delivery UUID and use the safe SMTP boundary: three attempts,
30-second timeout, 10/30-second backoff. Successful retries do not repeat mail;
changed recipients, deleted accounts and stale review versions cancel pending
delivery. Errors are sanitized. SMTP acknowledgement followed by failed commit
can repeat an email, so transport semantics are at least once. Notification
failure never undoes an approved role.

Run the existing supervised database queue worker. ACCOUNT_NOTIFICATION_MAILER
inherits verification mail configuration; production uses the approved SMTP
provider. No live provider calls or production credentials were used locally.

## Verification

Full local PostgreSQL suite: 176 tests, 1,128 assertions passed. Coverage includes
both reviewers, disallowed roles, self-review, stale decisions, ineligible
applicants, escaping, private downloads/path containment, rollback, notification
retry/cancellation, CSRF and throttles. A real overlapping PostgreSQL test proves
one elevation/audit/notification. All eight migrations execute on clean test
databases in concurrency checks. Cached config/routes/views: 30 review/progression
tests, 197 assertions passed. Pint/Vite pass and CI YAML parses. Eleven frontend
template tests passed at the play milestone. Browser checks verify public play,
search and guest login/registration; authenticated reviews use feature tests.
Remote CI, real SMTP delivery and production deployment remain unverified.

## Subsequent implementation

The owner resolved authoring/publication policy and the subsequent
[creator studio](creator-studio-implementation.md) implements module drafts,
typed game/quiz attachments, preview, audit and moderation. The
[course composition](course-composition-implementation.md) slice adds ordered
reuse and reviewed course revisions. A06 resubmission and private media uploads
remain separate work. Follow the course document's next prompt and unresolved
learner access decisions.
