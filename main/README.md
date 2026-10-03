# Kody

Game-first coding platform with learning modules, creator content and monetization
layered over playable experiences. See the approved
[product amendment](docs/game-first-product-direction.md). This is a Laravel
modular monolith; business use cases are implemented incrementally.
Read `AGENTS.md` and [architecture conventions](docs/architecture.md) first.

## Development setup

Commands below run from the Laravel directory (`main/` in this repository).

Requirements: Composer 2; PHP 8.4.1+ for the locked runtime and test dependencies
(application declaration remains PHP ^8.3); Node 22.12+ with npm; PostgreSQL 17
(CI's baseline). Enable PHP ctype, curl, dom, fileinfo, filter, intl, mbstring,
openssl, PDO/pdo_pgsql, tokenizer, xml and zip. Use `composer check-platform-reqs`
to verify the actual lock file. Do not downgrade dependencies to suit old XAMPP.
XAMPP may supply compatible local PHP/Apache; PostgreSQL runs separately, and
Apache must serve only `public/`. No application path depends on XAMPP.

Create separate local PostgreSQL login roles and owned databases `kody` and
`kody_test`. Give the test role access only to the disposable test database;
never configure tests with a production login. Supply local passwords privately.

```sh
composer install
cp .env.example .env
cp .env.testing.example .env.testing
```

PowerShell uses `Copy-Item` instead of `cp`. Edit DB host/port/login/password in
each local environment file. `.env.testing` uses the dedicated test role.

```sh
php artisan key:generate
php artisan key:generate --env=testing
php artisan migrate
php artisan migrate --env=testing
npm ci
npm run build
php artisan about
php artisan migrate:status
```

Run `php artisan serve` and `npm run dev` in separate terminals. Run the database
worker in another terminal:

```sh
php artisan queue:work database --sleep=3 --tries=3 --timeout=60
php artisan schedule:work
```

The scheduler closes/activates weekly events and recovers overdue submissions
through `routes/console.php`. `composer dev` retains the existing Laravel development command.
Boost is already locked as a development dependency. Browser URL logging is
disabled to protect verification fragments. Its MCP server can be run
with `php artisan boost:mcp`; inspect installation options before configuring
an editor, and preserve the project's authoritative `AGENTS.md`.

## Tests and quality checks

```sh
composer validate --strict
composer check-platform-reqs
composer lint
composer test
composer audit
npm audit --audit-level=high
npm run test:games
npm run build
```

`composer format` applies Pint formatting. There is no configured static analyzer.
Tests cover health, PostgreSQL persistence/constraints/concurrency, registration,
verification, login lockouts, session replacement, real database browser sessions,
rollback, CSRF and authorization/mass-assignment boundaries.
The full suite requires PostgreSQL. `phpunit.xml` forces `testing`, `pgsql`, an
empty DB URL and database `kody_test`; `.env.testing` supplies host/login/port.
RefreshDatabase may reset the test schema: use only a disposable database.
Run `php artisan config:clear` before changing environments or running tests.
The frontend's existing Bunny font plugin downloads fonts at build time, so the
build requires outbound HTTPS. The build then serves fonts locally.

GitHub Actions runs from repository-root `.github/workflows/ci.yml`, with commands
in `main/`: PHP 8.4, Node 22, isolated PostgreSQL 17, lock-file installs, audits,
Pint, frontend build, clean migrations, Pest tests and configuration/route cache
checks. A frontend artifact carries the verified Git SHA; it is not a complete
production release. PRs and main pushes trigger CI; deployment is separate.

## Health and environment conventions

`GET /up` returns `200 {"status":"ok"}` after application boot, without querying
the database. `GET /ready` runs `SELECT 1`; success is 200, database failure is
`503 {"status":"unavailable"}`. Both use fixed JSON with no-store caching and
no web/session middleware. Failure logging contains only a fixed message.
Maintenance mode returns Laravel's 503 for both probes. Readiness checks database
connectivity, not mail, external providers, worker health or scheduler execution.

Keep local, testing, staging and production configuration separate. Never commit
environment files or real credentials; only sanitized examples are tracked.
Use `config()` inside application code and `env()` only in configuration files.
UTC is canonical. Database queues dispatch after commit by default; test queues are sync.
Account verification persists its database job inside the account transaction.
The base seeder creates no accounts or production credentials.

## Account registration milestone

`/register` implements A01 registration and pending Instructor applications;
`/email/verify` implements A02 single-use activation and limited resends.
Configure `ACCOUNT_VERIFICATION_MAILER=smtp` with sandbox SMTP credentials and
run `php artisan queue:work database --tries=3 --timeout=60` for delivery.
Do not use a log mailer for verification tokens. Credentials use the private
local disk by default; preserve its storage across release switches.

See [account implementation and traceability](docs/account-implementation-plan.md)
for approved requirement decisions, migration preflight, token protection,
concurrency coverage and remaining recovery/approval/provider scope.

`/login` implements A03 email/password login with durable progressive lockouts,
consent before replacing an active session, and POST logout. The protected
`/dashboard` provides the play hub, saved streak/ladder state and a link to enrolled
course journeys; full B01 dashboard scope remains incomplete. Protected routes must use
both `auth` and `account.session`, and sensitive actions must recheck state
transactionally. See [login traceability](docs/login-implementation.md).

Use `HASH_DRIVER=argon2id` outside disposable tests; verify `password_algos()`
includes `argon2id` in the deployment PHP runtime. Laravel's default Argon2
settings are retained; benchmark on deployment hardware before claiming NFRs.
Existing recognized bcrypt/Argon hashes can sign in and upgrade on successful
password login. The additive migrations do not rewrite existing passwords.

## Production preparation

Approved Instructors can build adventures in `/create`, attach configurable games
or quizzes and submit drafts. Moderator/Admin review at `/manage/modules` controls
publication and replacements. Learning shows approved revisions; verified wins
qualify the Manila daily streak. Creators receive reviews at `/updates` and can
archive published adventures. See [creator studio scope and verification](docs/creator-studio-implementation.md).

The studio also supports `/create/courses`: versioned course drafts, ordered reuse
of owned approved adventures, saved previews and course review at `/manage/courses`.
See [course composition scope](docs/course-composition-implementation.md).
Learners can browse `/learn/courses`, explicitly join free courses and resume
their pinned lessons in `/learn/courses/mine`. Verified game/quiz wins save
assessment progress and share the daily streak. Owners can archive courses;
existing enrollees retain access while new enrollment stops. Paid enrollment is
unavailable. See [course learning scope](docs/course-learning-implementation.md).

Contributors and Instructors can author coding quests in `/create/challenges`,
with versioned problems, execution settings and sample/hidden tests. Moderator/Admin
review at `/manage/challenges` controls publication and replacements; owners can
archive published quests. `/challenges` exposes approved metadata and authenticated
sample previews. Confirmed free attempts now pin the approved revision, enforce
three lifetime attempts and queue private evaluation. Execution stays unavailable
until configured Judge0 compiler IDs and limits pass `php artisan kody:judge0-check`.
See [submission setup and scope](docs/challenge-submission-implementation.md) and
[challenge studio scope](docs/challenge-studio-implementation.md).

The Play hub now presents weekly quests with their own three-attempt budgets.
Moderators configure approved, revision-pinned events at `/manage/weekly-events`;
the Laravel scheduler activates the Sunday-to-Sunday Manila cycle and selects an
approved quest when no manual selection exists. Private history lives at `/weekly`.
Judge0 setup and all XP/rank/KodeBit rewards remain deferred. See
[weekly event scope](docs/weekly-challenge-plan.md).

All five roles can edit their own profile at `/account/edit`. Email/password
changes require the current password and end sessions; changed email requires
verification again. Learners and Contributors can apply to become creators and
resubmit after rejection, with private credentials and prior decisions preserved.
See [A06 scope and verification](docs/profile-editing-implementation.md).
Paid purchases and other postponed scope are tracked in the
[deferred-feature register](docs/deferred-features.md).
Learners, Contributors and Instructors can archive their own accounts with
password confirmation, retaining data and returning through A04 recovery.
See [A07 archival scope](docs/account-archival-implementation.md).
Participant accounts without authored modules, courses or challenges can request
permanent deletion at `/account/delete`, using password and explicit confirmation.
Active evaluations must finish first; Archived users reactivate through A04 first.
Deletion removes private learning/submission records and revokes sessions. Private
credential files use encrypted, retryable cleanup records. Keep database workers
and the scheduler running; `php artisan kody:account-erasures-retry` requeues
unfinished cleanup without duplicating pending jobs. See
[deletion scope and operational limits](docs/account-deletion-plan.md).
Learners can apply for Contributor access at `/account/contributor-application`
after 30 days, 25 distinct validated module completions and 50 distinct passed
coding challenges. Moderator/Admin review at `/manage/contributors` controls
elevation. Rejected applications retain history; only one Pending role application
is allowed at a time. Supporting files stay private and notices use database jobs.
See [A09/G05 scope](docs/contributor-application-plan.md).
Moderators and Administrators can browse account status and enforcement history
at `/manage/accounts`. Confirmed suspension immediately revokes sessions;
reinstatement requires a new login. The approved hierarchy protects self/Admin
targets, and committed coding evaluations continue. See
[G01/G03/G04 scope](docs/account-governance-implementation.md).
Administrators can also appoint verified Active participant accounts as Moderators
and remove them to their recorded prior role. Confirmation requires the current
Administrator password, revokes target sessions and records audit/notification
history. Self/Admin targets and legacy Moderators with unknown prior roles remain
protected. See [G02 role scope](docs/moderator-appointments-implementation.md).
Administrators can correct username and first/last name for eligible accounts
when the user requests help, with password confirmation, audit, session revocation
and a queued notice. See [G02 support scope](docs/support-profile-corrections-implementation.md).
Administrators can view read-only account, content, validated learning and coding
attempt totals at `/manage/reports`, with retained date filters and consistent
PostgreSQL snapshots. Financial/reward reporting remains unavailable. See
[G07 scope and count definitions](docs/system-reports-implementation.md).
Moderators/Administrators can withdraw and restore Published/Archived content at
`/manage/content`. Staff withdrawal blocks enrolled learners too, preserving
content, progress and committed evaluations. Creator edits cannot lift the block.
See [G06 scope and rollback safeguards](docs/content-withdrawal-implementation.md).
Administrators manage reusable game/quiz defaults at `/manage/game-presets`.
Module assessments pin immutable preset versions; updates and inactivation retain
existing play. Rewards stay Deferred. See
[preset workshop scope](docs/game-presets-implementation.md).

The target is Linux EC2, Nginx/PHP-FPM with PHP 8.4.1+, RDS PostgreSQL and S3 where required.
Set `APP_ENV=production`, `APP_DEBUG=false`, a stable managed `APP_KEY`, HTTPS
`APP_URL`, `SESSION_SECURE_COOKIE=true`, HttpOnly cookies and SameSite=lax.
Secure cookies default to true in staging/production. Use RDS
`DB_SSLMODE=verify-full` and `DB_SSLROOTCERT` pointing to the current AWS CA bundle.
`DB_CONNECT_TIMEOUT` bounds connection establishment; it is not a query deadline.
Use managed secrets and least-privilege service/deployment identities. The S3
driver package and provider integrations must be added/tested when needed.

Supervise the worker with systemd/Supervisor under the application user, from the
active release, using the worker command above and automatic restart. Its 60s
timeout stays below database `retry_after=90`; allow at least 90s for graceful
shutdown and review these values when real jobs arrive. Monitor failed jobs and
queue latency. Run `php artisan queue:restart` after deployment.

Use one production cron entry under the application user:

```cron
* * * * * cd /var/www/kody/current && php artisan schedule:run >> /var/log/kody-scheduler.log 2>&1
```

Rotate/monitor that log; define overlap/single-server locks for future tasks.
Expose only `public/`, enforce HTTPS and secure headers/request limits, and keep
private storage outside disposable releases. Deploy immutable releases with safe
`migrate --force`, cached configuration/routes/views and `/up`/`/ready` smoke
checks. Roll back application releases independently of database migrations.
Monitor HTTP, queues, database and scheduled work; configure encrypted backups
at least every 24h per SRS 4.2 and test restoration before a production release.
AWS deployment, TLS/encryption, backup restoration and availability targets
have not been provisioned or verified by this foundation task.
