# Financial report read consolidation

The owner requested continued consolidation on 18 November 2026. This focused
Administration/G07 change reduces repeated reads in `ReportFinancials::snapshot`.
Transaction services continue to own every mutation. The service remains inside
`SystemReports`' authorized, independent PostgreSQL REPEATABLE READ, READ ONLY
transaction. The report/filter/export contract is unchanged.

## Measurement and implementation

A disposable PostgreSQL fixture contains 200 historical postings and 205 postings
inside a one-day Manila window. Query logging starts immediately before the
projection call, excluding fixture setup and HTTP/controller work. The pre-change
service at baseline `e4e7d33ba3a9fe3c09f2ebe7f5ef619364e9b565` used 18 queries: four cash sums (opening,
inflows, outflows, expenses), current position reads, bounded registers and costs.
The reconciliation cases passed; the new query-budget check correctly failed at
18 total / four cash-sum queries before implementation.

The three sums over the same selected window now share one PostgreSQL filtered
aggregate. The opening balance keeps its separate historical query. Empty and
one-sided sums coalesce to zero. Every amount retains signed integer centavos;
positive expense reversals retain the same net-expense classification. No new
query combines paid cash with estimates, requested payments or current liabilities.

The new query-budget regression caps this projection at 16 total queries and two
cash-sum queries. Full matching register counts and the latest 200 detail rows
remain available. Further reductions are allowed by the budget check. These are
query-count measurements; device latency and production capacity remain unmeasured.

## Regression boundaries

New cases cover inclusive start/exclusive end at Manila midnight, historical
opening cash, values beyond signed 32-bit range, positive and negative postings,
expense reversals, empty/inflow-only/outflow-only/zero windows, private reference
redaction and complete totals beyond the detail limit. Existing reporting tests
retain independent read-only snapshots, concurrent-read consistency, exact money,
current-session/Administrator authorization, private exports and cleanup.

`ReportFinancials` joins the existing baseline-free level-5 static-analysis scope,
bringing it to 24 PHP files. One redundant null-safe read under null coalescing is
simplified without changing its zero fallback. No financial write, policy, schema,
provider or asset change is introduced. Normal PHP deployment is sufficient;
there is no migration or new environment key.

## Verification and open gates

Using PHP 8.4.27 and a disposable loopback PostgreSQL 18 cluster on 18 November:

- The 50-test reporting/finance subset passed normally (391 assertions) and with
  isolated cached configuration/routes (394 assertions). These are separate runs,
  not additive coverage totals. The final six new projection cases also passed
  independently (55 assertions), including the query budget and bounded details.
- Both existing Administrator browser workflows passed: account/report exports and
  deposited cash/paid expense reconciliation. They exercised PDF/CSV/Excel,
  desktop and 390/320px surfaces with accessibility/overflow checks.
- Baseline-free static analysis passed all 24 scoped files with PostgreSQL
  unavailable. Full Pint, strict Composer validation and the locked PHP dependency
  audit passed; all 98 changed-document relative targets resolve.
- Temporary testing route/configuration caches were cleared. No build inputs or
  packages changed, so frontend unit/build/restore suites were not repeated in
  this local batch. The complete required CI suite remains part of PR validation.

The measurements establish two fewer database round trips for this projection;
there is no production timing or capacity claim. The existing
[picomatch advisory gate](static-analysis-2026-11-18.md#npm-advisory-gate) was rechecked:
registry latest remains affected 4.0.7, and npm audit still reports six high entries.
No suppression, downgrade or npm dependency change is part of this refactor.
Full remote CI remains required and currently encounters that upstream gate.

Related records: [reporting contract](system-reports-implementation.md),
[refactoring review](refactoring-review-2026-10-26.md),
[deferred work](deferred-features.md) and [release evidence](release-evidence-index.md).
