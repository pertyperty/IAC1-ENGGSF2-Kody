# Kody sustainability analysis — pilot planning draft

Prepared 2026-10-04 for fewer than 100 registered users. The owner supplied the
packages, proposed 65/35 net-revenue split and 4% reward cap as **provisional**
research inputs, not finalized financial rules. No purchases, earnings, payouts
or reward issuance are enabled by this document. See the
[deferred register](deferred-features.md) and [completion roadmap](platform-completion-roadmap.md).

## Workload and costing assumptions

- 100 registered users, 20 daily active learners and approximately 5–10 concurrent
  interactive sessions; 100 simultaneous users requires a separate load test.
- Games, practice quizzes and the virtual terminal execute locally with bounded
  server replay; only full Python/Java/C++ challenges use Judge0.
- 100 users × 3 confirmed coding attempts/month × 10 test cases = 3,000 provider
  executions. A busy pilot with three times that activity uses 9,000 executions.
  Polling, discovery, retries and the provider's batch billing interpretation must
  be checked against the subscribed plan. An application attempt is not one
  provider execution when it contains several test cases.
- 730 operating hours/month, one AWS region, 10 GB of private objects, small
  transactional email volume and bounded logs. No video hosting/transcoding.
- USD billing converted at an **illustrative planning rate of ₱58/USD**, not a
  verified exchange rate. Replace it with the actual invoice/card rate and taxes.
  Free trials, credits and student offers are excluded from steady-state budgets.

## Smallest practical pilot configuration

Use the existing approved EC2/RDS/S3 architecture. Start with one Linux EC2
`t3.small` (2 GiB RAM) for Nginx, PHP-FPM, the scheduler and one supervised queue
worker, building assets before deployment. A 1 GiB host leaves less headroom for
PHP workers and should be selected only after measurement. Use private Single-AZ
RDS PostgreSQL `db.t4g.micro` initially, with an upgrade to `db.t4g.small` if memory
or connections require it. Encrypt storage, restrict RDS to the application
security group, and rehearse managed backup restoration. Single-AZ is a pilot
tradeoff and does not independently prove the SRS availability target.

