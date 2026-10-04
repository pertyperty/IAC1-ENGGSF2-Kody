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
| Sustainability analysis | Separate pilot analysis records provisional PHP packages, fees, costs, margin and cap/tie questions. | Current provider sources cited; AWS regional quote and finalized business rules remain required. |
| Previously deferred full-scope features | Provisional financial inputs recorded; live providers remain deferred. | Final financial/reward/rank and creator-deletion retention rules before implementation/activation. |

No guessed prices, reward amounts, ranking formulas, credentials or production
deployment are authorized by this roadmap. Requested decisions are tracked while
independent implementation continues.
