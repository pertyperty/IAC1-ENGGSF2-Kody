# Foundation conventions

The [creator studio](creator-studio-implementation.md) adds Content-owned versioned
module publishing, Administration-authorized review and audit, noncritical
in-app notification delivery, and server-validated assessments feeding existing
Gamification daily activity. Published revision pointers keep draft edits out of
learner views. New modules and published replacements require Moderator/Admin
approval; Instructor owners may archive Published modules with preserved history.

The project owner's [2026-10-03 game-first amendment](game-first-product-direction.md)
sets the current product direction. The play hub is the primary experience;
Learning is a separate catalog, and creators attach versioned game/quiz template
instances to modules. Logical module boundaries still protect authorization,
content ownership, verified assessments and financial integrity. Presentation
does not need to mimic a conventional LMS or older document styling.

Game template definitions belong to the game runtime; Content owns creator
instances and publication; Challenge owns graded submissions/evaluation;
Gamification owns streaks, ladder state and deterministic progression;
Transaction owns monetization and KodeBit ledger effects. A template runtime
accepts validated data and emits a practice result. Graded results must go through
server validation and durable idempotent submission records before progression.
Client animation or local storage is never authoritative achievement evidence.

Authority: `AGENTS.md`, approved SRS v1.4, and the current SDD through Section 4.
The SRS (`Kody-SRS-V1.4-APPROVED.docx`) and SDD
(`Kody_GPLPCMS-SDD-upto-Section-4.pdf`) were supplied in the main checkout;
keep their approved versions alongside the application and record amendments.
SDD Sections 5–9 remain reserved; do not infer their schema or algorithms.

## Module ownership

Keep one Laravel deployment and the existing directory structure. Add module
subdirectories only when a real use case needs them, for example
`app/Actions/Account/RegisterAccount.php`. Controllers and Form Requests belong
in `app/Http`, policies in `app/Policies`, models in `app/Models`, and jobs in
`app/Jobs`. Use module subdirectories within those folders when useful.

| Name | Owner | SRS |
| --- | --- | --- |
| Account | Identity, authentication, account lifecycle and role requests | A01–A10 |
| Content | Course/module lifecycle and composition | D01–D09 |
| Challenge | Challenges, durable submissions, attempt limits and evaluation | C01–C07 |
| Gamification | Deterministic rewards and rankings | E01–E06 |
| Engagement | Learner access and participation orchestration | B01–B11 |
| Transaction | Token ledger, premium access, earnings, payouts and notifications | F01–F05 |
| Administration | Approvals, moderation, accounts, reports, presets and FAQs | G01–G13 |

Use internal actions/services for immediate cross-module operations. Engagement
requests submissions from Challenge; Gamification requests credits from
Transaction. Controllers authorize, validate, call the owner, and return a
response. Eloquent provides data access; add repositories only for a concrete
need. No module folders, provider adapters, or role tables are created in advance.

## Authorization

Use Laravel's existing Gate/Policy system and `auth`/`can` middleware. Unknown
abilities are denied. Introduce policies for each real resource/use case, call
`Gate::authorize` before invoking its action, and recheck permissions and record
state immediately before sensitive writes. Laravel automatically discovers
conventionally named policies; explicitly register policies if module namespaces
require it. No universal administrator bypass is defined.

Learner, Contributor, Instructor, Moderator and Administrator are the approved
roles. Registration persists Learner only; privileged inheritance and approval
transitions must follow A09/A10 and G02/G05 when implemented. Role fields
and privileges must remain excluded from user input and mass assignment.
The current User fillable list accepts identity fields and password; regression
tests verify role/permission input is ignored and undefined privileges are denied.
This is an authorization mechanism, not completed RBAC behavior.

## Transactions and asynchronous work

PostgreSQL is canonical. Use transactions, foreign keys, uniqueness/check
constraints, locks or record versions for business invariants. Store money as
integer minor units or exact decimals. Add migrations incrementally.

