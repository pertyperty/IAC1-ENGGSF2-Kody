# A04 recovery and A05 profile

Account and Authentication owns these operations. A04 uses a hashed, single-use
recovery token with encrypted pending mail and an atomic database queue job.
The reset locks the account, checks current eligibility and token state, changes
the password, consumes the token, resets login lockouts and revokes sessions in
one transaction. It never automatically signs the user in. Archived accounts
must choose a different password and become Active only on successful reset.

## Approved decisions

The user approved generic confirmation for all request outcomes, resolving A04's
Email Not Found alternate flow against its nondisclosure business rule. Verified
Active and Archived accounts across all five roles may recover; Suspended,
Deleted and Unverified accounts cannot. Recovery cannot bypass moderation.
The previously approved 12–32 character password rules and secure hashing apply.

## Security, persistence and deployment

Migration `2026_10_03_000003_add_account_recovery` adds a unique recovery record
per user and a constrained purpose on existing verification deliveries. It is
additive and defaults existing deliveries to verification. Verification and
recovery cancellation are isolated. No production data reset is required.

Tokens travel in fragments, are removed from browser history, and are submitted
by CSRF-protected POST. A manual code form supports JavaScript-free access.
Validated recovery proof expires after at most five minutes and is bound to the
guest session. Raw tokens and passwords are never flashed or put in job payloads.
Status, verification, email, password and expiry are rechecked during completion.
The shared mail boundary accepts SMTP, plus the array transport only in tests.
Provider errors produce fixed sanitized failures. Successful delivery retries
are harmless; SMTP acknowledgement followed by a commit failure can repeat mail.

Run the existing supervised database queue worker. Recovery inherits the
verification mailer unless ACCOUNT_RECOVERY_MAILER is configured. Its defaults
are 60-minute token expiry, 60-second resend cooldown and five requests per hour;
these are configurable operational limits because A04 gives no numeric values.
Configure the approved SMTP provider and stable managed APP_KEY before release.
Database queue and database sessions must use the application database so their
effects can commit atomically. Other session stores are revoked by the durable
account fingerprint when they next request a protected route.

A05 returns an explicit allowlist of the current account's profile fields. Email
is partially masked, sensitive fields are excluded, missing legacy fields show
availability notes, and output is escaped. All roles inherit Learner access.
The account session middleware checks current status and session ownership.
Client-provided account IDs never select another profile. Responses prohibit
caching. The dashboard links to this profile without exposing the full email.

## Verification

Local PostgreSQL 17 / PHP 8.4 full suite: 134 tests, 854 assertions passed.
Coverage includes every role and eligible status, blocked status, token expiry
and replay, changed credentials, rollback, mail retries, privacy, CSRF, limits,
database session revocation and two actual overlapping PostgreSQL recovery
tests. All six migrations execute on a clean test database during concurrency
tests. Pint and the Vite production build passed; the existing optional fontaine
fallback warning remains. No live providers, remote CI or deployment were run.

## Next implementation prompt

Implement A06 own-account editing and A07 archival using the SRS flows, durable
audit history, immediate authorization checks and account row locking. Preserve
the approved A04 recovery eligibility. Resolve A06's sensitive-field definition,
email re-verification cross-reference and instructor application state labels
before claiming those flows complete. The user's later product direction takes
priority for the interface: playable games, an approachable module catalog and
instructors as learning content creators.
