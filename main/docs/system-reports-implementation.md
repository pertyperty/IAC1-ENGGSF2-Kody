# G07 read-only system reports

> Later amendment (2026-10-04): the owner delegated economy, XP/rank/reward,
> reviewed paid access and creator-retention decisions. See [current policies](economy-and-launch-decisions.md)
> and [implementation](economy-implementation.md). Earlier first-release deferrals below
> are historical; live providers/staging and numeric ratings remain deferred.

## Requirement and scope

G07 permits Administrators to select report types and filters, view validated,
consistent aggregates and retain filters after interruption. Reports must not
change source data. Administration and Governance owns the report service, using
existing module-owned records without calculating rewards, balances or new access.

The first release at `/manage/reports` summarizes implemented accounts, content,
validated learning and durable coding attempts. Financial, XP, rank and reward
reports remain unavailable while their supporting systems and formulas are
owner-deferred. This is partial G07 reporting scope, not complete financial or
gamification reporting. No user-identifying drilldown, source export or provider
diagnostics are exposed.

## Definitions

All report windows include the selected Asia/Manila calendar days, translated to
UTC with an inclusive start and exclusive next-day end. The default is the latest
30 calendar days; windows are limited to 366 days as a query bound. Filters are
validated and retained in the authenticated server session, so reopening the page
preserves valid selections. Invalid filters cannot replace the last valid filters.

| Report | Date selection | Count definition |
| --- | --- | --- |
| Accounts | `users.created_at` | Registered accounts grouped by current status and current role. These are separate breakdowns of the same accounts, not historical role/status transitions. Minimal Deleted tombstones remain counted. |
| Content | Content `created_at` | Modules, courses and challenges created in the window, grouped by current lifecycle. Replacement revisions are not new content items. |
| Validated learning | Activity `completed_at`, enrollment `enrolled_at`, assignment `completed_at` | Stored validated game/quiz activity records, course enrollments and completed course assignments. Different-day replays and module reuse across assignments can count separately. No claim of distinct learners/modules or rewards. |
| Coding attempts | `submitted_at` | Durable submissions grouped by current outcome/status, including standard and weekly attempts. A pass completed later can change the current outcome of an earlier submitted attempt. |

The page explains current-snapshot semantics, generated time, record-based counts
and that lifecycle counts include staff-withdrawn items whose lifecycle is retained,
and the exclusion of erased private learning/submission records. It never labels
these as immutable historical totals, unique learning clearances or reward grants.

## Authorization, integrity and performance

Only verified Active Administrators with a current session can access reports.
Participant and Moderator requests are rejected server-side. The service freshly
checks the actor inside an independent PostgreSQL `REPEATABLE READ, READ ONLY`
transaction. All groups share one database snapshot, even if a registration commits
between aggregate queries. Writes are prohibited by PostgreSQL within the snapshot.
The service refuses nesting in a caller's write transaction. Ordinary authenticated
session/filter persistence happens outside the source-data report transaction.

Fixed report definitions choose table/column/group identifiers; user dates are
bound values. Queries aggregate in PostgreSQL and return a fixed bounded number
of rows, including zero counts. The route has a ten-request per-minute budget.
Responses use `no-store, private`, escaped HTML and no authorization-sensitive
report cache. Names, usernames, email, private credentials, source, hidden tests
and provider profiles are excluded. No new external call, audit write, job or
financial effect is triggered by viewing a report.

`2026_10_03_000021_add_report_window_indexes.php` adds date-window indexes across
the eight source tables. It changes no source records and can remove indexes
without deleting data. Standard transactional index creation can lock writes while
indexes build; assess database size and maintenance timing before deployment on
large tables. This release has no measured production query/NFR compliance claim.
No new package or secret is needed. Use safe migrations and existing configuration.

## Verification

PostgreSQL tests cover authentication/roles, stale/restricted Admins, Manila
boundaries, valid and invalid retained filters, current lifecycle/outcome totals,
private-data exclusion and validated-record-only learning totals. Independent
database connections prove a concurrent committed registration does not mix
snapshots and appears on the next report. Another test verifies read-only isolation
and a database write rejection. All 12 focused G07 tests passed with 76 assertions.
The full PostgreSQL suite passed 638 tests / 4,528 assertions. All twenty-four
migrations execute on clean test databases. Cached configuration/routes/views
passed 150 G01–G04/G07/A06 tests / 1,126 assertions. Pint, 16 frontend tests,
Vite build and diff checks passed. The existing optional font-fallback optimization
warning remains; it does not prevent the build.

Remote CI, browser visual checks, production performance and deployment remain
unverified. Judge0 tests use the existing provider fake; live execution stays deferred.

## Next implementation prompt

The owner approved G06 staff withdrawal and restoration, now implemented with a
separate block that covers existing enrollees and pinned revisions while preserving
committed evaluations. See [withdrawal scope](content-withdrawal-implementation.md).
The owner approved the first-release Deferred preset reward mode; see
[G08–G10 scope](game-presets-implementation.md). Retain owner-deferred purchases,
rewards and live Judge0 activation in the deferred-feature register.

## Delegated economy extension — 2026-10-04

Administrator reports now include exact posted wallet KB/backing/platform cash
and validated XP/funded weekly-prize aggregates. The same bounded Manila date
filters and PostgreSQL repeatable-read, read-only snapshot apply. Report labels
state their units (PHP centavos, KB, XP or record counts); posted accounting is
not gross receipts, tax reporting or provider-bank reconciliation. No payer,
recipient, submission source or raw reference appears in aggregate rows. Private
Finance remains the separate review/history surface. Tests exercise boundary
dates, authorization, exact units and privacy. See
[economy verification](economy-implementation.md).
