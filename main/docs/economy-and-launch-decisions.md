# Delegated economy and launch decisions — 2026-10-04

The owner explicitly asked Codex to recommend and implement the remaining
decisions. This amendment adopts the policies below; it supersedes earlier
business-rule deferrals, while preserving the separate live-provider/staging
deferral. Implementation and verification evidence belong in the accompanying
implementation record. This document is authority, not a completion claim.

## Purchases and access — F01/F02, B03/B07

- PHP packages: 100 pesos / 105 KB, 300 / 330, 500 / 575. No subscriptions,
  expiry, automatic top-ups, token-to-cash redemption or purchased rank/XP.
- Start with GCash through Xendit's current Payments API. A verified callback
  plus provider-side retrieval must agree on merchant, request, reference,
  currency, amount and captured status before credit. Browser returns never credit.
- Record the merchant's verified fee schedule, including tax and processing
  charges, before activation. Published rates are not a substitute for a contract.
  Allocate each purchase's net PHP backing across **all** its KB, including bonus
  KB. Consume lots FIFO using integer centavos and conservative exact rounding;
  allocate the last remainder when the lot is exhausted. Preserve the ledger.
- Reviewed creator prices: modules free or 5–100 KB; courses free or 20–500 KB;
  standard challenges free or 5–50 KB. Existing content stays free. Owners choose
  within these bands; staff review pricing with the immutable content revision.
  Never charge a creator for their own content or recognize self-sale earnings.
- One payment grants continuing access subject to lifecycle/moderation rules.
  Course purchases pin the approved course/modules and cover those lessons without
  extra module charges. Standard challenge access includes the existing lifetime
  three attempts, not another three after a price/revision change. Weekly events
  remain free. Raising a price does not recharge an existing access grant.
- Optional reviewed rank gates and up to five owned published module prerequisites
  apply only at first admission. Gates use persisted achievements, not browser
  assertions; existing paid/free enrollments and grants are grandfathered.

## XP, ranks and weekly results — B09/E03–E06

- First validated starter game or quiz: 20 XP for each level/kind. First validated
  creator-module assessment: 40 XP per module, shared across courses and revisions.
  First coding pass, in either standard or weekly play: Easy 50, Medium 100,
  Hard 150 XP once per challenge across both contexts.
  A first weekly pass also earns 25 XP once per event. Reading and local previews
  earn none. Replays may qualify streaks but do not farm XP. Do not retroactively
  invent rewards for pre-amendment history.
- XP ranks: Seedling 0, Explorer 200, Builder 600, Solver 1,500, Architect 3,000.
  These are achievement ranks, not account roles or level-ladder unlocks.
- Weekly score is passed test cases / total test cases, represented exactly as
  integer basis points from 0 to 10,000. Select each user's best terminal valid
  attempt; equal scores remain tied. Runtime, spend, submission time and hidden
  provider output do not break ties. Unavailable evaluations are excluded.
- Publish only after the event closes and committed evaluations finish. Store
  immutable results and competition ranks (1, 1, 3). Only full passes qualify for
  monetary rewards. Nominal first/second/third KB prizes are 10 / 6 / 4.
- Tied users pool the prizes for all occupied ranks, including a tie crossing the
  third-place cutoff. Divide equally into whole KB; keep any remainder unissued.
  This preserves equal treatment rather than choosing a favored user for dust.
- Calendar-month Manila cap: floor(4% of **purchased KB spent on content in the
  previous month**, net of reversed spends). Reward and creator-issued KB are
  excluded from this denominator. Unused capacity expires; there is no reward debt.
  Reversals lower remaining issuance capacity without clawing back spent rewards.
- Every reward KB must be backed by 100 centavos reserved from matured platform
  margin. Cap and cash both limit issuance. If the nominal pool exceeds either,
  scale shares down deterministically and apply equal whole-KB rounding. Zero
  funding means zero KB prizes, with XP/results still available. No random rewards.

## Earnings, payouts and remedies — F03–F05

- Recognize 65% creator / 35% platform when paid access commits, using the exact
  backing value of consumed lots; a top-up alone creates no creator earnings.
  Round creator centavos down and assign the remainder to platform. Reward-backed
  spending follows the same split, funded by the reserved platform cash.
- Preserve F04's role distinction: Instructor sales earn PHP; Contributor sales
  earn KB (65% of spent KB tracked in thousandths until whole-KB claim), carrying
  their allocated PHP backing. Snapshot settlement kind at authoring; later role
  changes cannot rewrite a sale. Hold earnings/platform margin 14 days.
