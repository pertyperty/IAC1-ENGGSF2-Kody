# A07 account archival

Account Management owns archiving. The SRS A07 actors are Learners, Contributors
and Instructors; the owner's all-role A06 edit approval does not expand A07.
Verified Active users in those three roles may confirm archiving their own account
and enter their current password. Moderators/Administrators cannot self-archive
through this flow. Cancellation leaves account state unchanged.

`GET/POST /account/archive` displays the warning and accepts confirmed archival.
The policy, authenticated session middleware and service enforce fresh ownership,
role/status/session, profile version and password. The service locks the account
and rejects stale profile forms. It changes Active to Archived, advances the
profile version and records an audit atomically with session/proof revocation.
Database errors are sanitized without SQL bindings. Concurrent confirmations
produce one status change and one audit.

`AccountSecurity` is shared with A06 credential changes. It clears the durable
session fingerprint, rotates the remember token, deletes database sessions and
invalidates old verification/recovery proofs and pending authentication mail.
It changes no learner progress, profile identity, password, role, published
content, enrollment or credential history. An audit failure rolls everything
back. Other session stores lose access on their next protected request.

Archived accounts cannot sign in. The approved A04 recovery workflow remains
available for verified Archived accounts: request fresh recovery proof and choose
a different password to reactivate the same account and role. Recovery retains
its existing cooldown/expiry rules; archiving does not reset request budgets or
A03 failure counters. Committed challenge evaluation keeps its prior lifecycle.

No new migration, external integration, financial effect or provider credential
is introduced; this uses A06's profile-version migration. Database session state
must use the application database for atomic changes. Existing mail workers serve
later recovery requests. This is reversible archival, separate from the SRS's
permanent account deletion/anonymization use case.

Tests cover all allowed and blocked roles, cancellation/validation, password
privacy, stale forms, fresh restricted/replaced/expired sessions, intact profile
and progression data, rollback, database-session deletion, CSRF, actual competing
PostgreSQL confirmations and archive-to-A04 recovery. The full PostgreSQL suite
passed 468 tests / 3,286 assertions; cached configuration/routes/views passed
64 account-editing/archival tests / 486 assertions. All eighteen migrations execute
on clean test databases. Pint, Vite build, diff checks and the previously verified
16 frontend tests passed. Remote CI, live providers, authenticated browser visual
checks and production deployment remain unverified.

The owner resolved the deletion authentication conflict: Archived accounts must
reactivate through A04 before authenticated deletion. The first deletion release
covers accounts without authored content and waits for active evaluations to
finish. See [deletion scope](account-deletion-plan.md). The source labels both
deletion and Instructor verification A10; name the intended use case explicitly
in traceability instead of conflating them.
