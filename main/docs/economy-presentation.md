# Kody economy presentation

Use the [isolated Xendit Developer Mode setup](xendit-local-sandbox.md) for a real test-mode GCash checkout. The offline rehearsal covers more: it uses Kody's actual pages, checkout requests, accounting, access grants and payout review, with provider responses and mail faked. Use the offline screenshots for the withdrawal portion until test payout capability and delivery have been verified separately.

| Screen | Show and explain |
| --- | --- |
| Learner Wallet | Starter: PHP 100 buys 105 KB (1.05 KB/PHP). Builder: PHP 300 buys 330 KB (1.10 KB/PHP). Explorer: PHP 500 buys 575 KB (1.15 KB/PHP). Leave **Email me the receipt** off on one purchase and check it on the other. |
| Payment receipt | A verified capture credits the package once. Both purchases have private printable PDFs. Only the checked purchase queues the receipt email. A browser return alone does not credit the wallet. |
| Paid Courses | Two Explorer purchases add 1,150 KB. Unlock two reviewed courses priced at 500 KB each. The learner retains 150 KB. |
| Instructor Wallet | In this rehearsal the simulated capture fee is PHP 15.50 per PHP 500 package. This leaves PHP 484.50 behind 575 KB. The ledger takes value from the oldest lots first and rounds down in centavos. Each 500 KB sale consumes PHP 421.30 of backing, of which PHP 273.84 (65%, rounded down) goes to the instructor. |
| Earnings | PHP 547.68 is initially maturing. No withdrawal is allowed then. After 14 days (advanced in the rehearsal), PHP 547.68 becomes available. Enter PHP 499.99 to show the PHP 500.00 minimum, then enter PHP 500.00 to see the estimated recipient amount. |
| Admin Finance | Review the test recipient. The PHP 500.00 stays reserved during review and provider processing. Approval alone does not record a completed withdrawal. |
| Settled Earnings | A simulated verified payout deducts PHP 500.00, including a PHP 1.00 fixture fee. The recipient amount is PHP 499.00 and the instructor retains PHP 47.68. |

The example fees above are fixtures. Developer Mode top-ups in Kody currently use zero payment fee; a live contract could use different fees. KodeBits are spendable credits. Instructors withdraw eligible **cash earnings** from sales; Contributors can claim eligible whole KodeBits internally. Neither can withdraw their personal wallet as cash.

## Replay and reset

From `main/`, use a disposable loopback PostgreSQL cluster on a port other than 5432, create `kody_test` in it, and set `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` and a throwaway `APP_KEY` in the test process. Build assets with `npm run build`, then run `php vendor/bin/pest --filter "presentation rehearsal"`. The test creates throwaway learner, instructor and administrator accounts, publishes reviewed courses, and rolls back its transactions after each run. For local captures only, point `KODY_PRESENTATION_SNAPSHOTS` at an existing directory outside the checkout; the test saves HTML responses from these fixtures to that directory. Do not export snapshots from an instance with real users or real payout recipient information.

## Receipt delivery

Kody uses [Payment Requests v3](https://docs.xendit.co/apidocs/create-payment-request) with a [payment status webhook](https://docs.xendit.co/apidocs/payment-status-callback-webhook). This integration does not send the buyer's email to Xendit or rely on Xendit to email them. Use the existing [Mailgun SMTP configuration](integration-setup.md) and `ACCOUNT_NOTIFICATION_MAILER=mailgun` for a deliberate local delivery test; run the `notifications` queue only on the isolated database and keep other mail on a fake or local capture transport. Check the box on one new purchase from a verified test account with an authorized recipient address. Verify that the PDF arrives and that redelivering the provider callback does not send it twice. Do not retry uncertain SMTP delivery without checking its state first.

Migrate the separate sandbox database before restarting its app and worker. Purchases created before the receipt preference was stored have no recorded opt-in; their wallet receipt remains available. Existing deliveries keep their recorded state. Sandbox receipts say that no real payment was collected. Kody's payment acknowledgment is not a tax invoice.
