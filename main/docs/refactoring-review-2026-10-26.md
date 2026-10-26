# Refactoring review — 26 October 2026

The product changes are submitted in [PR #34](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/pull/34).
This follow-up starts the codebase refactoring requested by the owner. It is an
incremental review of all seven modules and their shared UI/tooling, with a first
implementation focused on repeated credential handling and unnecessary reads.
It does not claim that every file has been exhaustively audited.

## Implemented first pass

| Area / traceability | Before | Result |
| --- | --- | --- |
| Account / A09, A10; Administration badge review | Three download handlers and badge grants independently checked private paths/disks. An undefined disk could produce a storage configuration exception. | Account-owned `CredentialDocuments` shares availability checks and policy-authorized, audited downloads. Undefined/public disks, wrong namespaces, traversal, backslashes and missing objects fail closed. History stays scoped to the requested Instructor application. |
| Engagement / B01 creator dashboards | Instructor reports queried challenge attempts three times without displaying those values. Contributor headline metrics queried submissions four times. | Instructors make no submission queries. Contributor headline metrics use one PostgreSQL filtered aggregate, retaining distinct participants, all confirmed attempts and terminal-only pass rates. Including the two daily charts, submission queries fall from six to three. |
| Content / B01, B04 lesson discovery and history | Each card loaded the complete immutable revision, including content, attachments and assessment. An empty history still executed four queries. | Cards select summary fields plus the assessment template label; the full assessment remains in its authorized lesson/assessment workflow. Empty history returns without database reads. Publication filters, eager creator badges, pagination and saved-edition semantics remain intact. |

The shared document service owns storage validation and stream/audit details.
Routes retain current-session middleware and resource policies; the service also
authorizes before resolving a history row or storage object. Badge decisions keep
their existing current-password/session, locked version and approved-proof rules.
Upload validation and private erasure remain separate lifecycle operations.
Storage outages still surface as operational failures; this change does not hide
them as missing documents. No files, roles, rewards or financial rows are rewritten.

## Codebase review and next batches

These are concrete follow-ups, separate from the [activation deferrals](deferred-features.md).
They do not require live provider tokens.

| Surface inspected | Finding / next work | Required guard |
| --- | --- | --- |
| Account: role applications, credential lifecycle, profile and erasure services | Credential reads are consolidated in this pass. Continue reviewing duplicated upload cleanup only where failure behavior is equivalent. | Preserve generated private names, retained submissions, durable erasure and sanitized failures. Do not merge upload, download and erasure into one generic file manager. |
| Content: module/course publication, discovery and saved history | Catalog summary projections and empty-history reads are improved. Public creator lists still eager-load full published revisions to display titles; narrow those projections next. Publishing methods have different composition/dependency rules; review shared validation before extracting a framework. | Preserve approved pointers, immutable editions, archived-course access and withdrawn-module denial. |
| Challenge: publishing, submission storage and evaluation | Standard and weekly pages repeat public sample/attempt queries, but use different publication/pinned-event admission. Consider sharing only the read projection after authorization. Existing submission transaction/error handling and provider adapters already supply reusable boundaries. Measure before changing evaluation orchestration. | Preserve attempt locks, weekly pinning, hidden-case exclusion, idempotency and provider-fake tests. |
| Gamification: Tower authoring/expansion, progression, weekly events | `TowerExpansion` is large primarily because it holds curriculum data; size alone is not a reason to rewrite it. Future separation should move data only after verifying all reference solutions. | Preserve historical floors/revisions, exact track progression, explicit Run and reward boundaries. |
| Engagement: dashboards, creator charts and application chrome | Repeated submission aggregates are improved. Establish representative per-page query counts before adding caches or changing request snapshots. | No cross-user or stale authorization caching; keep Manila dates and empty/older-edition semantics. |
| Transaction: ledger, refunds, settlements and provider boundaries | Large services contain explicit locking and provenance logic. Profile report reads separately from mutations rather than mechanically splitting transaction bodies. | Preserve lock order, exact money, FIFO provenance, atomic audit/outbox work and fake-provider coverage. |
| Administration: reports, exports, reviews and operations | `ReportFinancials` has repeated cash-window sums that are a candidate for a later filtered aggregate. Keep it in a finance/reporting change with reconciliation tests. | Paid cash, estimates, current liabilities and event dates must stay distinct. |
| Shared JavaScript/CSS and browser tooling | Quiz validation/rendering already share `quiz-types.js`; the Tower and normal editors have different state/DOM contracts. Large game modules need measured bundle/runtime evidence before extraction. | Keep browser/server replay parity, version 1/2/3 compatibility, keyboard/focus handling and bounded pseudocode. |
| CI and engineering checks | Add the new credential regression matrix to cached-route checks. Incremental static analysis remains the next tooling proposal from the improvement review. | Choose Laravel/PHP-compatible tooling, start with a meaningful scoped check, and avoid mass suppressions. No package is added in this pass. |

Work in small reviewable branches, preserving a stable parent PR. For each next
batch, record the concrete duplication/measurement, changed behavior, regression
evidence and deployment impact here or in a linked focused record. A whole-tree
rename or generic repository/service layer is not needed to start this work.

## Verification and deployment

New regressions exercise current Instructor, Contributor and historical Instructor
downloads across unsafe storage references; stream names/bytes/headers; audit
scope; service authorization; unavailable badge proof; distinct returning
participants; out-of-window attempts; submission scan budgets; empty-history reads;
and card projections. Existing ownership, session, publication and progress tests
remain part of the broader suite. CI includes the credential matrix in its cached
configuration/routes pass.

Local checks use PHP 8.4.27 and isolated loopback PostgreSQL 18. Results are
separate runs, not additive coverage totals:

- Complete backend: **1,328 tests passed**, **10,367 assertions**.
- Cached configuration/routes: **101 tests passed**, **682 assertions**, including
  current/history credentials, badge proof, profile privacy, creator aggregates,
  discovery and saved progress. Isolated cache files were cleared afterward.
- Frontend: **64 tests passed** with the explicit PHP fixture runtime.
- Affected Chromium workflows: **3 passed** with patched Vite assets, exercising
  creator publication/course access, mobile empty catalogs and badge grant/revoke.
- Clean locked npm install, zero-vulnerability dependency audit, normal/Office
  production builds, Pint and changed-document relative links passed.

The first broader run passed 1,327 tests and caught one incomplete new fixture:
it simulated terminal verdicts without clearing the active-attempt marker before
the returning participant's next attempt. The fixture now preserves that invariant;
the corrected cached checks above pass. No application admission rule was weakened.
The final complete backend run above passes with the corrected fixture. Logs and
affected browser reports are retained in ignored private storage; local checks do
not establish remote CI or production readiness.

No schema, package, environment variable or provider activation changes are
required by this refactor layer. The patched Vite lock is inherited from PR #34;
both bundles and browser checks above use Vite 8.3.5. Deploy this
branch only after the additive migration from PR #34, which it builds upon.
Live SendGrid, Google, Judge0, Xendit and AWS/staging work remains in the existing
integration and deferred-feature records. Local fake-provider results cannot
establish those live contracts or production capacity.
