# A06 self-profile editing and Instructor applications

## Approved decisions and scope

Account Management owns these operations and calls the shared verification,
private storage and Administration audit boundaries. The owner approved editing
personal fields for all five roles, extending A06's three-role actor list.
Username and first/last name need normal validation. Email/password changes
require the current password, revoke sessions and old security proofs, and
changed email becomes Unverified until A02 succeeds. This resolves A06's undefined
sensitive-field category and erroneous verification cross-reference.

Profile edits cannot grant a role or change account status directly. The owner
approved verified Active Learners and Contributors submitting Instructor
credentials, with rejected applicants allowed to submit a fresh version. Pending
applications block duplicates; previous decisions and documents remain private
history. Moderator/Administrator approval alone grants Instructor access.

## Implemented behavior and integrity

`GET /account/edit` exposes only the signed-in account's editable fields and
version. `PATCH /account` validates the existing DDT name/username/email limits
and approved password rules. A locked fresh account/session and UserPolicy enforce
ownership. Migration `2026_10_03_000014_add_profile_version.php` adds a positive
profile version independent of login timestamps; stale forms cannot overwrite a
concurrent save. No-op submissions do not create another version or audit.

Ordinary edits retain the current session. Credential changes invalidate recovery
and verification tokens, cancel pending authentication mail, rotate the remember
token, clear the durable session fingerprint and remove database sessions. Password
changes do not reset A03 failure counters: only successful login/recovery does.
Email changes reset the verification request budget for the new address and
enqueue its verification through the existing encrypted delivery/UUID job path.
Credential mutation, revocation, audit, delivery and database job are one transaction.
Queue or audit failures leave the previous profile intact. Sensitive values are
never flashed, rendered in audit context or logged with SQL bindings.

`GET/POST /account/creator-application` supports existing-account applications.
File validation checks actual MIME, supported PDF/JPEG/PNG formats and configured
size. Private Laravel storage generates the filename. The action rechecks fresh
role/session, locks the account then application, validates its record version
and accepts only a first application or a Rejected one. It preserves account
access while the new version is Pending. Losing concurrent/stale submissions
clean up their new upload; prior credential files remain. Audit records store
IDs and version, never paths or credential contents.

Migration `2026_10_03_000015_add_instructor_application_versions.php` stores private
credential snapshots and per-version decisions with a unique application/version
constraint. Registration records its initial version; review records its decision
against that snapshot; resubmission keeps the old snapshot and creates a new one.
Existing records are backfilled from their known current state. The migration
does not reconstruct unrecorded earlier history. Owners see paginated escaped
decision history without storage paths. Existing privileged credential downloads
continue to serve the current private document with authorization and auditing.

## Deployment and remaining scope

Run both additive migrations and retain the existing database mail worker and
stable APP_KEY. Database sessions and queues must use the application database
for atomic effects; other session stores are revoked by the account fingerprint
on their next protected request. No new provider, package or financial effect is
introduced. No live mail provider was exercised locally.

The credential-history down migration refuses to erase populated history.
Application rollback should keep the schema/data; do not automatically revert
migrations. A pre-history release would stop recording new credential snapshots,
so assess compatibility before rolling back. Object retention/deletion needs a
privacy-aware policy before implementing account deletion; this change preserves
the owner-approved version history and does not claim regulatory compliance.

A07 account archival, account deletion and administrative account editing remain
separate. Profile editing does not provide self-assigned Contributor or privileged
roles. Judge0 API-plan setup remains deferred as previously approved.

## Verification

Tests cover all five edit roles, actor ownership, protected inputs, validation,
password re-authentication, normalization, old-proof invalidation, verification,
session deletion, SQL error sanitization, audit/queue rollback, CSRF, stale saves,
private credentials, duplicate Pending applications, rejected resubmissions and
retained approval decisions. Separate PostgreSQL processes exercise competing
profile saves and credential applications. The full PostgreSQL suite passed
447 tests / 3,144 assertions; cached config/routes/views passed 97 focused tests /
823 assertions. All eighteen migrations execute on clean test databases. Pint,
16 frontend tests, Vite build and diff checks passed. Remote CI, real mail delivery,
authenticated browser visual checks and deployment remain unverified.

## Next implementation prompt

The owner deferred F01 purchases; see the [deferred-feature register](deferred-features.md).
Continue A07 own-account archival with password confirmation, current session
checks and A04 recovery eligibility preserved. Before later paid purchases,
obtain exact package prices/currency/quantities, use server-owned snapshots and
an idempotent ledger, and credit only after a verified provider callback.