EC2 and RDS have region-dependent rates; obtain an `ap-southeast-1` Singapore
estimate before provisioning and compare latency/cost with another suitable
region. AWS bills compute, storage and relevant transfer separately. The ranges
below are engineering allowances, **not verified Singapore quotations**.
Sources: [EC2 pricing](https://aws.amazon.com/ec2/pricing/on-demand/),
[RDS PostgreSQL pricing](https://aws.amazon.com/rds/postgresql/pricing/),
[AWS calculator](https://calculator.aws/).

| Component | Suggested pilot choice | Monthly USD planning allowance |
| --- | --- | ---: |
| EC2 | One `t3.small`, On-Demand Linux; monitor burst credits | $22–30 |
| Application disk | 20–30 GB encrypted gp3 EBS, bounded logs | $3–5 |
| Public IPv4 | One address; $0.005 × 730 hours | $3.65 |
| RDS compute | Private Single-AZ PostgreSQL `db.t4g.micro` | $20–35 |
| RDS storage | 20 GB general-purpose storage; backup excess allowance | $3–5 |
| S3 | 10 GB Standard private objects plus requests/backup allowance | $1–3 |
| Judge0 CE | RapidAPI Basic pay-per-use; 3,000–9,000 executions | $5.10–15.30 |
| SendGrid Email API | Essentials entry tier after trial | $19.95 |
| Monitoring/transfer/snapshots | Small CloudWatch/log/transfer contingency | $5–10 |
| **Base estimate** | Before tax, domain, support and staff time | **$82.70–126.90** |
| **Budget with 20% contingency** | Account for uncertainty and small overages | **$99.24–152.28** |
| **Illustrative PHP budget** | At ₱58/USD | **₱5,756–8,833/month** |

AWS charges $0.005/hour for public IPv4; one continuously allocated address is
$3.65 at 730 hours. Use private RDS and avoid adding public addresses unnecessarily.
[AWS IPv4 documentation](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/using-instance-addressing.html).
S3 also charges for requests, retrieval/transfer and optional features; storage
alone is not the complete bill. [S3 pricing](https://aws.amazon.com/s3/pricing/).
Allow another $1–2/month for an annual domain as a planning assumption. NAT
gateways, load balancers, Multi-AZ databases, premium support and long retention
can change this substantially. A single public application host and private RDS
do not need a NAT gateway merely to exchange database traffic.

The selected Judge0 CE RapidAPI listing currently shows Basic **$0.0017 per
submission/use**, rather than an assumed perpetual free quota. Its Pro plan is
$44.99/month with 2,000 submissions/day; the listing also shows a bandwidth
allowance/overage. For 3,000–9,000 executions, Basic is the lower modeled cost;
verify the account's exact batch units and polling allowance before enabling it.
[Judge0 CE provider pricing](https://rapidapi.com/judge0-official/api/judge0-ce/pricing).

SendGrid's current Email API offer is a **60-day trial at 100 emails/day**, followed
by Essentials starting at **$19.95/month**. The trial is useful for setup, not a
permanent production budget. Domain authentication and delivery tests are still
required. [Twilio SendGrid pricing](https://www.twilio.com/en-us/products/email-api/pricing).

No subscription or infrastructure is purchased by this recommendation. User
count alone cannot guarantee capacity; record latency, DB memory, queue age,
provider usage and monthly spend during the pilot before expanding.

## Draft KodeBit packages

| Package | PHP price | KodeBits | Gross PHP per KB | KB above a nominal 1 KB/₱1 |
| --- | ---: | ---: | ---: | ---: |
| Starter | ₱100 | 105 KB | ₱0.95238 | 5 KB |
| Builder | ₱300 | 330 KB | ₱0.90909 | 30 KB |
| Explorer | ₱500 | 575 KB | ₱0.86957 | 75 KB |

The nominal comparison is analysis only, not an approved KB redemption rate.
The largest package gives about 9.52% more KB per peso than the smallest. Treat
package bonuses as issued purchasing value when modeling liabilities; they do
not automatically belong in, or outside, the separate reward cap.

## Gateway fees and 65/35 net split

The draft “about 3%” misses a fixed charge. Xendit's published Philippine table
lists **GCash e-wallet 3% + ₱11**, **Maya 2% + ₱11** and **domestic PHP cards
3.5% + ₱11**. Product, contract, VAT, payouts and additional services can change
the final invoice. Use the actual method and contract, not one universal rate.
[Xendit pricing](https://www.xendit.co/en/pricing/),
[Xendit fee and VAT treatment](https://docs.xendit.co/v1/docs/transaction-fees).

Illustration using GCash's published fee, before any additional tax/refunds:

| Package | Fee = 3% + ₱11 | Effective fee | Remaining amount | Creator at 65% | Platform at 35% |
| --- | ---: | ---: | ---: | ---: | ---: |
| ₱100 / 105 KB | ₱14.00 | 14.00% | ₱86.00 | ₱55.90 | ₱30.10 |
| ₱300 / 330 KB | ₱20.00 | 6.67% | ₱280.00 | ₱182.00 | ₱98.00 |
| ₱500 / 575 KB | ₱26.00 | 5.20% | ₱474.00 | ₱308.10 | ₱165.90 |

Domestic-card fees under that schedule would be ₱14.50 / ₱21.50 / ₱28.50.
These calculations model eventual paid-content economics. **A wallet top-up is
not yet a creator sale**: do not grant 65% of every purchase to a creator before
the buyer chooses content. Purchased KB remain available to spend, and earned
balances/refunds must reconcile to retained financial history.

If the full purchased value is eventually spent on creator content under this
illustration, the maximum platform contribution per package is shown above.
Unspent KB, promotional KB, mixed package rates, refund fees, tax and payout fees
need explicit allocation rules. Do not value every spent KB at ₱1: the package
rates differ. A defensible candidate is purchase-lot cost allocation, but it is
not adopted here without an approved spending/rounding/reversal contract.

For a ₱6,000 monthly operating-cost example, break-even requires at least:

| Only this package is sold and fully spent | Purchases/month needed | Gross receipts |
| --- | ---: | ---: |
| ₱100 | 200 | ₱20,000 |
| ₱300 | 62 | ₱18,600 |
| ₱500 | 37 | ₱18,500 |

Those are optimistic lower bounds that omit rewards, taxes, chargebacks and
creator payout overhead. With 100 users and only 50 buying a ₱300 package once,
the modeled contribution is ₱4,900, below ₱6,000. At the estimated upper pilot
budget, approximately 91 fully spent ₱300 purchases would be needed before
those other costs. Fewer than 100 users can support a technical pilot, but this
draft does **not** establish commercial sustainability.

## Reward cap and ties

The owner proposes reward emission at no more than 4% of token circulation.
This needs a defined measurement period and denominator. “Current outstanding
wallet balances,” “paid KB issued during the month” and “paid KB spent during
the month” produce different limits. An outstanding-balance denominator can
grow with prior rewards and shrink with spending; it is not a revenue yield.
The cap alone supplies no actual XP, rank thresholds or reward allocations.

For illustration only, 50 purchases of the 330 KB package issue 16,500 KB; 4% of
that issuance is 660 KB. At the illustrative net value per purchased KB,
₱280/330, this has up to ₱560 of net spending value. A 65% creator allocation
would add ₱364 of potential creator liability despite no extra purchase receipt.
Specify how rewarded KB fund creators and which account bears that expense.

The supplied tie rule uses competition ranks: two first-place finishers occupy
ranks 1 and 2, split the sum of those rewards equally, and the next finisher is
rank 3. This is recorded as the intended policy. Fractional KB, remainder
allocation, ties crossing the paid-rank boundary, cap reservation under
concurrency and reversals remain decisions before issuing financial rewards.
XP formulas, rank thresholds and the definition of a tied score also remain
unspecified. No automated reward is inferred from this analysis.

## Research claims and decisions still needed

The owner supplied comparisons to Roblox, Duolingo, Udemy and retail loyalty
programs, plus Alstyne et al. (2016), Gandomi & Marshall (2015) and Udemy (2023).
Those references were not supplied as complete bibliographic sources and are
**unverified background claims**, not evidence that a 4% cap or 65/35 split is
sustainable for Kody. Verify exact titles/pages and comparable fee definitions
before using them in an academic or investor-facing document.

Before implementation, finalize packages, currency/tax treatment, premium
content prices/access, KB cost allocation, bonus/reward funding and cap period,
rounding/refunds, creator earnings recognition, payout eligibility/minimum/fees,
and XP/rank formulas. Before activation, verify provider contracts privately,
forecast actual usage, obtain the regional AWS quote, run staging/load/restore
tests and set spend alerts. Live integration setup remains owner-deferred.
