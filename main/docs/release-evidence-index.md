# Release evidence index

The [20 November learning consolidation](learning-consolidation-2026-11-20.md)
records the game startup fix, viewport workspaces, persisted Tower preference,
private qualifications requests, author links, preset usage, Library bookmarks,
live profile previews, combined Help editing and preserved sidebar scroll.
Final candidate checks are tracked there; provider activation stays paused.

The [19 November game workspace follow-up](game-workspace-rework-2026-11-19.md)
records same-tab legal navigation, a single bounded pseudocode editor, clearer
preview/run state and shared mobile game refinements. These are local interface
and regression checks; curriculum/difficulty review, human learning, actual devices
and production legal/operating evidence remain open. Provider work stays paused.

Copy this record per candidate release. Evidence needs an environment, immutable
revision, date, reviewer and artifact/reference. A configured flag or an unchecked
box is not completed evidence. Never place tokens, credentials, private proof,
raw learner submissions or sensitive database dumps in this document.

The [17 November consolidation checks](consolidation-verification-2026-11-17.md)
record local development evidence and its exact source revisions. They do not
fill the unassigned release candidate or staged sign-offs below.

The [18 November learner rework](learner-first-rework-2026-11-18.md) records local
UI, guided-assessment, registration and migration evidence, including the exact
source revisions and full-run/targeted-run limits. It does not supply the human
pilot or production sign-offs below.

The [19 November creator follow-up](creator-workflow-clarity-2026-11-19.md) records
local challenge/preset form, validation, keyboard and preview checks. These are
workflow evidence, not a curriculum, usability or release sign-off.

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
| Terms / Privacy and acceptance | Local registration implementation; production review outstanding | Owner-authorized dated text, UB correspondence address and atomic new-user acceptance are implemented in the [learner rework](learner-first-rework-2026-11-18.md). Review operator/eligibility/retention facts and existing-user material-change policy for the candidate. |
| First production Administrator and lost-admin recovery | Procedure not reviewed | Named authorized operator, identity verification, least privilege, audit, tested recovery and backup responder; local provisioning command is not production bootstrap |
| Alerts and incident ownership | Missing staged evidence | Tested alert delivery, sanitized diagnostics, named primary/backup and response runbook |
| Restore and application rollback | Missing staged evidence | Isolated managed restore, key/private-file recovery, integrity checks, measured RPO/RTO and compatible release switch |
| Accessibility / target devices | Partial local automation only | Real-device and screen-reader sessions plus automated workflow evidence at candidate revision |
| SRS load, latency and availability | Unmeasured in target environment | Relevant capacity/latency tests and observation period; a small pilot does not prove 500 users or 99.9% availability |

The [19 November npm recheck](static-analysis-2026-11-18.md#19-november-advisory-recheck)
records passing installed-package and lock-file audits after the upstream advisory
was withdrawn. No lock change or suppression was made. The initial failure is
retained as historical evidence. The dependency-security row still requires fresh
candidate CI; local checks and earlier CI do not sign off a release.

## Separate activation gates

Standard Judge0 execution, weekly competitions, payments/payouts/refunds and optional
Google identity retain their own verified contracts, sandbox/failure drills and
operational sign-off in the [deferred register](deferred-features.md). A free pilot
does not activate them or remove approved SRS obligations. Balances, financial
history, refunds/support routes and retained legal/financial data remain accessible
under their existing policies.
