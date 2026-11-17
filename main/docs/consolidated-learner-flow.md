# Consolidated learner flow

Owner-requested consolidation, 17 November 2026, following the
[system review](system-scope-review-2026-11-13.md). This presentation/maintenance
batch follows [security/operations](consolidation-security-and-operations.md).
It adds no floors, engines, currency, eligibility rule or provider activation.

## One primary next step — B01/B02/B05

The learner overview has one primary action: the first uncleared available level
in the selected Tower track. A small server-owned projection reads level IDs,
positions and owned clearances, not stage definitions. Track choice remains a
session preference; an unknown key falls back to Beginner. Completed tracks offer
the map/track picker; empty tracks explicitly report unavailability. Level GET/Run
endpoints still enforce current access, ordering and revision state.

The old three-level ladder is collapsed under **Optional starter lessons**. Its
routes, IDs, history, unlocks, assessment validation and XP remain intact. The
cosmetic mission board and unused service are removed, replacing competing
starter-led Continue prompts. No history is migrated or fabricated.

Navigation groups Play, Learn, My progress and Help/account. Courses stay in Learn;
owned courses, Tower review lists and weekly history sit with progress. The
header's duplicate Courses destination is removed. Staff/creator policies and
studio links remain available. Tower/streak rules appear next to Continue and
XP/ranks next to their saved totals. Disabled checkout no longer advertises
purchase packages; balances, purchase status/receipts, refunds and earnings remain
accessible. Course/challenge summaries use concrete learning terms.

## Authoring and smaller reads — A06, D01–D09

Long module, course, challenge, preset and Tower forms show a browser leave warning
when controls differ from their initial mounted state. Reverting avoids a warning;
ordinary valid Save submissions proceed. Empty file inputs, unnamed Tower controls,
dynamic controls and local-preview exclusions are handled. Editor validation that
blocks submission keeps protection active. This is a navigation safeguard, not
autosave or publication. Browser/mobile lifecycle support varies; creators still
save drafts explicitly.

Public creator lists select parent IDs/publication pointers and revision IDs/titles
only. Publication/approval/withdrawal filters, six-per-type limits, totals and
private identity boundaries stay in place. Obsolete Instructor-at-signup JavaScript
is removed; verified-account applications retain their existing server workflow.

## Verification and remaining work

Focused checks passed 80 backend tests / 1,061 assertions and two new Chromium
workflows, including mobile accessibility and cancelled/reverted authoring edits.
Complete stack verification is recorded in the
[development evidence](consolidation-verification-2026-11-17.md). A stale
dashboard-copy assertion was updated from adventures to lessons; no domain policy
was changed to satisfy it.

A named curriculum reviewer must select/sign off actual pinned beginner content;
a human pilot must observe task completion and concept transfer. Synthetic accounts
and reference solutions do not replace that work. Reviewed legal facts/text,
acceptance implementation and staged operations remain in the
[release index](release-evidence-index.md). The proposed free pilot is not full SRS
completion and does not activate providers.

The [navigation discovery batch](navigation-discovery-2026-11-18.md) adds optional
Tower list navigation and a filtered staff review inbox. Measured page-specific
JavaScript loading and financial-report query consolidation remain separate
measured batches. [Scoped static analysis](static-analysis-2026-11-18.md) now covers
selected authorization/operations/credential/inbox boundaries; wider coverage
remains incremental. No device/latency speedup is asserted.
The [current learning policy](current-learning-policy.md) consolidates superseding
rules so older milestone wording does not guide new work incorrectly.
