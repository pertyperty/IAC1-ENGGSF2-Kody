# G07 business reporting implementation

Last updated: 2026-10-13. This is the current reporting record, consolidating the
initial aggregate slice and subsequent PDF/Excel and financial reporting work.

## Scope and ownership

Administration and Governance owns read-only management reports at
`/manage/reports`. Transaction and Earnings remains the only owner of cash,
wallets, expenses, purchase proof and settlements. Reports read its retained
records; they never credit funds, retry providers, approve requests or grant XP.

Only verified Active Administrators with a current session can generate or
download reports. The service rechecks the actor/session/policy inside one
independent PostgreSQL `REPEATABLE READ, READ ONLY` transaction. Counts, trends,
registers and financial position share that snapshot. Concurrent changes appear
on a subsequent report. Reports refuse nesting inside a writing transaction.

## Dates and interpretation

Calendar dates use Asia/Manila. Start is inclusive; end is midnight after the
selected final day, exclusive. The latest 30 days is the initial window; valid
filters persist in the authenticated session. Invalid filters cannot replace
them. Windows are bounded to 366 calendar days, including leap years.

Today, This month, This year and Last year shortcuts apply explicit dates.
Daily/monthly/yearly grouping is independently selectable. Empty buckets show
zero retained records; partial first/last buckets use only selected dates.
Registrations use Laravel's original UTC timestamp-without-time-zone columns,
explicitly interpreted as UTC before Manila conversion. Domain activity and
financial columns are timezone-aware and are converted directly.

The completion chart shows the latest 24 periods; the accessible table and
exports contain all selected periods. Charts and overflowing tables support
keyboard scrolling. Counts use retained data, not reconstructed historical
states; deleted private learning records cannot be recovered from these reports.

| Report scope | Selection and meaning |
| --- | --- |
| Accounts | Registration date; separate current status/role breakdowns of the same accounts. |
| Content | Creation date; current module/course/challenge lifecycle, including withdrawn content. Replacement revisions are not new items. |
| Validated learning | Activity completion, enrollment and course assignment completion dates. Valid replays and module reuse can count separately. |
| Coding attempts | Submission date; current Queued/Evaluating/Passed/Failed/Unavailable outcome. Source and hidden cases are excluded. |
| Accounting | Wallet operation/platform cash posting dates, exact KB and PHP centavo metrics; extended financial position and registers below. |
| XP/rewards | Award/posting/publication dates; actual persisted grants, not browser practice or projected rewards. |
| Engagement/Help | Retained current reactions updated in the window, Help entries created and first article completions. Removed reactions are not historical events. |
| Governance | Submitted application versions and recorded enforcement/role/content moderation actions. |
| Operations | Sent verification/financial mail and recorded failed jobs, not provider delivery proof or infrastructure monitoring. |
| Overview | Combined scopes above. |

Every scope includes period trends for registrations, assessed completions,
article completions, coding attempts, XP grants, paid expense centavos and net
cash centavos. Executive indicators explain account growth against the preceding
equal-length window, distinct assessed participants, terminal coding pass rate
and the current backlog of attempts submitted in the window.

## Financial position and registers

Overview and Accounting include:

- opening posted platform cash before the window;
- positive cash inflows and absolute cash outflows posted in the window;
- paid operating expenses, classified by the existing audited
  `operating-expense:` reference, **already included in outflows**;
- net movement and closing cash, reconciling
  `opening + inflows - outflows = closing`;
- current reserved platform cash, wallet backing and unclaimed **Cash** creator
  earnings, labeled snapshot liabilities separate from period cash movement;
- cash and wallet posting registers and payment/payout/refund request registers;
- latest monthly operator cost estimates for months intersecting the window.

Paid expenses require the existing password-confirmed, evidence-bearing,
cash-locked Finance action. Reports do not fetch AWS or other provider bills.
Monthly estimates can include committed future costs; they must never be added
to paid expenses or treated as a separate ledger debit. A zero recorded expense
total does not establish zero actual operating cost. Contributor KodeBit earnings
are not PHP cash creator liabilities.

Request registers select **creation date and current state**, not settlement date.
Requested gross/fee values are labeled accordingly. They are not income statements,
audited financial statements, tax reports or bank reconciliation. Ledger records
remain append-only; later reversals retain their own posting dates.

Each register extracts the latest 200 matching records in deterministic
`created_at, id` order and discloses the complete matching count. Totals and
trends use **all** matching records. Narrow dates for further detail. Cost estimates
list the intersecting months in chronological order. Registers include internal
record IDs, typed state/classification, times and exact monetary/token values.
Names, email, provider identifiers/payloads, checkout URLs, recipient details,
invoice evidence, code and hidden tests are excluded.

