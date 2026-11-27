## Local Xendit Developer Mode

Use a separate checkout and a fresh PostgreSQL cluster listening on loopback on
an unused port other than 5432. Create `kody_xendit_sandbox` there. Never copy an
existing Kody database or point this instance at an existing queue. This mode
exercises GCash top-ups only; refunds and creator payouts stay disabled. Use the
[offline presentation rehearsal](economy-presentation.md) to show the full journey.

1. Create a separate, Git-ignored `.env` for the sandbox instance. Use
   `APP_ENV=local`, a fresh application key and a loopback `APP_URL`. Keep
   `DB_URL` empty; use the sandbox database, `DB_CONNECTION=pgsql`,
   `DB_HOST=127.0.0.1` and the new port. Use the database queue on that same
   connection and `MAIL_MAILER=log` with throwaway accounts. Keep other providers
   disabled. Keep `XENDIT_ENABLED=false` and `XENDIT_CONTRACT_VERIFIED=false`.
2. Set the Developer Mode `XENDIT_SECRET_KEY`, `XENDIT_CALLBACK_TOKEN` and
   `XENDIT_BUSINESS_ID` privately. Set `XENDIT_SANDBOX_RETURN_URL` to a test-only
   HTTPS landing page that tells the tester to return to the local wallet. It
   does not need to access the wallet or receive any credentials. Set
   `XENDIT_SANDBOX_ENABLED=true` only on this instance. Do not set contract fees
   or attest to staging readiness for the sandbox; its fixture fee is zero.
3. Clear cached configuration, migrate the empty sandbox database and start the
   local web server and `php artisan queue:work database --queue=payments,notifications`.
   Sign in as a verified test learner and confirm that Wallet displays the
   Xendit test-mode banner. Do not make the web app publicly accessible.
4. When a public HTTPS relay is available, forward only `POST /webhooks/xendit`
   to the sandbox instance. Preserve the raw body and callback headers. Register
   the endpoint for **Payments v3 - Payment Status** in Xendit Test Mode; the
   Payment Requests v3 status endpoint can also point there to receive expiry.
5. Request one starter package from the wallet. Record the purchase reference,
   provider request ID and initial wallet balance. Complete the test checkout
   or use Xendit's [test simulation endpoint](https://docs.xendit.co/apidocs/simulate-payment-test-mode)
   for the request ID. Confirm the worker checks the payment request and the
   captured payment with Xendit. Check that the wallet moved by exactly one
   package, then resend the same callback from the dashboard and verify that
   the balance and number of receipts stay the same. Save identifiers and
   outcomes only, without callback tokens or checkout URLs.
6. Remove the Test Mode webhook registration and shut down the relay and worker
   when the test is over. Sandbox results do not satisfy live activation gates.

Provider references: [Payment Status webhooks](https://docs.xendit.co/docs/payments-api-webhooks),
[Retrieve payment request](https://docs.xendit.co/apidocs/get-payment-request),
and [Retrieve payment](https://docs.xendit.co/apidocs/get-payment).
