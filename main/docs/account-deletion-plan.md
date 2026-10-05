# Account deletion/anonymization — SRS A10 Delete Account

> Later amendment (2026-10-04): the owner delegated economy, XP/rank/reward,
> reviewed paid access and creator-retention decisions. See [current policies](economy-and-launch-decisions.md)
> and [implementation](economy-implementation.md). Earlier first-release deferrals below
> are historical; live providers/staging and numeric ratings remain deferred.

The SRS uses A10 for both Delete Account and Instructor verification. This record
identifies deletion by its name; it does not conflate the two workflows.

## Approved decisions

The owner approved requiring Archived users to reactivate through A04 before
accessing authenticated deletion. Ordinary Archived login/session access stays
blocked. The first deletion release covers verified Active Learners, Contributors
and Instructors with no authored modules, courses or coding challenges in any
lifecycle state. Creator deletion is unavailable until content retention/removal
rules are defined. The owner also approved waiting for Queued/Evaluating attempts
to finish before removing submission data, preserving committed evaluation.

## Implementation and data inventory

Account Management owns `GET/POST /account/delete`. The user sees an irreversible
warning, types `DELETE MY ACCOUNT`, confirms explicitly and supplies the current
password. UserPolicy, current-session middleware and a fresh locked account
protect ownership, role and status. A profile version rejects stale forms.
Content ownership is rechecked after locking the actor, serializing with authoring.
Participation locks serialize deletion with evaluation; reads use bounded chunks.

The transaction revokes sessions and security proofs through AccountSecurity,
then removes private records in dependency order. It removes email verification,
recovery and delivery rows; instructor applications, credential versions and
cascaded decision deliveries; notifications; learning activity/input/progress;
course enrollment/progress; and completed challenge submissions, source, provider
tokens and participation rows. It preserves other creators' content and audit
history. Committed queue jobs contain UUIDs and become harmless when their deleted
logical records are absent. Guest authentication proofs lose validity; their
short-lived opaque session metadata expires under existing session retention.

A09/G05 adds Contributor applications and delivery history to this inventory.
Deletion also removes related staff notices and queues current/historical
Contributor supporting files through the same encrypted erasure boundary.

A minimal users row retains the stable audit reference, synthetic random email,
`Deleted account` label, neutral Learner role and anonymized timestamp. Original
names/username/email/hash are removed or replaced, verification/session/remember
proof is cleared, and normal login/recovery cannot reactivate it. This is application
identity removal with retained pseudonymous references, not a claim that every
external observer or historical backup can no longer identify the person.

Migration `2026_10_03_000016_add_account_anonymization.php` adds the timestamp,
a constraint protecting marked account identity/status, and a durable file-erasure
outbox. Existing Deleted rows without this marker are not retroactively claimed
to be anonymized. No production data is purged by the migration.

## Private files and failure handling

Each distinct current/historical credential path creates one erasure record with
an encrypted path and a UUID-only database job in the same transaction. The outbox
retains private paths only until cleanup succeeds; completed rows clear the path.
Queue/audit/storage-write errors during admission roll back identity removal and
outbox creation. No file is removed before the deletion transaction commits.

EraseAccountFile uses private Laravel storage and guards the credential namespace,
public disks and traversal. It deletes idempotently, including already missing
objects, and clears encrypted path data after success. Failure leaves durable
pending work and sanitized diagnostics with the erasure ID only. Jobs have three
attempts, 30-second timeout and bounded backoff. Acknowledged object deletion
followed by database rollback can retry safely.

`kody:account-erasures-retry` runs every five minutes with scheduler overlap
protection, locks at most 100 pending rows and queues retries atomically. It skips
a still-existing queued/reserved job, preventing duplicate buildup while workers
are down. Failed/exhausted jobs can be requeued once their active database job is
gone. This is eventual object removal; the success message states that private
files are queued for removal and does not claim they are already erased.

## Deployment and operational limits

Run the additive migration and keep database workers/scheduler running. Queue
and database sessions must use the application database. A stable APP_KEY is
required until all encrypted paths are consumed. Monitor failed jobs and pending
file-erasure records; prolonged storage failures leave private cleanup outstanding.
No new dependency, live provider integration or monetary effect is introduced.

Do not automatically revert this migration after marked deletions/pending cleanup.
Application rollback keeps schema and tombstones; pre-erasure code does not process
this outbox, so preserve a compatible cleanup worker or assess the rollback first.
A restored historical backup can restore erased data: production restoration must
reconcile later erasure records/tombstones before exposing restored accounts.
Backup/provider/log retention and restore drills need operational verification;
this implementation alone is not a privacy-compliance claim. It cannot retract
mail already sent or source already delivered to Judge0. Live Judge0 remains
explicitly deferred by the owner.

## Verification and remaining work

PostgreSQL/provider-fake/private-storage tests cover roles, ownership, confirmation,
password privacy, stale forms, restricted/Archived sessions, active evaluation,
completed-source removal, learner data removal, other-owner content preservation,
audit/queue rollback, competing confirmations, encrypted file outbox, deduplication,
cleanup retry/idempotency, scheduler requeue and guarded migrations. The full
PostgreSQL suite passed 499 tests / 3,527 assertions; cached configuration/routes/
views passed 95 deletion/profile/archival tests / 727 assertions. All nineteen
migrations execute on clean test databases. Pint, 16 frontend tests, the Vite
build and diff checks passed. Focused tests use the suite-wide name filter so
existing cross-file fixtures are loaded. Remote CI,
live provider/storage delivery, browser visual checks and deployment are unverified.

Creator deletion remains in the deferred-feature register.
G02 Moderator appointment records retain only minimal role/audit references after
deletion; queued appointment notices cancel against the Deleted tombstone. See
[Moderator appointment scope](moderator-appointments-implementation.md).

The next independent scope after initial deletion
was A09/G05 Contributor applications, now implemented under approved eligibility,
text, history and Pending-overlap rules. See [Contributor scope](contributor-application-plan.md).
Keep paid purchases, paid admission,
Judge0 activation and reward formulas deferred as approved.
