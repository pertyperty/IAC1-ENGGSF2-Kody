# A03 login and session management

Sources: SRS v1.4 UC A03 and account dictionary; A07 archive restrictions;
SDD 3.3/4.1/4.2; AGENTS.md. Account Management owns authentication and session state.

## Approved decision (2026-10-03)

A03's exception flow gives lockouts at failures 3/6/9, while its Business Rules
say the duration increases after every subsequent failure. The project owner
approved the explicit schedule in this chat:

- Third failed password: 15 minutes.
- Sixth failed password: 1 hour.
- Ninth failed password: 24 hours; every three subsequent failures: 24 hours.
- A cooldown does not reset the counter. Successful login resets it; successful
  recovery must also reset it when A04 is implemented.

A03's Email Not Found and Account Not Verified headings are interchanged; their
actual system responses identify the intended behavior. Unknown emails and
wrong passwords share the credentials error. Status-specific responses require
a correct password, apart from the required account lockout notice.

## Implementation and schema

LoginRequest validates format and normalizes email. LoginController invokes
LoginAccount and returns a result; no client role, user ID or redirect controls
the authenticated account. Users sign in by email as explicitly specified by
A03, despite the dictionary describing username as a login identifier.

The additive login migration stores a durable failure count, lockout deadline,
last-login timestamp and active-session fingerprint/expiry. PostgreSQL checks
protect nonnegative counts and paired session fields. These fields are hidden
and excluded from mass assignment. No external provider or queue is used.

LoginAccount locks the user row before checking credentials/current state,
updating failures, or replacing a session. Only Active, verified users may sign
in. Suspended, Archived and Deleted accounts are denied. Malformed inputs do not
count as incorrect passwords; attempts during a lockout do not extend it.
Independent IP limits supplement the persistent account lockout.

Laravel's SessionGuard regenerates the session ID and CSRF token. The action
records a SHA-256 fingerprint of the resulting random ID, never the raw ID.
Any transaction failure clears the in-memory session before propagating the
error. Successful login records last_login_at and clears failures/lockout.

An unexpired existing session requires explicit continue/cancel consent.
Credential proof is kept only in the new guest session for five minutes as an
internal account ID, password-hash digest and previous-session fingerprint.
It contains no plaintext password. Continuation rechecks expiry, password,
lockout, account eligibility and the previous fingerprint under lock. Competing
confirmations cannot both replace the same session. Cancel preserves the old
session. A changed password/session invalidates the pending confirmation.

Protected routes require both `auth` and `account.session`. The latter rechecks
fresh verified/Active state, fingerprint and idle expiry, and extends expiry on
protected activity. A replaced session loses access on its next protected
request; an already-running request cannot be recalled. Sensitive future
actions must independently recheck authorization/state inside their own
transactions. Logout clears only its matching fingerprint, so an old browser
cannot terminate its successor. Session invalidation rotates the CSRF token.

The authenticated `/dashboard` is an account welcome page and logout entrypoint.
It establishes A03's redirect/session boundary; B01's consolidated course,
progress, activity and notification dashboard remains unimplemented. Account
pages expose only the current user's identity. Protected responses use no-store.
Verification results now link to the login page.

## Password and logging review

Production defaults to Laravel Argon2id so approved 12–32-character passwords
retain all bytes even with multibyte characters. PHP documents bcrypt's
72-byte truncation; Laravel supports both algorithms. See the
[PHP password_hash reference](https://www.php.net/manual/en/function.password-hash.php)
and [Laravel hashing documentation](https://laravel.com/framework/docs/13.x/hashing).
Recognized bcrypt/Argon2i/Argon2id legacy hashes remain usable and upgrade to the
configured algorithm at successful password login. Conflict confirmation has
no plaintext password and defers rehashing until a subsequent password login.
Production PHP must include Argon2id support; tests use fast bcrypt fixtures and
also exercise real Argon2id, multibyte integrity and legacy upgrades.

Boost's development browser logger is disabled because it records full URLs
and could capture verification fragments before JavaScript removes them.
Its development MCP functionality remains available. Passwords and verification
tokens are not flashed, stored in job payloads or included in application logs.
Deployment must use HTTPS, secure cookies, stable managed APP_KEY, private
storage and the documented database session configuration.

## Verification and remaining work

Pest covers all roles, validation, wrong/missing credentials, restrictions,
the approved lockout schedule, exact cooldown expiry, hash upgrades, session/CSRF
rotation, failed transactions, logout, suspension during a session, stale/expired
confirmation, cancellation and independent browser-session ownership.
Real PostgreSQL process tests prove competing password failures preserve the
threshold, only one simultaneous login authenticates directly, and only one
confirmation replaces a particular session. Database-session tests prove
replacement revocation and protect the successor from stale logout requests.

A09/A10 approval, Google OAuth and the full B01 dashboard remain
separate slices. Terms acceptance appears in SRS's functional summary but is
absent from detailed A01, and no approved Terms document/version was supplied;
record the requirement decision and legal content before claiming A01 complete.
A04 recovery and A05 profile are covered in [their implementation record](recovery-profile-implementation.md). Production monitoring,
provider validation, CI execution on GitHub and performance targets have not
been proven by local tests.

## Milestone verification (2026-10-03)

- Full suite: 97 tests, 555 assertions passed on PHP 8.4.26/PostgreSQL 17.11.
- Cached configuration/routes/views: 46 login/session/hash/health/authorization
  tests, 311 assertions passed. The earlier 40 cached registration/verification
  tests also passed before the login slice.
- All five migrations succeeded on a freshly recreated disposable test database.
- Pint, Composer validation/platform checks, Composer audit, npm audit and Vite
  build passed. Optional fontaine fallback support still produces the existing
  non-blocking build warning. Workflow YAML parsed successfully.
- Browser inspection verified the login form layout under ordinary session/CSRF
  middleware. Functional authentication uses automated tests and test credentials.
- Review included the accumulated foundation and A01/A02 changes. It corrected
  shared throttle keys, protected development URL logging, and preserved Unicode
  password integrity before staging. No real credentials or generated assets
  are included in the commit.
