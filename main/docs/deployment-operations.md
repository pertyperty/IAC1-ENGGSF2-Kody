# Deployment operations and rehearsal evidence

This is preparation for the AGENTS.md production model, not a deployed release.
The local drills below used a disposable PostgreSQL database and synthetic data.
No cloud resources, production accounts or provider credentials were used.

## Release and workers

Use Linux, PHP 8.4.1+ within PHP 8.x, PostgreSQL, HTTPS and a web server exposing
only `public/`. Build an immutable release from passing CI. Attach private config
and shared storage, run `migrate --force`, build config/route/view caches, then
switch `current` atomically. Keep the application key stable and backed up securely:
verification delivery and other encrypted data depend on it. Never run
`migrate:fresh` or seed test credentials on the release database.

For the 2026-10-10 tower release, run
`php artisan db:seed --class=TowerLevelSeeder --force` after migrations and before
the switch. It installs missing curriculum positions without overwriting edits
or creating accounts. Private lesson media joins credential storage: verify upload
limits, authorized revision-pinned streaming and the erasure worker using
[the tower/media record](tower-and-media-implementation.md).

The [Supervisor example](../ops/supervisor/kody-worker.conf.example) runs the
database payments/notifications/default queues as an unprivileged service user. Review executable,
release/storage paths, ownership and log retention before installing. The example
uses a 60-second worker timeout, below the current 90-second queue `retry_after`;
jobs have their own shorter bounded timeouts. Keep `retry_after` above all job
timeouts when changing either setting. One worker is a starting operational
configuration, not evidence of capacity. Increase concurrency only after measuring.

After each switch, run `php artisan queue:restart`; Supervisor must start the
replacement worker against the new release. Confirm the worker PID changes and
processes a non-sensitive sandbox job. Check `queue:failed`, durable delivery state
and queue age. Diagnose failed jobs before explicitly retrying them. Financial work uses payments and notifications in addition to default. A worker
that listens only to default will strand financial work; inspect every queue.

## Scheduler

Run one scheduler entry as the application service user on the initial single EC2
host, once per minute:

```cron
* * * * * cd /var/www/kody/current && /usr/bin/php artisan schedule:run >> /var/www/kody/shared/storage/logs/scheduler.log 2>&1
```

Configure log rotation and alerts for scheduler failure. `schedule:list` must show
submission expiry and weekly sync every minute, erasure retry every five minutes,
financial recovery every five minutes, and OAuth attempt pruning daily. The registered tasks use `withoutOverlapping`;
their cache backend must provide shared durable locks. Adding another scheduler
host requires reviewing distributed single-server scheduling first. Weekly events
retain the approved Asia/Manila boundaries; canonical timestamps remain UTC.

## Storage, health and monitoring

Instructor and Contributor credentials must use private storage. Keep shared local
private files outside the web root, or use an authorized private S3 disk with
least-privilege IAM and encryption. Verify signed credential access as authorized
staff and denial as guest/cross-user. Do not link private storage into `public/`.

Probe `/up` for application liveness and `/ready` for database readiness; neither
requires a browser session or exposes connection details. Readiness is not proof
that queues, mail, scheduler, S3 or external providers are healthy. Configure
monitoring for HTTP errors/latency, database pressure, queue depth/oldest job,
failed jobs, durable mail failures, scheduler success, disk/memory and enabled
provider failures. Centralize sanitized logs with alerts and a named responder.
Test an alert in staging before claiming monitoring is operational.

## Backup and restore

Use encrypted managed database backups plus private-object backups/versioning and
secure recovery of application keys. The [delegated launch policy](economy-and-launch-decisions.md) adopts daily
encrypted backups, 35-day RDS retention/PITR, 30-day noncurrent S3/log retention,
RPO15m/RTO4h and monthly isolated restoration. Verify these actual settings and
measured targets before release; policy adoption is not a deployed backup. Protect logical dumps as sensitive data, even when used only for
restore drills. Keep passwords out of command arguments and shell history.

Restore an RDS snapshot or logical archive into a **new isolated target**. Never
point a rehearsal at the live database. Confirm migration versions, roles/statuses,
published revision pointers, enrollments, progress, audits and representative
encrypted records/private files. Measure restore duration against approved recovery
targets. Exercise application login/lesson access against the restored staging
environment without sending production mail or activating payments/evaluation.

Application rollback switches to a previous compatible release and restarts
services; it does not automatically roll back migrations. The expanded preset
allowlist requires releases that understand any newer persisted game instances.
Database recovery is a separate assessed operation.

## Local evidence — 2026-10-04

- All 31 migrations ran on an empty disposable PostgreSQL database.
- Browser: Instructor starter → custom Terminal Quest preview → draft → staff
  approval → course composition → staff approval. Fresh Learner registration →
  email verification → saved garden win → free enrollment → custom assessment
  win → persisted Cleared course state.
- Database worker processed one synthetic verification delivery using the allowed
  testing-only array transport. A preceding non-test array attempt was correctly
  rejected; no production mail rule was relaxed. This is not SMTP delivery evidence.
