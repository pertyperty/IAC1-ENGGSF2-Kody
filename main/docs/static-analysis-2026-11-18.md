# Incremental PHP static analysis

The 18 November owner request groups engineering improvements into focused PRs.
This batch adds an incremental type/control-flow check alongside formatting and
runtime tests. It follows the [navigation batch](navigation-discovery-2026-11-18.md)
and addresses the tooling proposal in the [refactoring review](refactoring-review-2026-10-26.md).

## Scope and operation

Run `composer analyse` from `main/`. PHPStan with Larastan uses the committed
`phpstan.neon` at level 5. Its initial paths cover enums, authorization policies,
operations admission/heartbeats, the worker probe, queue-check/heartbeat commands,
trusted-proxy handling, private qualification downloads and the new review inbox.
The Laravel extension enables model-property checking and parses model `casts()`
methods so enum/date comparisons reflect Laravel runtime types. Its result cache
stays under ignored `storage/framework/cache/phpstan`.

The 20 November learning consolidation adds the Bookmark, ReviewQueue, Help,
FAQ and CourseLearning controllers to the checked paths. Their dynamic published
revision projection retains selected title fields without loading full payloads.

The scope is deliberately explicit. There is no baseline, blanket ignore rule or
file exclusion hiding errors inside those paths. The rest of the application is
not claimed to pass static analysis. Add coherent services/controllers to `paths`
in later focused PRs, resolve their findings and retain behavioral regressions.
Increase strictness only when the next level's annotations and findings can be
reviewed accurately. Do not silence an issue by weakening runtime validation or
financial/identity/credential safeguards.

The development dependencies are Larastan and PHPStan, plus Larastan's SQL parser.
Production installs with `--no-dev` do not need the analyzer. Existing runtime
package records and existing development dependency versions remain unchanged.
The lock file defines the exact tools used in CI and local runs.

CI runs `composer analyse` after preparing the testing environment and formatting,
before migration and runtime tests. It has no failure bypass. The existing backend,
cached routes, browser, audit, build and restore checks remain required. Static
analysis is development evidence; it proves neither deployment readiness nor
teaching effectiveness.

## Verification

On 18 November 2026, PHP 8.4.27 / Laravel 13.42 with Larastan 3.15.0 and
PHPStan 2.3.6 passed all **23 scoped PHP files** at level 5. The initial model-cast
configuration produced false string/enum comparisons; enabling AST parsing of the
existing `casts()` methods resolved those without changing policy expressions or
weakening type checks. The three remaining findings identified redundant null-safe
version reads in `BudgetAdmission`. It now reads the current version once, retaining
missing-row version zero, exact validation, locking, audit and threshold notices.

Verification passed:

- Normal `composer analyse` and a fresh serial debug run with an unavailable
  PostgreSQL endpoint and external providers disabled. This check does not require
  a migrated/connected database, matching its position before migrations in CI.
- 68 affected backend tests / 426 assertions on disposable PostgreSQL, including
  credential access, queue/ingress controls, inbox behavior, budget initial/update/
  stale versions, durable alerts and financial reports. This final run includes
  the budget-read change; an earlier 54-test run preceded it.
- Full Pint, strict Composer validation, platform requirements, exact locked
  install dry run and both installed/locked Composer audits (no advisories).
- Comparison with the parent lock confirms every runtime package record and
  existing development dependency version is unchanged. Only Larastan, PHPStan
  and `iamcal/sql-parser` 0.7 were added as development packages.
- All 93 relative Markdown targets across changed records resolve.

The previous navigation layer's UI/browser/build evidence remains in its own
record. This tooling layer changes no assets, so those suites were not repeated
locally. Full CI remains required at the PR revision; no final remote result or
production-readiness claim is made here. No schema change or provider activation is
part of this batch. Remaining curriculum, legal, operations and measured-performance
work stays in the [deferred register](deferred-features.md) and
[release evidence index](release-evidence-index.md).


## Scope expansion

