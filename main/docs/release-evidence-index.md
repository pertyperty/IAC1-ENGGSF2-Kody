# Release evidence index

Copy this record per candidate release. Evidence needs an environment, immutable
revision, date, reviewer and artifact/reference. A configured flag or an unchecked
box is not completed evidence. Never place tokens, credentials, private proof,
raw learner submissions or sensitive database dumps in this document.

The [17 November consolidation checks](consolidation-verification-2026-11-17.md)
record local development evidence and its exact source revisions. They do not
fill the unassigned release candidate or staged sign-offs below.

## Candidate

| Field | Value |
| --- | --- |
| Release boundary | Proposed free learning pilot; not full SRS completion |
| Environment / URL | Not yet designated |
| Immutable Git revision | Not yet designated |
| Evidence date | Not yet reviewed for a release |
| Release reviewer | Unassigned |
| Operations primary / backup | Unassigned / unassigned |
| Curriculum owner / backup | Unassigned / unassigned |
| Moderation/privacy owner / backup | Unassigned / unassigned |
| Dependency maintenance owner / backup | Unassigned / unassigned |

## Required evidence

Record revision/environment/date/reviewer in each reference when it differs from
the candidate above. Local test runs in implementation records are development
evidence, not an attestation that these staged checks passed.

| Check | State | Required artifact / reviewer evidence |
| --- | --- | --- |
| Composer/npm audits, exact lock install, backend/cached tests and bundles | Awaiting candidate | CI run and artifact digest tied to the candidate revision; advisory disposition |
| Ingress / HTTPS / sessions | Missing staged evidence | Approved host/proxy topology, blocked direct origin, spoofed-header/link checks, secure cookies |
| All worker queues and scheduler | Missing staged evidence | Both supervised consumers, per-queue probes, real application jobs, restart and failure/retry drill |
| Mail | Missing live evidence | Authenticated sender/domain; verification, recovery and failure/uncertain delivery with real inboxes |
| Private storage and erasure | Missing staged evidence | Authorized current/history documents, guest/cross-user denial, encryption/permissions and exact-version erasure |
| Reviewed beginner path | Missing editorial sign-off | Named curriculum reviewer, approved pinned revisions, correct/incorrect checks and valid continuation |
| Human learning pilot | Not performed | Voluntary task observation/interviews, confusion and concept-transfer findings, correction log; no mastery claim |
| Terms / Privacy and acceptance | Blocked by reviewed facts/text | Operator/postal/eligibility/geography/retention decisions, immutable published versions, server-owned acceptance and existing-user policy |
| First production Administrator and lost-admin recovery | Procedure not reviewed | Named authorized operator, identity verification, least privilege, audit, tested recovery and backup responder; local provisioning command is not production bootstrap |
| Alerts and incident ownership | Missing staged evidence | Tested alert delivery, sanitized diagnostics, named primary/backup and response runbook |
| Restore and application rollback | Missing staged evidence | Isolated managed restore, key/private-file recovery, integrity checks, measured RPO/RTO and compatible release switch |
| Accessibility / target devices | Partial local automation only | Real-device and screen-reader sessions plus automated workflow evidence at candidate revision |
| SRS load, latency and availability | Unmeasured in target environment | Relevant capacity/latency tests and observation period; a small pilot does not prove 500 users or 99.9% availability |

## Separate activation gates

Standard Judge0 execution, weekly competitions, payments/payouts/refunds and optional
Google identity retain their own verified contracts, sandbox/failure drills and
operational sign-off in the [deferred register](deferred-features.md). A free pilot
does not activate them or remove approved SRS obligations. Balances, financial
history, refunds/support routes and retained legal/financial data remain accessible
under their existing policies.
