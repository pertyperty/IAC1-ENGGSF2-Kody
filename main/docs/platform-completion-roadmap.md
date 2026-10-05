# Platform completion roadmap

The owner requested implementation of the completion roadmap on 2026-10-04.
This working record tracks outcomes rather than treating an entire platform as
complete after a single rendered page. Detailed records remain linked from the
[implementation map](implementation-status.md).

| Workstream | Current state | Acceptance evidence needed |
| --- | --- | --- |
| Expanded creator quizzes | Implemented: 2–6 choices, 1–10 questions, versioned snapshots and server validation. | Authoring, review, presets, negative and frontend tests; browser reorder/preview/save verified. |
| Consolidated dashboards | Implemented: owned courses/progress/visits/quests/updates and creator summaries. | User isolation, availability filtering and bounded-query tests. |
| Longer learning paths and reading completion | Owner-approved and implemented; existing enrollment access is preserved. | Migrations, transactional unlocks, idempotent audited reading and exclusion from streak/Contributor credit. |
| Launch learning material | Two editable original course plans with eleven lesson slots, twelve lesson starters and three coding problem sets in all three approved languages. | Unsaved selectors/draft tests and deterministic problem outputs verified. Actual publication requires creator/staff review workflow; no automatic production publication. |
| Browser and performance verification | Local quiz authoring, sequential reading/assessment, dashboard themes, keyboard bypass and 320/390/768/1280px checks verified; repeatable scenarios recorded. Dashboard query growth is bounded by regression tests. | Actual Safari/Firefox/devices and staging load/latency require available environments; local observations do not establish SRS capacity. |
| Deployment and mail verification | Owner explicitly keeps live setup deferred; TLS SendGrid transport, sanitized setup command and guide implemented. | Actual HTTPS/mail/supervision/storage/alerts/managed restore await staging. |
| Sustainability analysis | Separate pilot analysis; delegated business policies now adopted. | Actual merchant fees/minimums, regional quote, usage and financial viability before activation. |
| Economy / achievements / creator erasure | Implemented under the [delegated amendment](economy-and-launch-decisions.md): backed ledger, paid access, settlements/refunds/reconciliation, XP/ranks, capped immutable weekly results and reviewed creator retention. | [Implementation evidence](economy-implementation.md); live providers and staging remain deferred. |

The later explicit owner delegation authorizes the documented recommended business
choices. It does not authorize live provider activation or deployment. The
[deferred register](deferred-features.md) owns remaining setup and verification.

## Acceptance follow-up — 2026-10-05

The [master audit](requirements-acceptance-audit.md) maps all original use cases
and NFR evidence. Reviewed challenge category/tag discovery is implemented; quiz
XP copy and 320px ladder overflow are corrected. Four isolated browser journeys,
axe checks and populated upgrade/restore rehearsal are repeatable locally and in
CI. [The launch curriculum pack](launch-curriculum-review.md) provides publication
checks. Terms content/version remains a real owner-content gap; private providers,
managed staging and actual devices retain their explicit deferral.
