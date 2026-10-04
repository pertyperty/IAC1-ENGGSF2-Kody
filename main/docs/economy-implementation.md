# Economy, achievements and privacy implementation

Authority: the owner delegated the remaining decisions on 2026-10-04. The
[policy amendment](economy-and-launch-decisions.md) owns those choices. This record
describes implementation and limits; [the implementation map](implementation-status.md)
is the master documentation entry point. Original SRS/SDD artifacts remain intact.

## Delivered behavior and traceability

| Scope | Implementation |
| --- | --- |
| F01 purchases | Three server-owned PHP packages; password/confirmation UUID; durable asynchronous GCash payment request; authenticated callback and matching backend retrieval before credit. Creation/redirect never credits. |
| F02, B03/B07 access | Reviewed immutable pricing, XP thresholds and owned-module prerequisites; metadata-only locked previews; one atomic FIFO debit, sale, earnings allocation and access grant. Course purchase covers its pinned lessons. Existing grants remain grandfathered; challenge attempts do not reset. |
| F03–F05 settlements | Private wallet/earnings/refund histories, 65/35 backed allocations, 14-day maturity, Contributor fractional carry and whole-KB claims; Instructor PHP500-minimum payout with encrypted GCash recipient, password/Administrator review and reservations. Verified failure releases; verified success deducts; verified reversal returns net funds once. |
| Refunds and remedies | Whole untouched original purchase within 14 days; unused access within seven days; locked usage recheck, staff review, durable reversal records and provider-confirmed fiat refund. Exceptional cases have an audited investigation/decision queue; resolving a case does not fabricate a financial outcome. |
| B09/E01, E03–E06 | Idempotent authoritative XP and separate achievement ranks; authenticated leaderboards; best verified weekly score, competition ties, immutable staff publication and whole-KB rewards constrained by prior purchased-token spending and matured platform cash. Existing Deferred weekly events stay Deferred. |
| Account erasure | Existing tombstone/erasure outbox plus creator retention consent, all-revision staff inspection, unchanged inventory hash and settlement checks. Shared material remains, free for future admissions. Optional separately confirmed balance relinquishment handles voluntarily surrendered balances and dust. |
| G07/G13 / operations | Administrator-only dated accounting/XP/reward aggregates, financial liabilities/cash history, attested owner funding and paid-expense entries; versioned monthly cost reports, once-per-threshold notices, automatic admission pause, secret-free launch/config checks and read-only ledger reconciliation. |

## Persistence and atomicity

Migrations 000031–000037 add wallet accounts/lots/immutable operations and entries,
platform cash entries, content sales/earnings, payment/payout/refund operations,
minimal authenticated provider proofs, financial notices, entitlements, XP awards,
weekly retained scores/results, budget reports, remedy/privacy reviews, token-origin
tracking and recovery metadata. Existing prices/gates default to free/none; existing
opens, enrollments and standard participations receive Legacy entitlements.

PostgreSQL checks protect nonnegative/reserved balances, exact sale conservation,
approved price/rank ranges, unique active sales, confirmation references and reward
awards. Triggers reject ledger/reversal changes and published-result changes or
late inserts. Submission source erasure can null only the result's source pointer;
pseudonymous verified scores remain. Financial rollback refuses retained history;
switch to a schema-compatible release rather than dropping accounting tables.

The pilot serializes financial mutations through one PostgreSQL row lock. Domain
services first lock the current account/content when needed, then the economy
lock and operation/lot/earning records. This favors auditable correctness for the
small pilot; it is not a throughput claim for the unchanged SRS concurrency target.
The debit, durable entries, sale, creator/platform allocation, entitlement, audit
and database-queue notice commit together. Queue connection must share the app DB.
No controller computes rewards, balances or splits. Prerequisite validation rejects
self-dependencies and transitive cycles; staff approval rechecks current availability
without changing the saved creator-settlement policy. Shared price/rank controls
remain available for garden, arcade, quiz, preset and lesson-only authoring.

All money uses integer centavos. Parsing preserves provider JSON decimal lexemes
without floating-point conversion; Payments/refunds send numeric major-unit PHP,
while Payouts v3 sends integer minor units. FIFO allocation preserves exhausted-lot
remainders and purchased-token provenance, including returned access lots. Reward
and creator-issued origin never enlarges the purchased-spend reward denominator.

## Provider boundary, failures and privacy

Xendit uses the current Payments/Payouts APIs through one bounded, sanitized adapter.
Callback merchant, reference, amount, currency, capture/recipient and expected
state must match the locked internal operation; purchases and payouts additionally
require backend retrieval. Refund completion uses the authenticated documented
refund event; no undocumented refund GET endpoint is invented. Provider POSTs do
not retry after an uncertain response. Only documented Payouts v3 idempotency keys
are sent. Repeated callbacks are harmless; conflicting proofs enter Review.

