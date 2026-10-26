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
| Engagement / B01 creator dashboards | Instructor reports queried challenge attempts three times without displaying those values. Contributor headline metrics scanned submissions four times. | Instructors make no submission queries. Contributor headline metrics use one PostgreSQL filtered aggregate, retaining distinct participants, all confirmed attempts and terminal-only pass rates. Including the two daily charts, submission scans fall from six to three. |
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
| Content: module/course publication, discovery and saved history | Summary projections and empty-history reads are improved. Publishing methods resemble one another but have different course composition and dependency rules. Review small shared validation operations before extracting a publication framework. | Preserve approved pointers, immutable editions, archived-course access and withdrawn-module denial. |
| Challenge: publishing, submission storage and evaluation | Existing submission transaction/error handling and provider adapters already supply reusable boundaries. Add measured query/latency evidence before changing queue/evaluation orchestration. | Preserve attempt locks, hidden-case exclusion, idempotency and provider-fake tests. |
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

Validation results are recorded below after the final runs. No schema, package,
environment variable or provider activation changes are required. Deploy this
branch only after the additive migration from PR #34, which it builds upon.
Live SendGrid, Google, Judge0, Xendit and AWS/staging work remains in the existing
integration and deferred-feature records. Local fake-provider results cannot
establish those live contracts or production capacity.