- Instructor payout: at least PHP500 matured earnings, password re-authentication,
  explicit confirmation, verified recipient details, Administrator approval and a quoted
  contract-based fee borne by the recipient without markup. Reserve gross earnings
  immediately; deduct only after verified provider success. Failure releases the
  reservation; unknown/timeouts remain pending reconciliation, never blind resend.
  A callback and retrieval confirming reversal restore the returned transfer
  amount once; retain the original quoted fee. Out-of-order reversals settle and
  reverse the still-reserved allocation atomically rather than crediting twice.
- Contributors claim matured whole KB internally, without cash withdrawal.
  Instructors may still claim historical Contributor KB earnings. Both paths use
  durable idempotency and private histories.
- Courtesy purchase refund: within 14 days, the entire original lot must be
  unspent. Reserve its KB while a staff-approved full-gross refund is processing.
  Platform bears retained gateway/refund fees; verified success removes KB,
  verified failure releases them. No negative balance or browser-proven refund.
- Unused access refund: within seven days and before any assessment completion
  (course), validated module win, or confirmed coding attempt. Staff reviews the
  reason, reverses the associated unmatured earnings/margin and returns the same
  backed KB while revoking access; retained visits/history are preserved.
  Defects, withdrawals, disputes and mandatory remedies outside courtesy rules
  go to staff review; the courtesy limits do not assert a legal waiver.
- Critical receipts/payout/refund notices use a durable, retryable email outbox
  and private in-app updates. Delivery failure does not undo financial success.
  Preserve pseudonymous financial references; do not market application receipts
  as BIR-compliant invoices before the business's tax setup is verified.

## Creator deletion and operations

- Retain published/pinned/referenced educational content and audit history under
  neutral "Deleted creator" attribution. Do not silently delete learner material.
  Creator deletion requires explicit retention consent, staff privacy review of
  every retained revision and an unchanged inventory fingerprint. Personal data
  found in retained text must be addressed before approval; a checkbox alone
  does not automatically redact it. Block final erasure while financial balances,
  unsettled transactions, evaluations or review decisions remain outstanding.
  Retained learning material becomes free for new users; existing immutable prices,
  paid grants and historical sales remain preserved. No new sale pays a deleted creator.
- Balance relinquishment is a separate, optional permanent action, never automatic
  during deletion. Show exact wallet KB, cash earnings and fractional earned KB;
  require current password, the explicit phrase and an unchanged balance fingerprint.
  Block reserved/unmatured amounts and open operations. Users may spend, claim,
  request payout/refund or seek a remedy instead. Transfer voluntarily surrendered
  backing to platform cash through immutable entries with consent and receipt.
- Use the existing erasure outbox and identity tombstone. Keep financial records
  for at least the applicable five-year accounting period, extended for legal
  holds; exact tax-year filing dates and statutory documents require the operator.
  No automatic financial purge or promise of comprehensive anonymization.
- Pilot region: Singapore `ap-southeast-1`; monthly planning ceiling PHP12,000,
  including gateway minimum/processing allowances. Alerts at 50/80/100%, pause
  new provider-backed admissions at a breached configured limit, and investigate.
  One small EC2/private Single-AZ RDS/private encrypted S3 remains a pilot choice,
  not proof of the unchanged SRS 500-concurrent-user/99.9% targets.
- Daily encrypted RDS backups with 35-day retention/PITR; private object versioning
  with 30-day noncurrent retention and erasure reconciliation. Target RPO15 minutes,
  RTO4 hours; rehearse monthly on an isolated target and verify actual timings.
  Keep sanitized operational logs 30 days. Name an operations owner and backup
  responder before live activation; do not invent team members or buy resources.
- Numeric ratings stay deferred; existing replaceable reactions are sufficient.

## Primary references and verification boundary

[Xendit Payments API](https://docs.xendit.co/docs/migrate-payment-api-v2-to-v3),
[webhook authentication/idempotency](https://docs.xendit.co/v1/docs/handling-webhooks),
[fee, retained-refund fee and monthly-minimum policy](https://docs.xendit.co/v1/docs/transaction-fees),
[BIR EOPT accounting retention](https://bir-cdn.bir.gov.ph/BIR/pdf/flyer-eopt.pdf),
[RDS retention](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/USER_WorkingWithAutomatedBackups.BackupRetention.html).
Verify the merchant's actual contract, tax treatment and enabled API products in
staging. Code/provider fakes and policy adoption do not activate real money or
constitute legal, accounting, availability or recovery certification.
