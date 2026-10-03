# Account registration and email verification

Authority: SRS v1.4 A01/A02, the account data dictionary, SDD 3.3/4.1/4.2,
and AGENTS.md. Account Management owns this implementation.

## Approved requirements decisions

On 2026-10-03 the project owner approved both amendments in this chat:

- A01 passwords use 12–32 characters, with uppercase, lowercase, numbers and
  symbols, and Laravel secure password hashing. This resolves the dictionary's
  conflicting 2–32 range and description of encryption.
- Add Unverified to account statuses. Registration starts Unverified; successful
  A02 email verification changes the account to Active.

## Implemented traceability

| Requirement | Implementation and evidence |
| --- | --- |
| A01 guest registration | Form Request validates identity, uniqueness and password rules; RegisterAccount explicitly creates Learner/Unverified accounts. RegistrationTest covers invalid fields, duplicates, role escalation, guest restrictions and throttling. |
| A01 Instructor alternate flow / A10 handoff | Validated PDF/JPEG/PNG credentials use private storage and generated names; a Pending instructor application is saved atomically with the account. No Instructor privileges are granted. Tests cover actual MIME, private paths and rollback cleanup. |
| A01 delivery failure | Account, verification state, encrypted delivery record and database job commit together. SendVerificationEmail retries bounded SMTP delivery; failure preserves the account and token. Tests cover rollback, retry, stale work and sanitized failures. |
| A02 activation | Secure random token, hashed verification state and configurable expiry; single-use activation under a user row lock. Feature tests cover expiry, replay, changed email, suspended status and invalid tokens. |
| A02 resend rules | Five requests including registration and a one-minute cooldown, enforced under the same user lock. Generic responses avoid disclosing account state. Tests cover the cap and cooldown. |
| PostgreSQL concurrency | Two real overlapping processes exercise duplicate registration, final-slot resends and simultaneous activation. Tests inspect PostgreSQL lock waits before releasing the competing requests. |

The incremental migration adds account fields, lifecycle/profile constraints,
case-insensitive email uniqueness, verification state, durable deliveries and
instructor applications. Legacy identity fields remain nullable; previously
verified users become Active. Preflight existing data for duplicate lowercase
emails before deployment. No seeded accounts or production credentials exist.

## Security and operation

Privileged fields are excluded from mass assignment and rejected in registration.
Verification tokens are encrypted in pending deliveries, absent from job payloads,
and erased on delivery/cancellation/activation; only a hash remains in verification
state. Tokens travel in email URL fragments, which browsers do not send to servers.
The page removes the fragment and posts the token with CSRF protection; a manual
code form supports browsers without JavaScript. Token input is never flashed.

Verification always uses the database queue on the application database, even
when another default queue is configured. Run a supervised database worker.
Configure ACCOUNT_VERIFICATION_MAILER=smtp with sandbox SMTP settings locally,
and the approved SendGrid SMTP transport for production after provider validation.
Log/failover mailers are rejected because they can expose verification secrets.
SMTP timeout is 10 seconds, job timeout 30 seconds, with three attempts and
10/30-second backoff. SMTP delivery is at least once: an acknowledgement followed
by a database commit failure can repeat an email, but cannot repeat activation.

Expiry defaults to 60 minutes and credential size to 5 MiB as configurable
operational limits, not newly asserted SRS requirements. Keep APP_KEY stable and
managed; pending token ciphertext requires it. Private uploads must use persistent,
non-public storage and deployment permissions appropriate to the chosen platform.
Notification failures do not roll back successful account creation.

## Remaining scope and decisions

A03 login/session handling is implemented in the subsequent slice; successful
verification now offers sign-in. See [login implementation](login-implementation.md).
A10 manual review,
notifications and privileged application access are not implemented. Its
Pending Review/Accepted versus dictionary Pending/Approved labels and Invalid
account status need an explicit decision before that workflow. Current storage
uses the dictionary's Pending state only.

Terms acceptance is described in the SRS functional summary but not detailed
A01, and no approved Terms content/version has been supplied. This remains an
explicit requirements/content decision, not invented legal text.

Production SendGrid configuration, sandbox delivery, operational monitoring,
deployed privacy controls and NFR measurement remain release work. This slice
does not complete every A01/A02 alternate flow or A10, and proves no production
availability or compliance target.

## Local verification (2026-10-03)

- PHP 8.4.26, PostgreSQL 17.11, Node 22; isolated test database.
- Complete Pest suite: 53 tests, 245 assertions passed, including three actual
  overlapping PostgreSQL process tests.
- Cached configuration/routes/views: 40 account and health tests, 201 assertions
  passed; caches cleared after verification.
- Pint and git diff whitespace checks passed; Vite production build passed.
  The existing optional fontaine fallback warning remains non-blocking.
- Browser inspection verified registration layout, Instructor field toggling,
  home links and automatic fragment-token submission with an invalid test token.
  A separate automated test enables CSRF enforcement outside Laravel's test bypass.
- Providers were faked; no live email or production service was contacted.
  GitHub-hosted CI and production deployment have not been run.