The existing database queue tables are sufficient for the foundation. Database
jobs dispatch after commit by default, preventing workers from seeing rolled-back
state. This alone does not meet SDD 4.2's atomic pending-work requirement: when a
workflow needs reliable delivery, write its durable pending job/outbox in the
same database transaction as the initiating state, then process it idempotently.
Define bounded timeouts, retries/backoff and unique operation IDs per real job.

Scheduled definitions belong in `routes/console.php`. Real weekly tasks must use
overlap/distributed-lock protection and their shared weekly-instance state;
no placeholder tasks have been added. Store timestamps in UTC; resolve weekly
PHT semantics against the SRS before implementing them.

## External boundaries

Introduce adapters in `app/Integrations/{Google,Judge0,SendGrid,Xendit}` only with
the owning use case. Define a narrow contract for test doubles; translate
provider responses to internal results. Configure secrets through config files,
never controller `env()` calls. Validate responses, bound timeouts, sanitize logs,
retry only safe operations, and enforce durable idempotency for financial effects.
CI must use fakes; no real provider integration is implemented in this foundation.

## Traceability and unresolved decisions

This foundation supports SRS 3.4 (security/technical constraints), 4.2 (persistent
data, asynchronous processing, access control and modular maintenance), and SDD
3.1–3.3 / 4.1–4.2. It completes no A–G business use case and proves no availability,
concurrency, latency, encryption-at-rest or backup target.

Subsequent account slices are documented in
[registration/verification](account-implementation-plan.md) and
[A03 login](login-implementation.md), including their approved amendments and
actual scope. Those implementations do not establish unmeasured NFR compliance.

[A04/A05 recovery/profile](recovery-profile-implementation.md) and the
[play implementation](play-implementation.md) document the subsequent tested
slices. The play hub now records server-validated daily streaks and level
clearance; it does not yet issue XP, ranks, KodeBits or creator-published content.

[A10 creator review](creator-review-implementation.md) adds policy-controlled
private credential review, atomic role elevation/audit/pending notification and
stale-write prevention. It grants Instructor access but does not yet implement
the Creator studio or module publication.

Requirement conflict detected:

- SRS: 3.1.1 Code Execution API lists Python, Java, C++, JavaScript and PHP;
  3.4 Technical Environment Constraints restrict Version 1 to Python, Java, C++.
- SDD: 3.3 / 4.1 delegate evaluation behavior to the SRS and do not resolve this.
- Consequence: language validation and Judge0 mapping cannot be finalized.
- Recommendation: approve one Version 1 language list before C03/C04 integration.

Requirement conflict detected:

- SRS: A01 Business Rules require 12–32 characters; 5.2 Data Dictionary,
  `password_hash`, says 2–32 characters and describes encrypted passwords.
- SDD: Account ownership in 4.1 provides no alternative password constraint.
- Resolution approved by the project owner on 2026-10-03: use 12–32 characters
  and secure password hashing. The owner also approved adding Unverified status
  to reconcile A01/A02 with the account dictionary. See the account implementation
  document for the implemented scope and remaining decisions.

SRS 3.1 permits MySQL/PostgreSQL and Appendix D costs MySQL; SDD 3.2 explicitly
selects PostgreSQL, as does AGENTS.md. PostgreSQL is used under that precedence;
the budget should be reconciled before infrastructure provisioning.

The supplied SRS 4.2 states 99.9% uptime; no alternate numeric target was found
in the reviewed foundation sections. Do not assert a conflict or compliance
without source evidence and measurement.

Technology mismatch: composer.json declares PHP ^8.3, but locked Symfony 8.1
runtime packages and PHPUnit 13 require PHP >=8.4.1. Dependencies are preserved;
development, CI and deployment instructions require 8.4.1+. Align the declared
minimum in a separate dependency-baseline decision if PHP 8.3 support is required.
