# Google account linking and sign-in — A03/A06

## Approved scope

The owner approved explicit Google linking for existing verified accounts after
confirming their Kody password. Registration remains A01/A02. A Google email that
matches an existing account is never sufficient to link, register or authenticate
that account. This is an incremental integration, not completion of every OAuth
registration flow in the source documents.

Verified Active accounts of all five roles may manage their own linked identity.
Google must report a verified email, but an explicitly selected Google identity
may use a different email from Kody. Provider email never replaces Kody email,
verification, profile or role. One Google subject belongs to at most one account;
one account has at most one linked Google subject. Replacing it requires unlinking
first. The local password remains available, including recovery through A04.

Linking and unlinking revoke sessions and pending account-security tokens, advance
the profile version and create an audit event atomically. The user signs in again
afterwards. Unlinking remains available even when Google configuration is disabled.
Password recovery does not implicitly unlink an established Google credential;
use the explicit unlink action to remove it. Suspension, archival, deletion and
password lockouts remain enforced during Google sign-in. A03's existing session
replacement confirmation is reused, with an additional linked-identity version
check. Successful Google sign-in resets failed-login counts as a successful login.

## Implementation and ownership

Account and Authentication owns the workflow. Thin controllers and a Form Request
delegate to `GoogleAuthentication`, `GoogleOAuth` and the existing `LoginAccount`.
The adapter uses MIT-licensed Laravel Socialite v5.31.0, pinned in composer.lock;
its declared Illuminate compatibility includes Laravel 13. No existing locked
package was updated. Tokens, provider profile, avatar, raw subject and email are
not persisted. Only a SHA-256 digest of the namespaced Google subject is retained.

The additive `2026_10_03_000027` migration introduces `google_identities` and
`google_auth_attempts`. PostgreSQL uniqueness, foreign keys and checks enforce
ownership, linked-state consistency, valid hashes, positive versions and typed
callback intent. Unlink keeps an empty versioned identity row to reject stale
forms after unlink/relink. Account deletion removes both records before retaining
the existing anonymized account/audit reference.

The authorization-code flow requests only `openid email`, uses session state and
S256 PKCE, and asks Google to select an account. A callback record binds the intent
to the initiating session and, for linking, current profile/identity versions.
Records expire after five minutes. Starting another flow replaces the prior
session's record. A row lock commits consumption before any provider HTTP request;
simultaneous or replayed callbacks cannot exchange the code twice. The linking
transaction locks the account and rechecks the current session, authorization and
versions after provider verification. A unique provider subject prevents competing
accounts from acquiring it.

The provider adapter uses fixed Google HTTPS endpoints and server-to-server
userinfo. It does not trust a browser-supplied identity/email/ID token. HTTP calls
have a three-second connection timeout and eight-second request timeout, TLS
verification and no following redirects. Authorization-code exchange is never
automatically retried; failures require a fresh flow. Provider exceptions are
replaced with a generic error without retaining their response or exception chain.

Routes use CSRF for starts/mutations, account-session middleware for management
and rate limits for starts, callbacks, linking and unlinking. Callback responses
use no-store and no-referrer. Callback privacy middleware strips query secrets
before Laravel saves the previous URL, including throttled responses. Exception
input flashing excludes OAuth code/state/token/secret fields. Audit context contains
only account/version references, never provider tokens, email or subject.

The scheduler prunes callback records whose expiry is older than 24 hours, daily
with overlap protection. This operational retention does not extend validity.
No new queue or external financial/gamification effect is introduced.

## Configuration and deployment

Google remains disabled by default. Configure privately, per environment:

```dotenv
GOOGLE_AUTH_ENABLED=false
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
```

Create a Google OAuth Web application with the exact callback URI registered.
The URI must be `${APP_URL origin}/auth/google/callback`, without query, fragment
or credentials. Kody rejects a different origin or path. Production requires HTTPS;
HTTP loopback is allowed only for local/testing environments. Do not paste secrets
into chat or commit `.env`. Existing signed-in browser sessions are not evidence
that the Google project, consent settings or application credentials are ready.

Before enabling for users, configure consent/testing access, verify a live local
or staging link/sign-in/unlink cycle, denied consent, an unlinked account and
existing-session replacement. Test fresh sign-in and confirm Kody status/lockouts
still apply. Credentials alone do not prove live readiness. Keep production
disabled until this verification is complete. CI uses fake provider responses and
never calls Google. Rebuild Laravel configuration, routes/views and frontend assets
after deployment; run safe migrations and keep the scheduler active.

Configure reverse proxy/access logs, tracing and APM to redact the OAuth callback
query. Application middleware does not retroactively sanitize upstream logs; that
deployment configuration remains to be verified. Production Secure/HttpOnly cookie
and intentional SameSite configuration still apply. Never log OAuth secrets,
authorization codes, bearer tokens, state, session IDs or complete provider bodies.

## Verification

Automated coverage exercises explicit linking across all five roles, changed local
email isolation, unknown identities/email matching, PKCE, token exchange/userinfo,
strict verified-email checks, malformed responses, cancellation, provider failures,
callback replay/expiry/session mismatch, competing flows, stale profile/security
state, unique ownership, unlinking, audit rollback, account deletion, session
conflicts, disabled configuration, account status/lockouts, constraints, throttling,
CSRF and pruning. Independent PostgreSQL processes test callback consumption,
identity ownership and simultaneous sign-ins.

Verification passed: focused authentication/deletion checks 159 tests / 1,268
assertions; full PostgreSQL regression 882 tests / 6,518 assertions; cached
configuration/routes/views checks 442 tests / 3,503 assertions. All 30 migrations
execute on clean test databases. Pint, Composer validation/platform requirements,
20 frontend tests, production Vite build, workflow YAML parsing and diff whitespace
checks passed. Dependency installation reported no security advisories. The
temporary cached test environment explicitly uses the suite's bcrypt fixture
driver; production retains its configured secure hashing default.

Live OAuth, upstream callback-log redaction, remote CI and deployment remain
unverified. No production credentials or provider calls were used in tests.

## Sources and next step

Primary integration references: [Google OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect)
and the installed [Laravel Socialite source](https://github.com/laravel/socialite/tree/v5.31.0).

Next: privately configure the Google OAuth Web application and run the documented
staging/local round trips before activation. If the owner defers Google setup,
record that alongside the existing deferred integrations and continue an
independent requirement. Google-only registration needs an explicit profile,
password/recovery and registration policy before implementation. Purchases,
numeric ratings, rewards and live Judge0 retain their existing deferrals.
