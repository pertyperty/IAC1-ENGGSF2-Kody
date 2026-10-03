# G02 account support corrections

## Approved scope and ownership

G02 permits Administrator account editing but does not enumerate administrative
profile fields. The owner approved corrections to username and first/last name
for verified Active Learner, Contributor, Instructor and Moderator accounts when
the account holder requests help. Administrators confirm their own current
password and the support request. Self and Administrator targets retain A06
self-profile editing. Email/password changes remain in owner A06/A04 workflows;
arbitrary permissions and role changes are outside this editor. Approved Moderator
appointments/removal remain a separate G02 action.

Administration and Governance owns corrections and audit. Account Management owns
security revocation and private mail transport. This implements the approved G02
support subset, not an unrestricted administrative account editor.

## Implementation

The staff account page displays an authorized Administrator-only correction form.
The existing account catalog and Moderator view still omit legal names and email;
only eligible Administrator detail forms expose the editable first/last name.
The form requires explicit correction and support-request confirmations. This is
the Administrator's attestation, not proof from an implemented support-ticket
system. Username and name validation matches A06 (6–30-character unique username;
up to 50 Unicode letters per first/last name). Display name is server-derived.

The PATCH route has CSRF, current authenticated-session checks and a five-request
per-minute budget. The service locks actor and target users in stable ID order,
rechecks fresh Administrator eligibility and session, target eligibility, password,
confirmations and profile version, and writes only the approved fields. Stale forms
must reload. Concurrent confirmations commit one version/history/audit/job. Database
username uniqueness handles simultaneous collisions. Duplicate and storage errors
are sanitized; SQL bindings and passwords are not logged. Protected identity,
role, status, ownership and permission inputs cannot be applied.

Changed fields, derived display name, profile version increment, session/proof
revocation, correction history, minimal audit and database queue insertion are one
transaction. An unchanged submission does not increment the version, end sessions
or issue a notice. Email, password hash, verification, role/prior-role snapshot,
content ownership, learning records and committed submissions remain intact.

History and audit retain field names, actor/target IDs and version/time, never old
or new names/usernames, email, password or personal free text. Paginated staff
history is read-only. Minimal pseudonymous references survive approved deletion;
queued notices cancel for Deleted/unverified destinations. Private UUID-only jobs
send an escaped historical correction time and field list to the current verified
email. Failures retain the committed correction, record failure and retry three
times with a 30-second timeout and 10/30-second backoff. Acknowledged notices are
skipped; SMTP acceptance followed by a crash can still duplicate mail. Operators
retry exhausted failed jobs. Live delivery is not claimed.

## Migration and deployment

`2026_10_03_000020_create_account_support_corrections.php` adds correction/delivery
history with restricted foreign keys, unique target/version and checks on
non-self correction, positive version and allowed nonempty JSON field lists.
No profile values are copied into the new table. It is additive and refuses
destructive rollback after corrections exist. Preserve schema on application
rollback. Run safe migrations and existing supervised database workers with the
configured private mail transport; no new provider secret, package or money effect.

## Verification

PostgreSQL tests cover target/actor roles, self/Admin protection, enforcement states,
expired actor sessions, confirmations, password privacy, stale versions, Unicode
and length constraints, protected fields, duplicate usernames, unchanged requests,
audit/queue rollback, delivery retry, deletion cancellation, guarded migration,
database-session revocation, simultaneous confirmations, CSRF and rate limits.
The full PostgreSQL suite passed 626 tests / 4,452 assertions; all 69 G02 tests
passed with 546 assertions. All twenty-three migrations execute on clean test
databases. Cached configuration/routes/views passed 138 G01–G04/A06 tests with
1,050 assertions. Pint, 16 frontend tests, Vite build and diff checks passed.
Vite retains its existing optional font-fallback optimization warning.

Remote CI, browser visual checks, live mail and production deployment are unverified.

## Next implementation prompt

G07 read-only reports now summarize implemented account, content and validated
learning/submission records; see [report scope](system-reports-implementation.md). Keep
owner-deferred purchases, paid access, rewards and live Judge0 activation unavailable.
G06 content withdrawal still needs an approved removal/restoration policy covering
existing enrollment and pinned revisions before staff can remove published content.