## Dedicated export layouts

Exports are deliberately separate from the website layout:

- **PDF:** A4 management report with branded executive summary, a financial
  statement section when applicable, operating metrics, period analysis,
  populated financial appendices, repeating table headings, confidentiality
  footer and page numbering. PDF appendices display the latest 40 records per
  register, explicitly labeled; Excel/CSV include up to 200. Empty appendices
  do not create empty pages.
- **Excel:** separate Executive summary, Recorded metrics, Activity trends,
  Financial position and register/estimate worksheets; merged title/period/scope
  rows, wrapped text, alternating row fills, dark headings, fixed widths, frozen
  headings, filters and A4 landscape print settings with page footer.
- **CSV:** UTF-8 BOM and labeled sections, reporting period, units, observations,
  trends and financial registers. Spreadsheet control prefixes are escaped.

Money remains integer centavos in calculations. Exact PHP display strings avoid
floating-point financial conversions; Excel's centavo columns remain integers
for downstream analysis. Explicit StringCells never become formulas. OpenSpout's
header/footer control ampersands are XML-escaped because it writes those elements
verbatim. Tests parse **every generated XML part**, beyond checking ZIP signatures.

Responses are private/no-store with download MIME/disposition and nosniff headers.
The route retains its ten-request-per-minute budget. No provider calls or financial
writes occur during report generation.

## Export failures and dependency setup

The reported `Dompdf\\Options` failure came from stale Composer dependencies in
the owner's main checkout. `composer install --no-interaction --prefer-dist`
installed the **committed lock**, including Dompdf and OpenSpout; no package update,
secret change or database operation was required. Every checkout needs its own
Composer install after pulling a release. An npm build only installs/builds the
frontend. `kody:setup-status` checks document package availability without
contacting providers. Unavailable PDF/Excel packages produce an actionable 503
instead of a missing-class exception.

Excel exports no longer call `tempnam(sys_get_temp_dir())`. Each request creates
its own UUID directory under private `storage/app/private/report-exports/`.
OpenSpout uses that same directory for internal temporary files. A finally block
removes owned temporary files after success or failure, independently of Windows
TEMP/TMP configuration. PDF font/temp cache remains inside writable
`storage/framework/cache/pdf/`; remote fetches, PHP and JavaScript are disabled.
Only the fixed report and receipt view allowlist can be rendered.

Deployments must install the lock with the approved PHP/extensions and ensure
the application user can write private storage and framework caches. Live
SendGrid, Judge0, Google and Xendit verification remains deferred.

## Related role/workspace corrections

- Finance cash history uses signed presentation, preventing exceptions when
  rendering negative expense/reversal entries. Provider amount validation still
  rejects negative input; only the presentation path accepts signed values.
- Finance cash-creator liabilities exclude Contributor KodeBit settlements.
- Finance has a direct reporting action and grouped, responsive funding/expense/
  budget tools with the existing password/evidence/confirmation safeguards.
- Independent finance, wallet, earnings and account-governance history paginators
  retain other page selections instead of resetting them on every next-page link.
- Shared report trends/financial partials keep accessible bounded table surfaces
  and responsive layouts consistent.
- The removable showcase Help seed now describes current DOCX/PPTX page previews,
  adjustable split viewing, fullscreen and original downloads. Seed reruns still
  preserve existing Administrator-edited Help entries.

No schema migration, role promotion, demo account reset or live provider activation
is part of this change.

## Verification

Local focused verification passed 66 PostgreSQL tests / 523 assertions (11 existing
Pest risky warnings), 59 frontend tests, Pint and both Vite builds. All five role
workspace browser checks passed; the two report/export/expense workflows passed
after the final export repair. Poppler page review verified the five-page expense
management PDF; imported workbook renders verified summary, trends and financial
position. Final complete/cached/backend/browser/restore CI evidence is recorded
in the pull request.
Tests cover snapshot isolation and concurrent registration, report RBAC/current
sessions, Manila inclusive boundaries, day/month/year grouping and empty buckets,
expense reconciliation, distinction between request state and cash proof,
bounded extracts, private-field exclusion, signed display, temporary cleanup,
all export formats and Excel XML/printing/freeze/filter structure.

Isolated browser workflows cover the five policy-filtered workspaces in both
themes and desktop/mobile layouts, account browsing, all three export formats,
and password-confirmed funding -> paid expense -> reconciled report -> PDF.
Synthetic demonstration data never touches the owner's database. PDF pages and
workbook summary/trend/financial sheets receive visual review.
