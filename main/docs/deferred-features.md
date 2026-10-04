# Deferred-feature register

The owner delegated remaining business decisions and implementation on 2026-10-04.
The [economy/launch amendment](economy-and-launch-decisions.md) supersedes earlier
pricing, XP, rewards, payout and creator-deletion deferrals. Their code and
fake-provider evidence belong in [economy implementation](economy-implementation.md).
This register owns remaining activation/verification deferrals; do not ask for
these private keys again.

| Deferred scope | Current preparation | Needed before activation/completion |
| --- | --- | --- |
| AWS / staging / production operations | Worker/log examples, private RDS/versioned S3 template, budgets, responder configuration, launch/ledger checks and local restore evidence. | Regional quote, named humans, staged HTTPS/IAM/encryption/PITR/version erasure, managed restore, alerts, measured load and rollback. No resources purchased or deployed. |
| Xendit live purchases/payouts/refunds | Disabled GCash adapter/storefront, verified callback/retrieval, exact FIFO ledger, settlement reservations, refunds and reconciliation. | Merchant onboarding, sandbox credentials, verified fee/tax contract, products/channels, callback/redirect checks, tax setup and end-to-end success/failure/reversal/timeout drills with passing staging operations. No real payment enabled. |
| Judge0 live execution — C03/C04/B07 | Provider preflight/queued evaluations and fakes; selected CE endpoint `https://judge0-ce.p.rapidapi.com`. | Private plan/key, provider-specific Python/Java/C++ IDs/limits, execution/error drills, billing units and timing measurement. Live execution disabled. |
| Google identity live setup — A03/A06 | Explicit password-confirmed linking, subject-based sign-in and unlinking for existing verified Active accounts; disabled. | Private client/exact HTTPS callback, consent and live authentication/session/error verification. No email auto-linking or Google-only accounts. |
| SendGrid live setup | TLS-required SMTP, durable account/financial notices and uncertain-delivery review. | Private key, authenticated sender/domain, real inbox/delivery/failure and supervised worker checks. Array transport is testing only. |
| Numeric ratings — B10 | Approved replaceable/removable Like/Helpful/Favorite reaction. | Approved scale, eligibility and aggregation if numeric ratings are later wanted. |
| Actual device and SRS capacity/availability evidence | Local Chromium/viewport and bounded-query regressions; explicit pilot sizing assumptions. | Safari/Firefox/devices, staged load against unchanged SRS targets, measured feedback/latency/availability and recovery. |

Setup: [integration guide](integration-setup.md). Policy:
[decisions](economy-and-launch-decisions.md). Verification:
[operations](deployment-operations.md). Costs:
[sustainability](sustainability-analysis.md). Business choices are adopted; private
contracts, real systems and evidence remain separate requirements.

Managed presets retain Deferred direct rewards. Validated assessments can earn
shared once-per-module XP without inventing preset-specific monetary payouts.
Pre-amendment weekly events retain Deferred KB rewards; newly configured events
use the adopted capped policy. Historical XP or financial rewards are not backfilled.
