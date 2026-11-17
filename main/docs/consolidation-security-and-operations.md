# Consolidation: dependencies, queue coverage and ingress

Owner-requested continuation, 17 November 2026, implementing the first two batches
from the [system review](system-scope-review-2026-11-13.md). The modular monolith,
authoritative progress, immutable revisions and ledger policies remain in place.

## Dependency maintenance and applicability

Fresh locked/installed Composer audits reported **11 advisories across eight
packages**, one more advisory/package than the 13 November report. A compatible
targeted update, with minimal transitive changes and no ignored advisories, updates:

| Package | Previous | Updated | Applicability review |
| --- | --- | --- | --- |
| laravel/framework | 13.34.0 | 13.42.0 | Concurrency process-output injection advisory. No `Concurrency` API use was found in application code; the framework is patched rather than exempted. |
| phpseclib/phpseclib | 4.0.1 | 4.0.3 | Unbounded key-parser iteration counts. No application SSH/PFX/CMS key-upload workflow was found; this remains a runtime transitive dependency. |
| symfony/filesystem | 8.1.6 | 8.1.11 | Scheme/prefix path handling. Private generated-key checks remain necessary; framework/tooling path operations still justify updating. |
| symfony/http-foundation | 8.1.8 | 8.1.11 | Four request/proxy/path/host advisories. Kody handles requests and generates account links; explicit ingress/host configuration below is an additional safeguard. Symfony Security firewall rules and `private_ranges` proxy shortcuts are not used by Kody. |
| symfony/http-kernel | 8.1.8 | 8.1.11 | Inline-fragment forwarded-header handling. No application inline-fragment rendering was found; patched without claiming a demonstrated application exploit. |
| symfony/mime | 8.1.7 | 8.1.10 | MIME parameter-name/header injection. Kody sends account/financial mail using structured templates, not uploaded arbitrary header definitions. Updating protects the runtime mail boundary. |
| symfony/routing | 8.1.8 | 8.1.11 | Compiled host-placeholder matching. Laravel route/policy checks remain authoritative; no application host-placeholder route was found. |
| symfony/yaml | 8.1.8 | 8.1.11 | Parser exhaustion; development dependency. No public YAML-import endpoint was found. |

Laravel's compatible prompts dependency moves from 0.3.24 to 0.3.27. No new package
or major framework version is introduced. The audit IDs/affected ranges and raw
local checks are retained in ignored private verification logs. This is source
applicability triage, not a demonstration that every advisory was exploitable.
Nginx/origin path controls and production mail behavior still need staged evidence.

## Queue coverage — C03/C04/E03 and operations

The Supervisor example and Docker Compose now run a separate `code-execution`
worker alongside `payments,notifications,default`. Account verification, recovery
and private erasure use the configured database default queue; finance also uses
the named queues. If `DB_QUEUE` changes, replace `default` in consumer definitions
and repeat the coverage check. Deployment must restart **both** workers.

The minute heartbeat sends one correlated probe per distinct application queue.
Success is recorded only by consumption on that queue. A nullable UUID added to
`operations_heartbeats` prevents timestamp collisions and stale/duplicate jobs from
acknowledging another probe. Each pending queue keeps at most one new probe;
stopped consumers do not accumulate minute-by-minute work. Retained old serialized
default probes are compatible and cannot acknowledge a newer correlated probe.

`php artisan kody:queue-check` reports each queue's Unknown/Recent/Stale processing
probe and returns failure if any required queue lacks a success within three
minutes. Administrator site management shows the same queue-specific evidence
alongside stored depth/age. A Recent probe establishes consumption only: real
provider success, throughput and latency remain separate checks. Failed probes
require investigation and explicit retry of their retained failed job; no blind
queue clearing or infinite redispatch loop is introduced.

Worker timeout remains 60 seconds, below `retry_after=90`; evaluation jobs retain
their shorter timeout, leases, releases and expiry policy. Synthetic tests use
the real Laravel queue runner and HTTP fakes to reach a terminal evaluation. They
never enable a live Judge0 account or weaken disabled-provider admission checks.

## Explicit ingress trust

`TRUSTED_PROXIES` is a comma-separated list of explicit IPv4/IPv6 addresses or
nonzero CIDRs. Empty trusts no forwarded headers. Wildcards, `REMOTE_ADDR`,
`private_ranges`, invalid addresses and `/0` ranges are rejected. Configuration is
read through Laravel config and works with cached configuration.

Only forwarded client IP, protocol and port are accepted from a configured proxy.
Forwarded host/prefix are ignored: ingress must preserve the approved original
Host and sanitize forwarded headers. `APP_ALLOWED_HOSTS` takes exact hostnames,
without scheme/port; unset or empty defaults to the host of `APP_URL`. Production
uses Laravel's host validation without implicit subdomain trust. Laravel bypasses
that host middleware in local/unit-test environments; production-mode regression
requests explicitly exercise rejection. Proxy/IP restrictions remain active in
all environments.

Before staging, select the actual proxy addresses/header behavior, restrict direct
origin access, configure HTTPS `APP_URL` and the exact allowed hostnames, then
rebuild config caches. Do not copy example network ranges or use the entire
Internet as a trusted range. Direct spoofed forwarded headers, approved proxy
traffic, unapproved Host and generated login-link origins are regression tested.
Infrastructure enforcement remains an operator task, not a code-only guarantee.

## Rollout and evidence

Run the additive `2026_11_17_000001_correlate_queue_processing_probes` migration
before serving this code or starting its scheduler/workers. It adds one nullable
column and rewrites no existing accounts, progress, application documents or
financial rows. The local development database is not migrated by automated
verification; tests use a separate disposable cluster. Keep private backups and
review application compatibility before rollback; do not automatically revert
database migrations.

Install dependencies from the lock, build normal/Office assets, rebuild caches,
deploy both worker definitions, run the scheduler, and verify each probe. This
prepares operations; it does not establish a public release, legal acceptance,
production Administrator recovery or live provider readiness. Use the
[release evidence index](release-evidence-index.md) to record those separately.

Focused development checks: 75 backend tests / 410 assertions passed for ingress,
real queue processing, same-second/stale probe handling, learning support, health,
site administration and challenge admissions. Frontend tests: 64 passed. Locked
Composer audit and npm audit report no known advisories after updating. Broader
cached/backend/browser/build and restore evidence is recorded in the
[consolidation validation](consolidation-verification-2026-11-17.md); these focused
results alone are not release acceptance.
