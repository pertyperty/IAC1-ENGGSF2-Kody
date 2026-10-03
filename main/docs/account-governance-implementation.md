# Account governance — G01 / G03 / G04

Administration and Governance owns staff account browsing and enforcement.
Account Management owns session/proof revocation; Coding Challenge Management
continues to own already-committed evaluation. G02 role/personal-field editing
is separate and is not implemented by these routes.

## Approved hierarchy and scope

The owner approved Moderator enforcement of Learner, Contributor and Instructor
accounts. Administrators may also enforce Moderator accounts. Self-enforcement
and Administrator targets are blocked, preserving the last Administrator in this
release. A later expansion of Administrator-target enforcement needs its own
hierarchy and last-administrator rules.

G03 moves Active to Suspended after explicit confirmation. G04 moves Suspended
to Active. Archived, Deleted and Unverified accounts cannot be converted through
these actions. Reinstatement does not verify an email, reset a password, clear a
brute-force lockout, grant a role or restore an old session. Account access still
requires all existing authentication and verification checks. There is no
invented automatic suspension duration.

## Implementation

`/manage/accounts` provides read-only, paginated username search and role/status
filters. Search treats SQL wildcard characters literally. Validated filters remain
in the current staff session and pagination URLs after refresh/interruption.
Lists/details display account ID/username, role/status and minimal enforcement
history. They do not disclose email, legal names, passwords, authentication proofs,
private credentials, source, balances or financial data. Pages are non-cacheable.

Policies, current-account-session middleware and a fresh locked actor protect
enforcement. Sorted actor/target locks serialize with account edits, applications,
security actions and competing confirmations. The target profile version and
expected lifecycle state reject stale or duplicate requests. The server derives
the resulting status; role/status/ownership inputs are prohibited.

AccountSecurity revokes authenticated sessions, remember/session proof and pending
verification/recovery proofs in the same transaction as status/version update,
enforcement record, audit and database queue job. Reinstatement keeps old sessions
revoked and requires a new login. Profile, content, progression, enrollment and
committed coding submissions are preserved. Suspended actors cannot initiate new
attempts or privileged actions; existing durable attempts continue evaluating.

Each enforcement row has a durable UUID, actor, target, action, version and delivery
timestamps. Notifications use a UUID-only database job and verified current email;
Deleted/unverified destinations are cancelled. Suspension email does not require
the recipient to remain Active. The notice identifies the original action/time,
so delayed delivery does not falsely describe the latest account status. User-facing
times use Asia/Manila; stored timestamps remain canonical UTC.

Mail failures preserve state and record retry/failure timestamps. Jobs have three
attempts, 30-second timeout and bounded backoff; failed jobs need operator retry.
Concurrent/repeated jobs lock the same row and skip acknowledged delivery.
SMTP acceptance followed by a crash can still duplicate email; exactly-once
external mail is not claimed. No notification failure reverses committed enforcement.

Approved deletion retains minimal pseudonymous enforcement/audit references
and cancels later notice delivery through the Deleted tombstone. No original email
or free-text personal data is stored in enforcement history. Backup/provider mail
retention remains an operational responsibility.

## Migration and verification

`2026_10_03_000018_create_account_enforcements.php` adds enforcement/delivery rows,
foreign keys, unique target/version and action/self-target constraints. It is
additive and refuses destructive rollback when history exists. Application rollback
preserves the schema. Run safe migrations, the existing supervised database workers
and configured private mail transport. No new package, secret or monetary effect.

Focused PostgreSQL tests cover hierarchy, self/Admin protection, lifecycle and
stale version checks, invalid/protected inputs, expired/restricted actors, immediate
database-session deletion, reinstatement/new login, rollback, durable notification
retry, committed evaluation during suspension and tombstone history. Separate
PostgreSQL processes prove simultaneous suspension/reinstatement commits one
transition and audit. The full PostgreSQL suite passed 557 tests / 3,906 assertions;
26 focused governance tests / 160 assertions passed. All twenty-one migrations
execute on clean test databases. Cached configuration/routes/views passed 87
governance/application/deletion tests / 606 assertions. Pint, 16 frontend tests,
Vite build and diff checks passed.
Remote CI, live delivery, browser visual checks and deployment are unverified.

## Next implementation prompt

G02 permits Administrator account editing and says role changes must follow
defined hierarchy rules, but does not define those assignment/demotion rules or
how they interact with approved A09/A10 contributor/creator review. Resolve allowed
role transitions and administrative field scope before building general account
editing; do not silently create a route that bypasses credential review or lets
an Administrator remove the last Administrator. Owner-deferred purchases, Judge0
activation and rewards remain unavailable.

Proposed next role scope for owner decision: Administrator appointment of
Moderators from verified Active participant accounts, and removal to the recorded
prior participant role; keep Contributor/Instructor elevation in A09/A10 and block
self/Administrator role changes. Existing Moderators without a recorded prior
role need an explicit resolution before demotion. This proposal is not approved
or implemented. Administrative personal-field editing remains a separate scope.