Creation starts with a committed Creating claim. A timeout/crash becomes Review
and retains reservations. `kody:finance-recover` recovers old Queued/Pending work
and moves expired Sending/Creating leases to Review. It never repeats uncertain
POSTs or email sends. Administrators can explicitly retry a saved authenticated
callback or uncertain receipt after investigation and password confirmation.
Unknown creation without an identified provider object requires provider/support
reconciliation; there is no button to invent success or release ambiguous funds.

Recipient details are encrypted at rest and excluded from queue payloads,
callback proof storage, logs and ordinary history. Staff recipient reads are
audited. Hidden challenge tests remain excluded from learner SQL/views. Private
credential erasure removes exact S3 object versions and markers, with bounded
pagination in both deletion and verification, failed-work retention and no
prefix-wide deletion. S3 requests have explicit five-second connect and
15-second response timeouts with two bounded retries. The official
Flysystem S3 adapter and lockfile are installed; production IAM/private storage
verification still requires staging. Accounting references/recipient records have
the adopted retention policy; no automatic tax-record purge is claimed.

Critical notices create private in-app updates and transactional email work.
Email failure cannot undo financial success. Uncertain SMTP acceptance requires
an explicitly audited retry because acceptance may have occurred before failure.
Application receipts explicitly distinguish themselves from tax invoices.
Deleted or unverified recipients receive no email; the delivery record is marked
Suppressed with no sent timestamp, preserving the distinction from accepted mail.

## Configuration and activation

Defaults keep Xendit disabled with no fee assumptions. The activation gate requires
verified contract fees, secrets/merchant, HTTPS, same-database queue, named primary
and backup responders, enforced budget and staging evidence. Empty/malformed fee
settings are missing values, never silently zero charges. `kody:setup-status` and
`kody:launch-check` print sanitized local checks without contacting providers.
`kody:ledger-check` checks lot/entry/account and platform-cash conservation without
repairing balances. See [integration setup](integration-setup.md) and
[operations preparation](deployment-operations.md).

Use workers listening on `payments,notifications,default`; the revised Supervisor
example preserves a worker timeout below queue retry_after. Actual failed-attempt
fees, monthly gateway minimums and cloud/mail invoices are operator-attested
platform operating expenses, separate from successful top-up lot allocation.
Budget reports include committed forecasts; they are not an AWS billing feed.

## Verification and boundaries

Focused PostgreSQL tests cover exact conservation/idempotency/rollback, paid previews,
course-covered assessment XP, provider proof validation, settlement reserves,
whole-purchase/access refunds, fractional claims, creator deletion, reward ties,
caps, immutable publication, reconciliation, budgets and explicit relinquishment.
Independent PHP processes prove overlapping wallet spends and payout reservations
wait on PostgreSQL locks and preserve one affordable operation. Provider tests use
synthetic credentials and HTTP fakes; no live API call or real money is involved.

Verification evidence follows below. Remote CI independently repeats the full
PostgreSQL suite and cached checks for the exact pull-request revision. Live Xendit, Google, Judge0, SendGrid, AWS/staging, tax
configuration, actual fees and managed recovery remain explicitly deferred. The
prepared AWS storage template has not been deployed or cloud-validated. These
boundaries cannot be converted into production proof by successful fake tests.


### Local verification — 2026-10-04

- Latest focused economy, weekly results, reports, readiness and private-erasure
  suite: 54 PostgreSQL tests, 325 assertions passed.
- Cached configuration/routes/views: 625 PostgreSQL tests, 4,741 assertions passed.
  Follow-up regressions additionally cover suppressed financial email and free
  admission/audit after creator deletion. CI repeats the expanded selection.
- Forty migrations applied successfully to a new isolated PostgreSQL database;
  the read-only ledger check reported zero wallet/lot/cash mismatches. The empty
  rehearsal database was removed after checking it; existing checkouts were untouched.
- All 44 frontend game tests, the Vite production build, strict Composer validation,
  full Pint formatting checks and PHP syntax checks passed. Dependency audits found
  no reported PHP or frontend vulnerabilities at verification time.
- Browser checks used a disposable PostgreSQL fixture and disabled providers:
  three visible PHP packages without enabled checkout, locked priced lesson preview,
  confirmed module/course debits, course-covered lessons without an extra debit,
  idempotent 40-XP assessment completion, saved streak and private financial reports.
  Shared authoring prices stayed visible for quiz and Terminal Quest scenarios.
- Dark/light switching and 320/390-pixel wallet, earnings, finance and report layouts
  had no horizontal overflow in local Chromium. This is not physical-device or
  Safari/Firefox evidence. A local synthetic-data finance screenshot is saved in
  ignored storage; no fixtures, user credentials or generated builds are committed.
- Review fixed staff preview completion routing/authorization and stale reward-text
  expectations. Regression tests assert staff practice has no completion endpoint,
  entitlement, saved activity or XP grant. Skipped email is Suppressed, never Sent.
- Documentation links and changed-file secret-pattern/encoding checks passed.
  AWS templates remain review artifacts pending authorized CloudFormation validation.
