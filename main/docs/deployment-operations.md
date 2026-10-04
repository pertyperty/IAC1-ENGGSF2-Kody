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

The [Supervisor example](../ops/supervisor/kody-worker.conf.example) runs the
current database/default queue as an unprivileged service user. Review executable,
release/storage paths, ownership and log retention before installing. The example
uses a 60-second worker timeout, below the current 90-second queue `retry_after`;
jobs have their own shorter bounded timeouts. Keep `retry_after` above all job
timeouts when changing either setting. One worker is a starting operational
configuration, not evidence of capacity. Increase concurrency only after measuring.

After each switch, run `php artisan queue:restart`; Supervisor must start the
replacement worker against the new release. Confirm the worker PID changes and
processes a non-sensitive sandbox job. Check `queue:failed`, durable delivery state
and queue age. Diagnose failed jobs before explicitly retrying them. Current jobs
use the default queue; additional queues need matching workers if introduced.

## Scheduler

Run one scheduler entry as the application service user on the initial single EC2
host, once per minute:

```cron
* * * * * cd /var/www/kody/current && /usr/bin/php artisan schedule:run >> /var/www/kody/shared/storage/logs/scheduler.log 2>&1
```

Configure log rotation and alerts for scheduler failure. `schedule:list` must show
submission expiry and weekly sync every minute, erasure retry every five minutes,
and OAuth attempt pruning daily. The registered tasks use `withoutOverlapping`;
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
secure recovery of application keys. Resolve and record the SRS backup cadence,
retention and recovery targets before release; this document supplies no new
business values. Protect logical dumps as sensitive data, even when used only for
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
