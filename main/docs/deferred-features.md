# Deferred-feature register

This register keeps explicitly deferred scope visible for later development.
Deferral does not mean implementation or live configuration is complete. Continue
independent work; do not repeatedly ask for deferred provider credentials.

| Feature | Current behavior | What is needed to resume |
| --- | --- | --- |
| Production operations verification | Local empty-database migration, browser journeys, database-worker mail test, due scheduler tasks, private storage denial and PostgreSQL restore drill passed. A Linux worker example/runbook exists; nothing was deployed. | Provision staging to validate HTTPS, supervision/restart, AWS IAM/RDS/S3 encryption and restoration, alert delivery, approved backup/recovery targets and measured load. See [operations evidence](deployment-operations.md). |
| Google OAuth live setup — A03/A06 | Explicit password-confirmed linking/sign-in is implemented, but disabled. Owner deferred private OAuth setup after implementation. | Configure the OAuth Web application, exact callback and credentials privately; verify live link/sign-in/unlink and consent/session error paths before enabling. See [Google authentication](google-authentication-implementation.md). Do not repeatedly request deferred credentials. |
| Numeric content ratings — B10 | Owner approved one replaceable Like/Helpful/Favorite reaction instead; numeric ratings explicitly deferred. | Approved rating scale, eligibility and aggregation contract before introducing numeric storage or scores. |
| Judge0 live execution — C03/C04/B07 | Provider boundary, preflight and queued evaluation exist; live execution stays disabled. Owner deferred API-plan/key setup. | Configure credentials privately, discover/verify Python/Java/C++ compiler IDs and limits, run sandbox success/failure drills and measure feedback timing. Selected endpoint: `https://judge0-ce.p.rapidapi.com`. |
| Paid KodeBit purchases — F01 | No purchase storefront or browser-based credits. On 2026-10-04 the owner supplied provisional PHP packages (₱100/105 KB, ₱300/330 KB, ₱500/575 KB) for analysis, explicitly not solidified. | Final package approval and financial allocation/refund rules; ledger, verified Xendit integration, idempotent callbacks, receipts and sandbox configuration. See [sustainability analysis](sustainability-analysis.md). |
| Paid course enrollment — B03/F02 | Currently authored courses enroll free. | Server-owned pricing, approved spending/access rules and an atomic non-negative KodeBit ledger. |
| Paid challenge participation and prerequisite/rank gates — B07/F02 | Current authored challenges and weekly events are free for approved participant roles. | Authoring settings and approved gates, prerequisite/rank implementation and atomic financial access grants. |
| XP, ranks and monetary challenge/weekly rewards — E01–E06 | Streaks and game clearance exist; weekly events provide attempts/results without rewards. The owner supplied competition-rank tie splitting and a provisional 4% emission cap for analysis. | Final XP/rank/reward values, tied-score definition, cap denominator/period, rounding, creator funding and durable ledger/idempotency. Coding-event passes do not currently grant daily streak progress. |
| Creator earnings and payouts — F03/F04 | Provisional 65% creator / 35% platform net split is recorded for sustainability analysis; no earned balances or payouts exist. | Final recognition/allocation, gateway fee/tax treatment, rewarded-token funding, payout eligibility/minimum/fees and refund/reversal rules; verified Xendit boundary and financial concurrency tests. |
| SendGrid and staging live setup | Owner reaffirmed live setup deferral on 2026-10-04. Named TLS-required SMTP transport and sanitized local setup command are available. | Private API key, authenticated sender/domain and real delivery/worker tests; staged HTTPS, storage, supervision, monitoring and managed restore verification. See [setup guide](integration-setup.md). |
| Account deletion with authored content — Delete Account (source labels A10) | Owner approved deletion only when no modules, courses or challenges have been authored. Creator accounts may archive instead. | Approved retention/removal and attribution rules for published and draft content, pinned revisions, learner access and free-text personal data. Do not silently delete shared learning material. See [deletion scope](account-deletion-plan.md). |

Purchases were deferred in direct response to the package-pricing question.
The existing free learning/game experience should stay usable while monetization
is unavailable. These entries preserve the approved decisions; they do not
authorize guessed fees, rewards, balances or production provider activation.

Relevant implementation records: [submissions](challenge-submission-implementation.md),
[weekly events](weekly-challenge-plan.md), [course learning](course-learning-implementation.md),
[game progression](play-implementation.md).

G08–G10 managed presets explicitly use Deferred rewards under the owner's approved
first-release scope; they do not unlock monetary/XP/rank rewards. See
[preset implementation](game-presets-implementation.md).

The owner reaffirmed live-provider/staging deferral on 2026-10-04 while asking for
easy later setup. Do not re-request these credentials during independent work.
Provisional business values are tracked as decisions needed before implementation,
not silently adopted as final monetary rules.
