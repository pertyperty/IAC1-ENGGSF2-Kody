# Consolidation development verification

Date: 17 November 2026, Asia/Manila. Execution/review: Codex automated development
checks and source inspection; human release reviewer remains unassigned.

Application source is `d64c48d1c0eadfe60263f0a8dfe155e212734794`, including the
security/operations parent `1f1f2ad677b5bdfaae228a06a9b394e8ade494ca`.
Final browser-test corrections are at
`d414c99d923e90300266cff79da78afb19c6ff4f`; final backend-test corrections are at
`72b679e2a101a7563311f0dee0954cabed17eca2`. Later documentation-only commits do
not change this tested application. PRs: [security/operations #39](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/pull/39),
then [learner consolidation #40](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/pull/40).
The second PR depends on the first. No merge or deployment is part of these checks.

## Environment and results

Windows local development, PHP 8.4.27, Node 22.20.0 and PostgreSQL 18.6. Backend
tests use a disposable loopback PostgreSQL cluster and `kody_test`; browser and
restore harnesses create their own isolated random databases. Development and
production databases are excluded. Providers remain disabled or faked; account
mail uses the array transport. This is not evidence of live provider delivery.

| Check | Result |
| --- | --- |
| Focused security/operations backend | 75 passed / 410 assertions at the parent implementation |
| Focused learner/queue backend | 80 passed / 1,061 assertions at the combined application source |
| Broad backend suite | 1,345 of 1,347 passed; two obsolete dashboard expectations corrected afterward |
| Both corrected backend files | 41 passed / 341 assertions, including starter/weekly invariants |
| Cached configuration/routes regression subset | 139 passed / 1,029 assertions, including both corrected files; private caches cleared afterward |
| Frontend suite | 64 passed |
| Full Chromium journeys/accessibility | 69 passed together at the corrected browser-test revision |
| Corrected continuation, leave guard, creator publication and wallet journeys | 4 passed together |
| Cross-engine smoke | 6 passed across Chromium, Firefox and WebKit; pseudocode bounds and Help/account accessibility |
| Populated upgrade and restore | Retained-history fingerprints and integrity checks passed across 85 tables; restore 7,737 ms locally |
| Normal and Office bundles | Passed |
| Composer installed and lock-file audits / npm audit | No known advisories reported |
| Composer validation/platform / deterministic install check / Pint | Passed; install dry run reports no lock/vendor differences |
| Changed Markdown relative links | 242 targets across 11 documents passed; historical deleted-service reference pinned to its baseline commit |

Build-reported baseline: shared JavaScript 140.22 kB (42.94 kB gzip), application
CSS 201.46 kB (37.97 kB gzip), and the separate Office preview bundle 750.00 kB
(191.98 kB gzip). These are artifact sizes, not measured network transfer, device
execution cost or page latency. Page-specific splitting remains a measured future
batch; no runtime performance improvement is claimed here.

The first broad Chromium run passed 66 of 69 workflows. Its three failures came
from shared fixture sessions left active by the new tests and two older checks
still expecting unavailable purchase packages. The new tests now sign out through
the application's CSRF-protected logout route. Wallet expectations verify the
unavailable state and preserved history/support instead. Application session and
payment protections were not weakened to pass tests.

The broad backend run found two obsolete dashboard expectations in
`CreatorJourneyTest` and `WeeklyEventsTest`. Their updates verify the preserved
starter history/rewards independently of Tower clears and the valid secondary
weekly-challenge destination. Both complete files passed afterward. The application
source is unchanged from the broad run; it was not rerun locally in full after
these test-only corrections. Required CI must rerun the complete suite at the
proposed head; the broad and focused results are separate, overlapping runs.

Synthetic screenshots, test logs and reports are retained under ignored
`storage/app/private/consolidation-*` and `storage/app/browser-*` paths locally;
they are not public release artifacts. CI retains its own revision-tagged browser
artifacts. Local execution does not attest that hosted required checks passed.

## Rollout and open gates

Before serving this application, apply the additive queue-probe migration, install
the lock file, build assets, rebuild caches, restart both worker groups and verify
each configured queue. Set deployment-specific allowed hosts and proxy addresses;
verify origin restrictions and forwarded-header sanitization in staging.

The [release evidence index](release-evidence-index.md) still requires named
operations/curriculum/moderation owners, beginner-content sign-off, human learning
observations, reviewed legal facts/text and acceptance implementation, production
Administrator recovery, and staged mail/storage/alerts/restore/capacity evidence.
Live execution, competitions, finance and optional Google identity retain separate
activation gates. The [deferred register](deferred-features.md) distinguishes those
requirements from optional maintenance batches. This verification does not declare
production readiness or full SRS completion.
