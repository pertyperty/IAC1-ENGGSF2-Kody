# Kody

Gamified Programming Learning Platform and Course Management System. This is a
Laravel modular monolith; business use cases will be implemented incrementally.
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

The scheduler currently has no Kody tasks. `routes/console.php` is its future
entrypoint. `composer dev` retains the existing Laravel development command.
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
`/dashboard` currently provides an account welcome page; full B01 learning
dashboard data is a subsequent feature. All future protected routes must use
both `auth` and `account.session`, and sensitive actions must recheck state
transactionally. See [login traceability](docs/login-implementation.md).

Use `HASH_DRIVER=argon2id` outside disposable tests; verify `password_algos()`
includes `argon2id` in the deployment PHP runtime. Laravel's default Argon2
settings are retained; benchmark on deployment hardware before claiming NFRs.
Existing recognized bcrypt/Argon hashes can sign in and upgrade on successful
password login. The additive migrations do not rewrite existing passwords.

## Production preparation

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