- `schedule:list` exposed the four tasks; due expiry/weekly sync ran successfully.
- Private storage write/read succeeded; its public URL returned 403.
- PostgreSQL custom-format dump restored into a second empty database. Row
  fingerprints matched across users, module/course revisions, enrollments, course
  progress, audit events, learning progress and level completions. The application
  key was retained for the drill. This does not test RDS or S3 restoration.
- Eight local readiness requests returned 200, 439–1,256 ms (mean 635 ms) while
  tests were running. These are development-server probe observations, not page
  load, concurrent-user capacity, availability or production performance claims.

Linux supervision/restart, production HTTPS, AWS IAM/RDS/S3, managed encrypted
backups, private object restoration, centralized monitoring/alert drills and
approved recovery targets still need a provisioned staging environment. Track
that work in the [deferred register](deferred-features.md); do not deploy based on
these local checks alone.

### Completion follow-up

The completion pass ran all 33 migrations on a new isolated PostgreSQL database,
including quiz version-2 preset constraints and the optional course-path policy.
An older application release cannot safely read newly authored multi-question
quizzes or preserve sequential/reading semantics. Use a compatible release for
application rollback; migration guards refuse retained incompatible state.
See [completion evidence](platform-completion-implementation.md#verification)
and [later integration setup](integration-setup.md). The earlier local worker,
backup and scheduler drill remains local evidence; this follow-up does not
claim a new managed-cloud rehearsal.

## Economy and launch preparation follow-up

The [private storage template](../ops/aws/pilot-storage.yaml) prepares private,
encrypted Single-AZ RDS with 35-day backups, deletion protection and snapshot
retention; private versioned S3 with public blocking/TLS enforcement and 30-day
noncurrent retention; and 30-day RDS logs. It depends on a reviewed VPC, at least
two private subnets and the application security group. It creates no EC2/TLS,
billing alerts, app IAM identity or deploy pipeline. Validate it with CloudFormation
in the authorized region before provisioning; it has not been deployed/cloud-tested.
Select the supported PostgreSQL engine version before executing a change set.

Use a separate least-privilege application PostgreSQL role; the managed master
secret is for controlled migration/operations, never frontend use. Attach the
[scoped S3 policy example](../ops/aws/private-storage-policy.json.example) to the
EC2 role after replacing the bucket. Use role credentials rather than committing
AWS keys. Bucket owner enforcement is compatible with the configured owner-full-
control upload ACL and AES256 encryption. Verify credentials cannot be public,
other buckets cannot be reached and all exact-key versions can be erased. A delete
marker alone is not credential erasure. Backup retention may retain encrypted
historical personal data until expiry; document and restrict recovery access.

Review the [log rotation example](../ops/logrotate/kody.conf.example); configure
Laravel daily logs with 30-day retention and bounded Supervisor output. Configure
CloudWatch queue age/errors, worker/scheduler health, disk, RDS pressure, provider
Review rows and restore/alert ownership. Actual cloud telemetry/notifications are
still staging work. The monthly budget report is operator-maintained actual plus
committed estimates, not an automatic cloud billing sync. Missing/breached current
reports pause new paid purchases and coding admissions; committed evaluations,
refunds and owed payouts continue.

During release: run migrations on a backed-up target, inspect old free entitlements,
run `kody:ledger-check`, cache configs/routes/views, restart supervised workers and
run `kody:launch-check`. The economy migrations refuse rollback with financial
history; use an application release compatible with paid access and new rewards.
Do not roll back to a free-only reader after granting paid entitlements.

`kody:finance-recover` safely requeues stranded Pending/Queued work and quarantines
expired Creating/Sending operations. Do not blindly retry uncertain external POSTs
or SMTP acceptance. Administrator finance provides audited callback/receipt retry;
an authenticated saved callback is still rechecked against expected provider proof.
Unknown creation without a provider ID needs provider/support investigation before
release of reservations. Investigate all accounting mismatches; the ledger check
never overwrites balances. Application receipts do not replace statutory invoices.

Monthly restore drill: create a new isolated RDS target, disable external providers,
restore private objects and the stable application key under restricted access,
compare revision/enrollment/progress/audit and ledger conservation, decrypt a
synthetic recipient/credential reference, run ledger/config checks and local app
smokes without sending mail. Record backup age against 15 minutes and end-to-end
restore time against four hours. Separately erase a synthetic versioned credential
and verify no older version or marker survives. Never aim the drill at live data.

The economy follow-up applied all 40 migrations to a new isolated local PostgreSQL
database and obtained zero mismatches from the empty-ledger check. This verifies
clean-schema installation, not an upgrade of live financial data or managed recovery.

### Populated acceptance rehearsal — 2026-10-05

`npm run test:restore` repeats an isolated 40-to-41 migration upgrade with
creator/course/learner/XP/ledger/hidden-case/encrypted-delivery history, followed
by a matching-client custom-format restore. All 75 table counts/digests and
ledger/decryption checks passed locally. The guarded script is reusable in CI;
[the evidence record](acceptance-verification.md) distinguishes it from managed
RDS/S3/key recovery and the older pre-economy upgrade.