The [financial-report follow-up](financial-report-reads-2026-11-18.md) adds the
report projection to the same level-5 check, expanding the scope from 23 to 24
files without ignores. Its focused record owns the additional regression results.
The [challenge-read follow-up](challenge-problem-reads-2026-11-18.md) adds both
problem controllers and their weekly-event model to the level-5 scope, from 23
to 26 files. Relationship annotations retain actual revision/challenge types;
the focused record owns its additional regression evidence.
The 23-file results above describe the initial tooling revision.

The 22 November backend batch adds 14 services and controllers covering course
and module publishing, learning progress and history, challenge submissions,
wallet/refund/settlement workflows, account deletion and creator profiles.
Eloquent relationship return types identify their actual models, and revision
model annotations describe the access columns added by the shared economy
migration. No baseline or ignores were added. Refund creation requires its
linked purchase before calling the provider and retains the reconciliation path
when it is missing.

## npm advisory gate

The following records the initial 18 November failure. The 19 November recheck
below supersedes its current advisory disposition; historical CI results remain.

The first remote run on this tooling revision stopped at the existing npm audit
step, before static analysis. The local PHP checks above pass, but this PR's CI is
**blocked**, not fully verified. Earlier #41/#42 CI runs passed before the current
registry advisory result; they do not attest to a fresh audit today.

Fresh `npm audit` reports six high-severity affected dependency entries derived
from one root advisory: [GHSA-fc5q-39xc-vjpf](https://github.com/advisories/GHSA-fc5q-39xc-vjpf)
(CVE-2026-112366), repeated-extglob regular-expression denial of service in
`picomatch`. The advisory was updated on 17 November 2026 at 15:56:44 UTC and lists
all versions through 4.0.7, with no first patched version. Registry `latest` is
4.0.7; 4.0.8 is not published. The inherited lock contains 4.0.7 through Vite /
tinyglobby / fdir and 2.3.2 through vite-plugin-full-reload. This PR has not changed
the npm manifest or lock.

Application-source inspection found no direct picomatch/glob-pattern calls; the
dependency tree places it in build tooling. `npm audit --omit=dev` reports zero,
but that alone is not absence-of-risk evidence: browser build dependencies are
also declared as development packages. No exploitability or release waiver is
claimed. Builds should continue to use reviewed repository inputs.

The advisory's linked [upstream PR #194](https://github.com/micromatch/picomatch/pull/194)
is open and explicitly states that it does not generally fix the vulnerability;
its text still identifies an exponential single-branch pattern. Pinning it is not
a demonstrated fix. npm's automated suggestion downgrades Vite/Laravel tooling
across incompatible major versions, so it was not applied.

No audit threshold, suppression, dependency alias or unreviewed fork was added.
The next dependency-maintenance PR must use a verified upstream fix, update every
affected path, retain a clean lock install and rerun full audits/builds/browser CI.
Until then, this security gate remains open in the
[release evidence index](release-evidence-index.md).


The [learner-first rework](learner-first-rework-2026-11-18.md) adds registration
terms/action and the four assessment/game services to this scope: 32 files in
all. Exhaustive unsupported-template handling and a redundant loop check were
resolved directly, without ignores or a baseline. Its record owns verification
at that later revision.

## 19 November advisory recheck

Fresh `npm audit --json` and `npm audit --package-lock-only --json` both passed
with zero reported vulnerabilities on the unchanged dependency manifest and lock.
The [GitHub advisory](https://github.com/advisories/GHSA-fc5q-39xc-vjpf) now marks
GHSA-fc5q-39xc-vjpf withdrawn on 18 November 2026. Registry latest remains 4.0.7.
This is a changed advisory disposition, not a package patch, security waiver or
proof that every possible dependency defect is absent. No suppression, downgrade
or audit-threshold change was made.

The local npm advisory gate now passes. Candidate-specific CI and the other
release checks still require fresh evidence. Logs are retained under
`storage/app/private/page-assets-npm-*.json`; the
[page-loading record](page-specific-assets-2026-11-19.md) owns this follow-up's
source revision and other local checks.
