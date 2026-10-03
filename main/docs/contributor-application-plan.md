# Contributor applications — A09 / G05 plan

Account Management owns Learner application admission; Administration and
Governance owns Moderator/Administrator decisions. A09's G03 cross-reference is
inconsistent with the named G05 Approve Contributor Requests workflow. Use G05
for review; G03 is account suspension.

## Pending requirements decisions

A09 requires an account at least 30 days old and “25 modules / 50 challenges.”
DDT-Contributor-Request-Information independently requires at least 25 completed
modules and 50 completed challenges. Asked the owner whether both thresholds
apply, with distinct server-validated module completions and passed challenges;
repeated revisions/weekly plays must not inflate eligibility. No eligibility
writer is implemented until this decision is approved.

The same dictionary declares request_message and moderator_feedback as
varchar(255), while their domain rules permit 500 characters. Asked the owner
whether matching 500-character columns and validation should resolve this.

## Proposed implementation prompt

After the owner resolves these conflicts, inspect existing creator applications,
private credential storage, notification delivery, session locks and audit
boundaries. Implement verified Active Learner admission with server-computed
eligibility, optional portfolio URL, private validated supporting credentials
(A09 requires an upload), and one Pending application per user enforced by
PostgreSQL. Do not fetch portfolio URLs on the server.

Provide authenticated Moderator/Administrator review, fresh actor/applicant
checks, locked versioned decisions, atomic Contributor elevation on approval,
unchanged role on rejection and queued notifications for submission/outcomes.
Each submitted application is decided once. Preserve prior decisions; do not
silently invent resubmission or overlapping Instructor-application behavior.
Integrate new private application data with deletion and durable file cleanup.

Test threshold boundaries, duplicate completions, unsupported roles/statuses,
cross-user access, stale/competing submissions and decisions, upload and delivery
failures, transaction rollback and account deletion. Run clean PostgreSQL
migrations, full/cached tests, Pint and frontend checks. No schema or live
provider change has been made for this planned scope.
