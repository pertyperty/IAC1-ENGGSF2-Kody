# G02 Moderator appointments and removal

## Requirement and approved scope

G02 (Administrator account editing) requires a defined role hierarchy and notices
for significant changes. The owner approved verified Active Learner, Contributor
and Instructor accounts being appointed Moderator by an Administrator, then
returned to their recorded prior participant role on removal. Self and
Administrator targets are blocked. Existing Moderators without a recorded prior
role cannot be removed through this workflow. Contributor and Instructor elevation
remains in A09/A10 review. This implements the approved role subset of G02;
general administrative personal-field or permission editing is not implemented.

Administration and Governance owns the action and audit. Account Management owns
session/proof revocation and private notification transport. No financial effect
or external integration is added.

## Implementation and security

The existing `/manage/accounts/{account}` staff page shows an Administrator-only
role confirmation form and paginated appointment history. The server derives the
resulting role; the request cannot supply arbitrary roles, status or restoration
values. The Administrator confirms with their own current password and explicit
consent. CSRF, authentication, current-session middleware and rate limits apply.

The service locks actor and target users in stable ID order, then rechecks the
fresh Administrator session, verified Active eligibility, target hierarchy,
expected action and profile version. Appointment saves the prior participant role;
removal restores it and clears the snapshot. Version increments, session/proof
revocation, durable role-change history, minimal audit and database queue insertion
commit atomically. Competing confirmations cannot apply the same transition twice.
The prior-role snapshot is hidden from ordinary user serialization.

Existing profiles, content, learning records, applications and committed coding
evaluations are retained. Tools follow the current role, so an appointed creator
must return to their original role to use participant authoring tools. Pending
applications remain recorded; their existing fresh eligibility checks prevent
review approval while the applicant is a Moderator. Role editing does not approve
or cancel applications. Old browser sessions are invalid after both transitions;
a new login is required.

UUID-only notification jobs deliver an escaped historical action/time/result to
the current verified email. Deleted or unverified destinations are cancelled.
Delivery failures keep the committed role change and record failure for retry.
Jobs use three attempts, a 30-second timeout and 10/30-second backoff; failed jobs
need operator retry. Acknowledged notices are skipped on repeated jobs. SMTP
acceptance followed by a crash can still duplicate external mail; exactly-once
mail is not claimed. History stores no email, password or personal free text.
Approved deletion retains minimal pseudonymous role/audit references.

## Migration and deployment

`2026_10_03_000019_add_moderator_appointments.php` adds a nullable prior-role column
and `account_role_changes` with restricted foreign keys, unique target/version,
non-self target and valid transition constraints. Existing Moderators retain NULL
prior roles; no legacy role is guessed. The additive migration refuses destructive
rollback when role history or snapshots exist. Roll back application code while
preserving the schema. Use safe migrations, supervised application database queue
workers and configured private mail transport. No new package or secret is needed.

## Verification

Focused PostgreSQL tests cover all three participant roles, hierarchy and legacy
protection, stale versions, explicit confirmation, password checks, restricted
actors/targets, protected inputs, rollback, private notices and delivery failure,
retained content/applications/evaluations, tombstone cancellation and database
constraints. Separate PostgreSQL processes test simultaneous appointment/removal;
database-session tests prove old Moderator browsers lose access after removal.
All 33 focused G02 tests passed with 255 assertions. The full PostgreSQL suite
passed 590 tests / 4,161 assertions. All twenty-two migrations execute on clean
test databases. Cached configuration/routes/views passed 102 G01–G04/A06 tests
with 759 assertions. Pint, all 16 frontend tests, the Vite build and diff checks
passed. Vite reports an existing optional font-fallback optimization warning;
the build succeeds without that optional package.

Remote CI, live mail delivery, browser visual checks and deployment are unverified.

## Next implementation prompt

Resolve G02 administrative personal-field scope before implementing a broader
account editor. The SRS does not identify the allowed profile attributes or a
staff email/password correction process. Recommended next scope for owner decision:
Administrator corrections to username and first/last name on verified Active
participant/Moderator accounts, with current Administrator password, confirmation,
audit, session revocation and a notice; retain owner email/password flows in A06/A04,
and keep self/Administrator targets in self-profile editing. This proposal is not
approved or implemented. Owner-deferred purchases, live Judge0 activation and
rewards remain tracked in `docs/deferred-features.md`.
