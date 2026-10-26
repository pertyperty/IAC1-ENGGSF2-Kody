# A10 instructor / creator approval

Implements A10's review slice for private Pending applications from verified
existing Learner/Contributor accounts. Instructors author learning modules and
courses through the Content studio. A06 first applications and rejected
resubmissions retain credential versions and decisions through the
[account editing follow-up](profile-editing-implementation.md). The later
[onboarding amendment](panel-learning-and-onboarding.md) keeps registration to
username, email and password; qualification collection belongs to the separate
verified-account application flow. Retained older registration applications
continue to use the same review history.

The owner approved Pending/Approved/Rejected dictionary labels, rejection
preserving Learner access, Moderator/Administrator review (A10 actors rather
than the dictionary's Admin-only control), and format checks plus manual
credibility review without an invented institutional-domain list or Invalid
account status. The application flow validates institution, specialization and
actual credential MIME/size before private storage.

## Authorization and persistence

InstructorApplicationPolicy protects list/detail/download/review routes. Current
account session middleware rejects expired, replaced or restricted sessions.
The action locks users in consistent ID order and then the application. It
rechecks reviewer permissions/session and Pending state/record version. Reviewers
cannot review their own application. Approval requires verified Active Learner
or Contributor access and grants only Instructor. Rejection preserves the
applicant's role and lifecycle status. Approval never grants Administrator access;
guarded local role fixtures are separate from this workflow.

Migration `2026_10_03_000005_add_creator_review_and_audit` adds review metadata,
version, audit history and durable notification deliveries. It is additive.
Role change, decision, audit, pending delivery and database job commit atomically;
queue failure rolls all back. Feedback is bounded to 255 characters and required
for rejection. Concurrent/stale decisions cannot overwrite a completed review.

Audit writes store IDs/event/minimal transition context, omitting credentials,
storage paths and document contents. Downloads also create audit events. This is
durable history, not a claim of tamper-proof storage or production retention.

Current and retained Instructor downloads and Contributor downloads now share
Account-owned `CredentialDocuments`. It checks the resource policy before history
or storage access and applies the same private-disk/path/header rules used by
qualifications badge proof checks. Missing disk configuration is unavailable
proof, not an uncaught disk-resolution error. See the
[refactoring review](refactoring-review-2026-10-26.md) for the security matrix;
[creator credibility](simple-learning-and-creator-credibility.md) owns the later
public-profile/badge scope and private history UI.

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
