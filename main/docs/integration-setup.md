# Deferred integration setup guide

Live Google, Judge0, SendGrid and staging setup remains owner-deferred on
2026-10-04. This guide prepares later configuration without enabling services,
creating cloud resources or asking for keys again. Never put credentials in chat,
Git, screenshots, command arguments, frontend code or support logs.

Run `php artisan kody:setup-status` to see sanitized **local configuration** checks.
It contacts no provider and does not prove that credentials, workers or storage
work. Existing `/up` and `/ready` remain public liveness/database probes only.

## SendGrid

Authenticate the sender/domain with SendGrid, configure SPF/DKIM and review DMARC.
Create a restricted Mail Send API key and save `SENDGRID_API_KEY` privately.
Set `MAIL_FROM_ADDRESS` to the authenticated sender and choose `sendgrid` for:

```dotenv
MAIL_MAILER=sendgrid
ACCOUNT_VERIFICATION_MAILER=sendgrid
ACCOUNT_RECOVERY_MAILER=sendgrid
ACCOUNT_NOTIFICATION_MAILER=sendgrid
```

The named transport sets `smtp.sendgrid.net:587`, username `apikey`, required TLS
and a bounded timeout. It uses the existing dedicated account delivery boundary;
there is no log/failover fallback for sensitive mail. Credentials are loaded from
Laravel config, so rebuild the config cache and restart workers after changes.
See [official SMTP setup](https://www.twilio.com/docs/sendgrid/for-developers/sending-email/integrating-with-the-smtp-api).

In isolated staging, send an ordinary non-sensitive probe first, then exercise
registration verification, generic password recovery and an account-change notice
with synthetic addresses you control. Confirm actual receipt, sender identity,
expiry/single use, failure retry and delivery-state transitions. Disable click
tracking or prove that it preserves the fragment-based one-time links. A database
worker/array test is not inbox delivery evidence. SMTP acceptance alone also does
not prove inbox delivery. No live messages are sent by `kody:setup-status`.

## Judge0

Use the owner-selected CE endpoint `https://judge0-ce.p.rapidapi.com` and configure
the RapidAPI key/host privately. Discover compiler IDs using that provider's
language endpoint; do not copy guessed IDs from another deployment. Configure
`JUDGE0_PYTHON_ID`, `JUDGE0_JAVA_ID` and `JUDGE0_CPP_ID`, then follow the existing
[submission preflight](challenge-submission-implementation.md). The existing
`kody:judge0-check` verifies provider mappings/limits, not learner execution.

Enable only within isolated staging for discovery/checks and fake-free success,
wrong answer, compilation/runtime failure, timeout and retry drills. Verify the
queue worker, submission expiry and language/resource limits before production
participation. Keep network access disabled in executions. Record the provider
plan's execution/batch/poll billing units and spend cap; see
[pilot cost assumptions](sustainability-analysis.md). Keep disabled outside those
authorized checks until setup is accepted.

## Google identity

Create a Web OAuth client with an exact HTTPS callback matching
`GOOGLE_REDIRECT_URI`. Set client credentials privately and keep
`GOOGLE_AUTH_ENABLED=false` until isolated staging is ready. Exercise explicit
password-confirmed linking for an existing verified Active Kody account, provider
subject sign-in, unlinking, cancelled consent, invalid/expired state and session
revocation. Never auto-link by email or create Google-only accounts. Follow
[the identity implementation](google-authentication-implementation.md).

## Staging and production operations

Use [the operations runbook](deployment-operations.md) for immutable release
deployment, workers, scheduler, private credential storage, alerts and restoration.
Verify HTTPS/secure cookies, `APP_DEBUG=false`, private RDS CA verification,
private object access, supervised worker restart, real minute scheduling and
encrypted managed backup recovery on a new target. Measure realistic catalog,
dashboard, course and worker load against the SRS before accepting capacity.

The local pilot suggests small EC2/RDS/S3 resources, not proven SRS availability.
Record the regional quote, operational ownership and backup/recovery values before
provisioning. Do not run test seeders or `migrate:fresh` on shared/live data.
The [delegated business policies](economy-and-launch-decisions.md) are now adopted;
real-money activation remains separately gated. No setup command enables payments.

## Xendit economy setup — deferred live verification

Use the merchant's approved GCash Payments API and Payouts v3 products. Store
`XENDIT_SECRET_KEY`, `XENDIT_CALLBACK_TOKEN` and `XENDIT_BUSINESS_ID` privately.
Keep `XENDIT_ENABLED=false` until evidence exists. Configure HTTPS
`POST /webhooks/xendit` in the provider dashboard, with callback authentication,
for payment capture/failure/expiry, payout success/failure/rejection/reversal and
refund success/failure. Browser wallet returns cannot prove payment.

Record verified all-in contract values, including tax/processing charges:
`XENDIT_PAYMENT_FEE_BASIS_POINTS`, `XENDIT_PAYMENT_FEE_FIXED_MINOR`,
`XENDIT_PAYOUT_FEE_MINOR`, `XENDIT_REFUND_FEE_MINOR`; there are no guessed defaults.
Set `XENDIT_CONTRACT_VERIFIED=true` only after review. Verify real GCash redirect
hosts against the strict adapter allowlist; never accept arbitrary URLs. Compare
success/failure/reversal amounts, retained fees, monthly minimums and refund fee
reporting with merchant statements.

Use isolated sandbox accounts for top-up/duplicate callback, return without
callback, wrong amount/currency/merchant, creator sale, holding/reservation,
payout success/failure/reversal, untouched-purchase and unused-access refunds,
and lost-response reconciliation. Payments/refunds use numeric major units;
Payouts v3 uses integer centavos. Never resend an uncertain payment/refund POST.

Configure `KODY_OPERATIONS_OWNER`, `KODY_BACKUP_RESPONDER` and
`KODY_BUDGET_ENFORCED=true`; record a current estimate through Administrator finance.
Only after the operational runbook passes may the operator attest
`KODY_STAGING_VERIFIED=true`. Run `kody:launch-check` and `kody:ledger-check`, rebuild
config cache and restart workers listening to `payments,notifications,default`.
Actual monthly minimums/failed-attempt charges are separately recorded operating
expenses. Application receipts are not tax invoices. Flags are attestations,
not independent proof. No sandbox account, email, cloud resource or real payment
is created by this development run.
