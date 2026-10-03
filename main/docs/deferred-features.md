# Deferred-feature register

This register keeps explicitly deferred scope visible for later development.
Deferral does not mean implementation or live configuration is complete. Continue
independent work; do not repeatedly ask for deferred provider credentials.

| Feature | Current behavior | What is needed to resume |
| --- | --- | --- |
| Judge0 live execution — C03/C04/B07 | Provider boundary, preflight and queued evaluation exist; live execution stays disabled. Owner deferred API-plan/key setup. | Configure credentials privately, discover/verify Python/Java/C++ compiler IDs and limits, run sandbox success/failure drills and measure feedback timing. Selected endpoint: `https://judge0-ce.p.rapidapi.com`. |
| Paid KodeBit purchases — F01 | Owner explicitly deferred purchases on 2026-10-03. No purchase storefront or browser-based credits. | Owner-approved currency, exact prices and KodeBit quantities; ledger, verified Xendit integration, idempotent callbacks, receipts and sandbox configuration. Do not infer a conversion rate from SRS feasibility examples. |
| Paid course enrollment — B03/F02 | Currently authored courses enroll free. | Server-owned pricing, approved spending/access rules and an atomic non-negative KodeBit ledger. |
| Paid challenge participation and prerequisite/rank gates — B07/F02 | Current authored challenges and weekly events are free for approved participant roles. | Authoring settings and approved gates, prerequisite/rank implementation and atomic financial access grants. |
| XP, ranks and monetary challenge/weekly rewards — E01–E06 | Server-validated game/quiz streaks and sequential game clearance exist; weekly events provide separate attempts and verified results without rewards. | Approved reward/rank formulas, qualifying actions, ownership and financial ledger/idempotency. Coding-event passes do not currently grant daily streak progress. |
| Account deletion with authored content — Delete Account (source labels A10) | Owner approved deletion only when no modules, courses or challenges have been authored. Creator accounts may archive instead. | Approved retention/removal and attribution rules for published and draft content, pinned revisions, learner access and free-text personal data. Do not silently delete shared learning material. See [deletion scope](account-deletion-plan.md). |

Purchases were deferred in direct response to the package-pricing question.
The existing free learning/game experience should stay usable while monetization
is unavailable. These entries preserve the approved decisions; they do not
authorize guessed fees, rewards, balances or production provider activation.

Relevant implementation records: [submissions](challenge-submission-implementation.md),
[weekly events](weekly-challenge-plan.md), [course learning](course-learning-implementation.md),
[game progression](play-implementation.md).
