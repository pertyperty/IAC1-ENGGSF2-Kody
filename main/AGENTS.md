# Kody Engineering Agent Guidelines

## Mission

The owner approved the first Google identity slice for existing verified Active
accounts: explicit linking after confirming the current Kody password, followed
by sign-in through the linked provider subject. Never automatically link by email
or create Google-only accounts; new registration remains A01/A02. All five roles
retain the existing account-status, lockout and single-session rules. Linking and
unlinking are sensitive account changes and revoke sessions. See
[Google authentication](docs/google-authentication-implementation.md).
The owner subsequently deferred Google OAuth configuration/live verification.
Keep it disabled and track setup in the [deferred register](docs/deferred-features.md);
continue independent development without requesting those credentials again.

The owner approved B10 one replaceable Like/Helpful/Favorite reaction per user
and content, with removal; numeric ratings are deferred. The owner delegated the
prior-access policy to platform judgment: verified Active Learners, Contributors
and Instructors need current access and a server-recorded authorized opening or
validated completion. Course reactions also require enrollment. Catalog browsing,
creator/staff previews and browser assertions do not qualify. See
[content feedback](docs/content-feedback-implementation.md).

For D04/D09/C07, the owner approved preserving all retained dependencies, even
after they become inactive. Learner history, pinned course revisions, weekly
events, feedback and moderation history block permanent deletion; offer archival
when eligible instead. Only dependency-free owned content may be permanently
deleted after explicit confirmation and a locked dependency recheck. Ordinary
authoring audits remain durable references. See
[content deletion](docs/content-deletion-implementation.md).

### Product direction amendment — approved 2026-10-03

Kody is a game-first coding platform. The project owner explicitly approved this
direction in chat; it supersedes older presentation guidance and the previous
feature sequencing where they would produce a conventional learning website.
See [the product amendment](docs/game-first-product-direction.md).

- Center the experience on playable games, daily streaks and a ladder of levels.
- Let guests play an immediate landing-page trial. Require authentication for
  learning modules, with clear sign-in and registration routes.
- Give Learning its own browse/search catalog rather than making it the play hub.
- Treat instructors as learning content creators. Modules compose learning
  material with game/quiz assessments instantiated from reusable templates.
- Separate game mechanics from creator-supplied content. Define typed, versioned
  placeholders for instructions, scenarios, objectives, questions and feedback.
  Placeholders are data, never uploaded executable JavaScript/PHP or HTML.
- Prefer suitable open-source games after checking their licenses, assets,
  dependencies, security and adaptation cost. Scratch implementations are allowed.
  Record provenance, retain required notices and pin any imported code version.
- Use Coddy/Duolingo as interaction inspiration, with original Kody branding and
  assets. Favor accessible, playful, modern interaction over rigid document styling.
- Keep account security, PostgreSQL integrity, server-side authorization,
  deterministic rewards, ledger ownership and deployment safeguards intact.
  Browser practice results cannot directly grant XP, ranks or KodeBits.
- Do not invent streak qualification/timezone/reset rules or ladder thresholds.
  Record unresolved decisions before persisting progression or issuing rewards.

This is an approved product amendment, not a claim that the original SRS/SDD
files have been revised or that the full game, creator or monetization systems
are implemented. Keep traceability and clearly label preview-only behavior.

The owner approved daily streak qualification by a server-validated game/quiz
win, midnight Asia/Manila boundaries, reset after a missed day and no freezes.
Clearing a game objective unlocks the next level; XP/ranks stay separate.
The owner also approved A10 Pending/Approved/Rejected states, rejection preserving
Learner access, Moderator/Administrator review, and format checks plus manual
credibility review without an invented domain allowlist. See
[play implementation](docs/play-implementation.md) and
[creator review](docs/creator-review-implementation.md) for scope and tests.

The owner subsequently approved Instructor-only module authoring (Contributors
author coding challenges), Moderator/Administrator approval of new modules and
published revisions, and Published as D03's archive-eligible state. Archived
modules are hidden from learners while revisions, activity and audit history are
preserved. See [creator studio](docs/creator-studio-implementation.md).

The owner approved extending the same Moderator/Administrator review policy to
new courses and published course revisions. Version 1 coding-challenge languages
are Python, Java and C++, resolving SRS sections 3.1.1 and 3.4; JavaScript and PHP
are excluded from Version 1. Judge0 language IDs must still be verified against
the configured provider before integration.

The owner approved reusing owned modules across multiple courses, with each
course revision pinning its approved module revisions. This supersedes the
dictionary's single optional course_id representation. Preserve old published
course content and ordering during draft edits and review. See
[course composition](docs/course-composition-implementation.md).

The owner approved resolving B03/B04 availability as Published content and
verified Active accounts, without adding an Active content lifecycle state.
D08 permits only the owning Instructor to archive a Published course. Archived
courses leave browsing and block new enrollment; existing enrollees retain
access and progress. Individual archived modules remain unavailable under D03.
The owner approved free enrollment for currently authored courses in this release;
paid enrollment stays unavailable until pricing and the KodeBit ledger exist.
See [course learning](docs/course-learning-implementation.md).

The owner approved Draft/Published/Archived/Deleted coding-challenge lifecycle
states with separate Draft/Pending/Approved/Rejected revision review states.
Published means approved and archive-eligible, resolving C01/C02/C06 versus the
challenge dictionary. Published replacement revisions require Moderator/Admin
approval; the last approved version remains available during review. Submissions
must retain their original challenge revision. See
[challenge studio](docs/challenge-studio-implementation.md).

The owner approved evaluating every confirmed challenge submission, with at most
one active evaluation per user/challenge context and committed evaluation
continuing after the browser closes. Standard challenges have three attempts per
user across all published revisions. Each weekly event will have its own separate
three-attempt budget. Current
authored challenges offer free participation to verified Active Learners,
Contributors and Instructors; token, rank and prerequisite gates await their
supporting rules and modules. See [challenge submissions](docs/challenge-submission-implementation.md).

The owner selected Judge0 CE at https://judge0-ce.p.rapidapi.com but deferred
API-plan setup and credentials. Keep live execution disabled until the owner is
ready; use provider fakes and continue independent development in the meantime.
The owner approved free weekly participation with verified results first and
deferred XP/rank/KodeBit rewards. Weekly windows run from Sunday 00:00 to the next
Sunday 00:00 in Asia/Manila, inclusive start and exclusive end. See
[weekly event implementation](docs/weekly-challenge-plan.md). The owner approved
future Scheduled events while the current event is active, resolving E02's
no-active-event configuration precondition. Enforce one Active event and keep
each event immutable after its start.

The owner approved A06 self-profile editing for all five roles. Email/password
changes require the current password; username and first/last name use normal
validated edits. Changed email becomes Unverified and ends the session until A02
verification. Profile editing cannot directly change roles/status. Verified Active
Learners and Contributors may submit Instructor credentials, including a fresh
submission after rejection. Block duplicate Pending applications, preserve
credential versions/decisions, and grant Instructor only after the existing
Moderator/Administrator review. See [account editing](docs/profile-editing-implementation.md).

The owner explicitly deferred F01 paid token purchases until package prices,
currency and KodeBit quantities are decided. Keep purchases unavailable and
continue independent features. Track deferred work in
[the deferred-feature register](docs/deferred-features.md), including Judge0 setup,
paid admission and rewards; do not silently enable those scopes.

A07 archival is implemented for its SRS actors: Learner, Contributor and
Instructor. Keep profile/progress/content data intact, terminate sessions and
preserve approved A04 recovery. See [archival](docs/account-archival-implementation.md).
The owner approved requiring Archived users to reactivate through A04 before
authenticated deletion. The first deletion release covers accounts without any
authored modules/courses/challenges; creator deletion awaits retention/removal
rules. The owner approved waiting for Queued/Evaluating submissions to finish
before deletion removes submission data. See [the deletion plan](docs/account-deletion-plan.md).

For A09/G05 Contributor applications, the owner approved 500-character application
messages and reviewer feedback with matching storage, resolving the dictionary's
varchar(255)/500-character conflict. The owner approved requiring account age of
at least 30 days AND 25 distinct server-validated completed modules AND 50 distinct
passed coding challenges, with repeats counted once. Rejected Contributor
applicants may resubmit with prior decisions and credentials preserved. Permit
only one Pending role application (Contributor or Instructor) at a time. See
[the Contributor application plan](docs/contributor-application-plan.md).

The owner approved G03/G04 enforcement hierarchy: Moderators may suspend/reinstate
Learners, Contributors and Instructors; Administrators may also manage Moderators.
Block self-enforcement and Administrator targets in the first release, protecting
the last Administrator. This does not authorize G02 role or personal-field edits.
See [account governance](docs/account-governance-implementation.md) for implementation,
session revocation, audit/delivery records and PostgreSQL concurrency coverage.

The owner approved the first G02 role-editing scope: Administrators may appoint
Moderators from verified Active Learner/Contributor/Instructor accounts and remove
them to their recorded prior participant role. Preserve A09/A10 elevation workflows;
block self/Administrator changes and removal of existing Moderators with unknown
prior roles. Administrative personal-field editing remains a separate scope.
See [Moderator appointments](docs/moderator-appointments-implementation.md).

The owner approved G02 support corrections to username and first/last name on
verified Active participant/Moderator accounts when the user requests help, with
current Administrator password, explicit confirmation, audit, target-session
revocation and notice. Self/Administrator targets retain A06, and email/password
changes remain in owner A06/A04 flows. See
[support corrections](docs/support-profile-corrections-implementation.md).

The owner approved G06 staff withdrawal/restoration for Published/Archived modules,
courses and challenges: block all learner access including existing enrollees,
preserve content/revisions/progress/audit and finish committed evaluations. Keep a
separate staff block from owner archival; only staff restoration can remove it,
and creator edits/republication must retain it. See
[content withdrawal](docs/content-withdrawal-implementation.md).

The owner approved G08–G10 Administrator-managed game/quiz presets using existing
server-validated win rules, explicit Deferred rewards and no XP/KodeBit grants.
Updates preserve existing module instances; inactivation blocks new use while
retaining references. Use immutable preset revisions and module snapshots, never
uploaded executable rules. See [game presets](docs/game-presets-implementation.md).

The owner approved public reading/search of Active FAQs (B11), with management
restricted to verified Active Administrators. Initial predefined topics are
Getting started, Accounts, Playing and learning, and Creating content. Learning
module authentication remains unchanged. See [Help](docs/faq-implementation.md).

You are working on **Kody: Gamified Programming Learning Platform and Course Management System (K:GPLPCMS)**.

This repository is a real Laravel application intended for deployment. Treat every production-bound change as maintainable software, not as tutorial, demo, or throwaway coursework.

Optimize for, in order:

1. correctness and requirements traceability;
2. security and data integrity;
3. maintainability and testability;
4. observability and recoverability;
5. production readiness;
6. reasonable operational simplicity for a four-person student team.

Do not claim a requirement is complete merely because a page renders or a happy path works.

---

## 1. Sources of Truth

Kody is governed by:

- **Kody SRS v1.4** for required system behavior, use cases, actors, business rules, data constraints, and non-functional targets.
- **Current Kody SDD** for architecture, module ownership, integration boundaries, deployment model, concurrency, and persistence design.
- Approved later amendments to those documents.
- Explicit current developer instructions.
- Existing repository conventions where they do not conflict with the above.
- Official Laravel 13 conventions and package documentation.

Use this precedence when implementation guidance conflicts:

1. explicit current developer instruction;
2. approved SRS requirement;
3. approved/current SDD architectural decision;
4. existing repository convention;
5. official Laravel 13 behavior and framework conventions;
6. conservative engineering judgment.

Do **not** silently invent or reconcile business rules. If two approved requirements conflict, identify the exact sections/use cases, explain the consequence, implement only the unambiguous portion, and mark the unresolved item as a requirements decision.

Known inconsistencies may exist in the source documents, including availability targets, supported language lists, password-field values, archive-access behavior, and some use-case cross-references. Never bury a guessed resolution in code.

---

## 2. Verified Technology Baseline

The current repository uses:

- PHP `^8.4.1` (owner-approved on 2026-10-04; see
  [runtime baseline decision](docs/runtime-baseline-decision.md))
- Laravel Framework `^13.17`
- Laravel Boost `^2.2`
- Pest `^5.2`
- Laravel Pint
- Vite / npm
- PostgreSQL as Kody's canonical relational database

Do not replace PostgreSQL with MySQL/MariaDB because XAMPP bundles them.

### Local development

XAMPP is permitted only as a local Apache/PHP convenience.

Local development must remain environment-independent:

- PostgreSQL runs separately as the application database.
- Apache, if used, must expose Laravel's `public/` directory, never the repository root.
- `.env` is local-only and must never be committed.
- sandbox/test credentials must be used for external services.
- no application code may depend on XAMPP-specific paths.

### Production target

Unless the approved SDD changes, design for:

- Linux on AWS EC2;
- PostgreSQL on AWS RDS;
- S3 for applicable object/static storage;
- HTTPS;
- production PHP runtime with PHP-FPM;
- Nginx or another explicitly approved production web server;
- supervised Laravel queue workers;
- Laravel scheduler;
- centralized logs/monitoring;
- encrypted managed backups.

Never deploy XAMPP to production.

---

## 3. Architecture

Kody is a **layered client-server modular monolith with asynchronous/event-driven integration where appropriate**.

Do not convert it to microservices without an approved architecture change.

The logical layers are:

1. Presentation Layer
2. Application and Business Logic Layer
3. Data Access Layer

The seven functional modules are:

1. Account and Authentication Management
2. Content Management
3. Coding Challenge Management
4. Gamification and Rewards
5. User Interaction and Engagement
6. Transaction and Earnings Management
7. Administration and Governance

These are logical modules inside one Laravel deployment.

Respect module ownership. A module must not duplicate another module's business rules simply because the same tables are reachable.

Examples:

- User Interaction requests challenge submission from Coding Challenge Management.
- Gamification requests KodeBit credits through Transaction and Earnings Management.
- Controllers do not calculate balances or rewards directly.
- External API code does not mutate unrelated domain state.

---

## 4. Laravel Implementation Rules

Prefer Laravel conventions unless the SDD explicitly requires otherwise.

Keep controllers thin. A controller should normally:

- authorize;
- validate or consume a Form Request;
- invoke an application/service/action;
- return a response.

Use:

- Form Requests for meaningful validation;
- Policies/Gates for object-level authorization;
- middleware for broad request concerns;
- service/action classes for non-trivial use cases;
- Eloquent for persistence without turning models into god objects;
- queued Jobs for asynchronous work;
- Events/Listeners where they reduce coupling without hiding critical transactional behavior;
- dedicated adapters/services for external integrations.

Avoid:

- giant controllers or models;
- business logic in Blade/templates;
- duplicated authorization checks;
- duplicated balance/reward algorithms;
- direct `env()` access outside config files;
- arbitrary global state;
- external API calls scattered across modules;
- unnecessary patterns added only for architectural appearance.

Repositories are optional. Introduce them only when they create a real abstraction benefit or the final SDD explicitly requires them.

---

## 5. External Integrations

Approved integrations are:

- Google Identity / OAuth / OpenID Connect
- Judge0
- SendGrid
- Xendit

Each provider must sit behind a dedicated integration boundary.

Business logic must not depend directly on provider-specific response shapes throughout the application. Translate provider responses into internal DTOs/value objects/results.

Every integration must define:

- timeout behavior;
- validation of provider responses;
- sanitized logging;
- retry behavior where safe;
- idempotency where effects matter;
- sandbox/test configuration;
- production configuration;
- secret isolation.

Never expose provider credentials to frontend JavaScript.

External providers must never receive direct database access.

---

## 6. Authentication, Authorization, and RBAC

Required roles:

- Learner
- Contributor
- Instructor
- Moderator
- Administrator

Do not treat hidden UI as authorization.

Enforce server-side authorization for every privileged route and resource action, including:

- content ownership;
- challenge management;
- moderation;
- role elevation;
- account suspension/reinstatement;
- reports;
- game configuration;
- financial information;
- payouts;
- administrative updates.

Re-check authorization immediately before sensitive state changes.

Never trust client-submitted values for role, ownership, price, balance, reward amount, approval state, or authorization status.

Sensitive administrative actions must be auditable.

---

## 7. PostgreSQL and Schema Integrity

PostgreSQL is canonical.

Use database constraints where they protect invariants:

- foreign keys;
- unique constraints;
- non-null constraints;
- check constraints where practical;
- indexes;
- exact numeric types;
- timestamps;
- lifecycle/status fields.

Do not rely only on application validation when the database can enforce correctness.

All schema changes require Laravel migrations.

Production-bound migrations must:

- work on an empty database;
- be safe to execute during deployment;
- avoid destructive operations unless explicitly approved;
- use expand-and-contract changes when compatibility matters.

Never use manual production schema changes as the normal process.

Seeders must be deterministic and safe. Never seed production credentials.

Factories are for automated tests and development fixtures.

---

## 8. Transactions and Concurrency

Any business action that changes multiple related records must be atomic.

Use Laravel database transactions and PostgreSQL locking/version checks where required.

### KodeBits spending

The following must succeed or fail together:

- validate eligibility/cost;
- lock/check balance;
- deduct balance;
- write the transaction/ledger record;
- grant access.

A KodeBit balance must never become negative.

### Payment purchases

Only a verified provider callback/webhook may credit purchased KodeBits.

A browser redirect is never proof of payment.

### Enrollment with tokens

Token deduction and enrollment/access grant must be one logical transaction.

### Rewards

Use durable uniqueness/idempotency constraints to prevent duplicate reward grants.

### Payouts

Prevent concurrent requests from spending the same publisher earnings. Reserve or lock amounts appropriately. Do not finalize earnings deduction before the verified-success condition required by the SRS/SDD.

### Moderation and content edits

Prevent stale writes and duplicate moderation. Use record versions, state checks, locks, or equivalent concurrency control.

Never implement concurrency-sensitive logic as an unprotected "read count -> decide -> insert/update" sequence.

---

## 9. Financial Ledger and Idempotency

Do not treat a mutable balance column as the only financial history.

Maintain durable ledger/transaction records for:

- purchases;
- KodeBit usage;
- reward credits;
- publisher earnings;
- contributor credits;
- payouts;
- approved reversals/adjustments.

Financial events should carry:

- unique internal ID;
- user/owner;
- type;
- exact amount;
- status;
- external reference when applicable;
- idempotency reference when applicable;
- timestamps.

Use integer minor currency units or exact decimals for money. Never use floating point.

### Xendit webhook handling

For each payment/payout webhook:

1. verify/authenticate the callback using the provider's supported mechanism;
2. validate required fields;
3. identify the internal transaction;
4. compare expected amount/currency/reference/status where relevant;
5. acquire appropriate lock;
6. detect already-processed provider event/reference;
7. apply the state transition exactly once;
8. commit;
9. return the appropriate provider response.

Repeated callbacks must be harmless.

---

## 10. Code Execution / Judge0

Never execute untrusted learner code directly in:

- the Laravel process;
- the EC2 application host;
- the database host;
- Kody's internal application environment.

Use Judge0 or another approved isolated execution provider.

Enforce server-side:

- supported language validation;
- challenge-language restrictions;
- source-size limits;
- time limits;
- memory limits;
- test-case integrity;
- submission limits;
- ownership checks;
- deterministic result handling where required.

Hidden test cases must never be exposed to normal clients.

Treat provider output as untrusted and escape/sanitize before rendering.

Submission records must exist durably before asynchronous evaluation begins.

---

## 11. Submission Limits

Where the SRS limits attempts, enforce limits server-side and concurrency-safely.

Do not use an unprotected:

`count attempts -> if under limit -> insert`

sequence.

The insertion/count invariant must survive simultaneous requests.

Each evaluation job must refer to one durable submission. Retries must not create duplicate logical evaluations, rewards, or rankings.

---

## 12. Queues and Background Work

Use Laravel queues for non-blocking work where appropriate, including:

- transactional email;
- notifications;
- Judge0 evaluation/result processing;
- leaderboard calculation;
- weekly aggregation;
- other slow external calls.

Jobs must be:

- retry-safe;
- idempotent when effects matter;
- bounded by timeout;
- configured with sensible retry/backoff behavior;
- observable through logs/failure records.

Where a queued effect depends on a committed database change, use dispatch-after-commit or another reliable pending-work/outbox strategy.

Production queue workers must run under Supervisor/systemd or an approved equivalent and must be gracefully restarted after deployment.

Potential queue separation, when justified:

- `critical`
- `payments`
- `code-execution`
- `notifications`
- `leaderboards`
- `default`

Do not add queue complexity without an operational reason.

---

## 13. Scheduler

Use Laravel's scheduler for scheduled application work.

Typical Kody responsibilities include:

- weekly challenge lifecycle;
- weekly result aggregation/publication;
- retryable maintenance;
- housekeeping.

Store canonical timestamps consistently, preferably UTC. Convert to the required business/user timezone for presentation and schedule semantics.

Prevent duplicate weekly operations with overlap/single-server/distributed-lock protection when appropriate.

Do not create ad hoc host cron jobs for every Laravel task when one scheduler entry can own the schedule.

---

## 14. Notifications

Non-critical delivery must not block user requests unnecessarily.

Queue email delivery.

A successful purchase, payout, reward, or access grant must not roll back only because notification delivery fails.

Record delivery state where required, retry safely, and prevent duplicate critical messages from job retries.

---

## 15. Security Baseline

Treat OWASP web application risks as baseline engineering concerns.

Protect against:

- SQL injection;
- XSS;
- CSRF;
- broken access control / IDOR;
- mass assignment;
- brute force;
- credential leakage;
- session fixation;
- insecure file upload;
- path traversal;
- webhook spoofing/replay;
- unsafe redirects;
- SSRF where external URL functionality exists;
- excessive data exposure.

Use Laravel's built-in security mechanisms rather than bypassing them.

### Passwords

Use Laravel-supported secure password hashing.

Never reversibly encrypt passwords.

Never log passwords, reset tokens, OAuth secrets, session IDs, API tokens, provider signing secrets, or payment credentials.

### Sessions

Production configuration must use HTTPS-appropriate cookie settings, including Secure and HttpOnly and an intentional SameSite policy.

Regenerate sessions after authentication.

Invalidate sessions when required by recovery/security-sensitive changes.

### Rate limiting

Apply server-side rate limits to sensitive endpoints, including:

- login;
- registration;
- verification resend;
- account recovery;
- code submission;
- expensive actions;
- privileged endpoints;
- webhook endpoints where compatible with provider delivery behavior.

### File uploads

Validate actual content/MIME, extension/type, size, authorization, filename/path handling.

Generate server-side storage names.

Instructor credentials and other private files must never be publicly exposed.

### Production errors

Production must use `APP_DEBUG=false`.

Do not expose stack traces, SQL errors, credentials, internal paths, or infrastructure details to end users.

---

## 16. Data Protection

Follow the SRS requirements for applicable privacy/data-protection principles, including RA 10173 and applicable GDPR principles.

Production traffic must use HTTPS.

Use AWS/RDS/S3 encryption at rest where applicable and application-level encryption for specific sensitive fields when the requirements demand it.

Do not claim encryption/compliance unless the deployed configuration actually provides it.

Account deletion/anonymization must preserve required referential/audit integrity without retaining unnecessary PII.

---

## 17. Logging and Auditing

Separate operational logging from durable audit history.

Operational examples:

- exceptions;
- provider timeout;
- worker failure;
- database error.

Audit/security examples:

- role changes;
- approvals/rejections;
- moderation;
- suspensions/reinstatements;
- content publication;
- payment/payout events;
- privileged configuration changes.

Use structured context when useful:

- request/correlation ID;
- user ID;
- transaction/operation ID;
- provider reference;
- job ID.

Never log secrets, full credentials, hidden test cases, or raw sensitive authentication material.

---

## 18. Performance and NFRs

Implement toward the SRS targets, including:

- code execution feedback within the stated normal-operation window;
- core page-load targets;
- gamification/dashboard update targets;
- the defined concurrent-user requirement;
- asynchronous handling of non-critical work.

Do not claim compliance without measurement.

Avoid obvious N+1 queries, unbounded result sets, and missing indexes.

Use pagination, eager loading, indexes, caching where safe, and asynchronous processing where justified.

Never cache authorization-sensitive or financial state in a way that can expose another user's data or stale access decisions.

---

## 19. Testing Policy

No feature is complete without appropriate automated tests.

Use Pest/Laravel testing conventions already installed in the repository.

### Unit tests

Use for deterministic domain rules such as:

- rewards;
- rank/tie calculations;
- token calculations;
- eligibility;
- state transitions.

### Feature tests

Cover:

- routes;
- validation;
- authentication;
- authorization;
- role restrictions;
- ownership restrictions;
- successful workflows;
- alternate/error paths.

### Integration tests

Use fakes/mocks in CI for external providers.

CI must not call production Google, Judge0, SendGrid, or Xendit APIs.

### Database-sensitive tests

Use PostgreSQL behavior for logic involving:

- locking;
- constraints;
- transaction semantics;
- PostgreSQL-specific JSON/query behavior.

Do not rely solely on SQLite for those cases.

### Security/regression tests

Include tests for:

- unauthorized and cross-user access;
- suspended-account restrictions;
- role escalation prevention;
- duplicate webhook delivery;
- duplicate reward prevention;
- transaction rollback;
- submission attempt concurrency invariants.

Every fixed reproducible defect should receive a regression test when practical.

---

## 20. Requirements Traceability

Associate meaningful implementation work with SRS use-case IDs:

- A01-A10
- B01-B11
- C01-C07
- D01-D09
- E01-E06
- F01-F05
- G01-G13

Use commit/PR descriptions, test names, issue references, or RTM-friendly documentation.

For completed work, report:

- requirement/use case;
- implementation;
- migrations;
- authorization impact;
- tests;
- security considerations;
- deployment/config impact;
- unresolved requirements decisions.

---

## 21. Git and Pull Request Discipline

Use short-lived branches.

Recommended names:

- `feature/...`
- `fix/...`
- `refactor/...`
- `docs/...`
- `chore/...`

Keep pull requests reviewable and focused.

Do not mix unrelated features into one PR.

Production-bound changes should merge through pull requests with required CI checks passing.

Never solve a failing pipeline by disabling the check unless the check itself is proven incorrect and the correction is documented.

---

## 22. CI Requirements

GitHub Actions is the intended CI/CD platform.

CI should run on pull requests and relevant pushes.

A production-ready CI pipeline must perform, as applicable:

1. checkout exact commit;
2. set up supported PHP and extensions;
3. provision PostgreSQL;
4. install Composer dependencies deterministically;
5. run `npm ci` when frontend dependencies are required;
6. run Pint/style/static checks configured by the project;
7. run dependency/security audits according to project policy;
8. create CI-safe environment configuration;
9. generate application key;
10. run migrations on a clean PostgreSQL database;
11. run automated tests;
12. build frontend assets;
13. produce/identify a traceable artifact/release tied to the Git SHA.

Do not use production credentials in CI.

Do not hide test failures with `continue-on-error`.

---

## 23. Production CD

Production deployment is separate from ordinary PR validation.

Preferred production flow:

- approved commit reaches `main` or an immutable release tag;
- GitHub protected production environment approval;
- GitHub Actions authenticates to AWS using OIDC/short-lived credentials where possible;
- one production deployment runs at a time;
- deploy an immutable release;
- run safe migrations;
- switch release atomically;
- restart/reload runtime services;
- gracefully restart queue workers;
- verify scheduler/runtime health;
- run health check and smoke tests;
- preserve release metadata.

Avoid long-lived AWS access keys when OIDC is available.

Apply least privilege to deployment IAM roles.

Never run `migrate:fresh`, destructive seeders, or database resets in production.

---

## 24. Release and Rollback Model

Prefer release directories rather than editing live production code in place.

Conceptual structure:

```text
/var/www/kody/releases/<release-id>
/var/www/kody/shared
/var/www/kody/current -> releases/<release-id>
```

Persistent user data must not live inside disposable release directories.

A deployment should conceptually:

1. acquire deployment lock;
2. identify immutable Git SHA/build;
3. create release directory;
4. place tested build;
5. attach secure config/shared storage;
6. verify permissions;
7. run preflight;
8. run `php artisan migrate --force`;
9. build appropriate Laravel caches;
10. switch active release atomically;
11. reload PHP/web runtime;
12. gracefully restart queue workers;
13. verify scheduler;
14. run health check and smoke tests;
15. mark deployment successful;
16. retain a limited number of previous releases.

### Rollback

Application rollback should normally switch to a previous known-good release and reload runtime services.

Do **not** automatically run `php artisan migrate:rollback` as part of an application rollback.

Database rollback is a separate, explicitly assessed operation because down migrations may destroy data.

Prefer expand-and-contract migrations so the previous release remains compatible during rollback.

---

## 25. Secrets and Configuration

Never commit:

- `.env`;
- OAuth secrets;
- Judge0 credentials;
- SendGrid credentials;
- Xendit credentials;
- database passwords;
- AWS keys;
- encryption/signing secrets.

Use environment-specific secret management such as AWS Systems Manager Parameter Store or Secrets Manager for production.

Keep secrets separate between local, CI, staging, and production.

Access environment values through Laravel config. Do not call `env()` directly from ordinary application classes.

Production must support config caching.

---

## 26. Health, Monitoring, Backups

Provide a lightweight health endpoint that exposes no sensitive configuration.

Where practical, distinguish liveness from readiness/dependency health.

Monitor at least:

- HTTP error rate and latency;
- exceptions;
- queue depth/failed jobs;
- worker latency;
- CPU/memory/disk;
- database pressure/connections;
- payment/webhook failures;
- Judge0 failures;
- mail failures where available;
- scheduled-job failures.

Do not claim 99.x% availability unless monitoring actually measures it.

Implement the SRS backup requirement at minimum and document restoration.

A backup strategy is incomplete until restore testing has been performed.

---

## 27. S3 / File Storage

Use Laravel's filesystem abstraction.

Production code must not depend on Windows/XAMPP paths.

For S3:

- keep private objects private;
- use temporary/signed access where needed;
- use least-privilege IAM;
- validate uploads;
- define lifecycle/retention policy as appropriate;
- never store secrets or private credentials in public buckets.

---

## 28. Production Web Server

The production web server must expose only Laravel's `public/` directory.

Do not serve:

- `.env`;
- `.git`;
- source/config directories;
- private storage;
- internal files.

Force HTTPS.

Configure secure headers and realistic request/upload limits.

Do not use Laravel's development server for production traffic.

---

## 29. Implementation Procedure for Every Task

For every feature/change:

### Step 1 — Resolve requirement

Identify:

- SRS ID;
- actors;
- preconditions;
- postconditions;
- basic flow;
- alternate flows;
- exceptions;
- business rules;
- data/DDT constraints.

### Step 2 — Resolve module ownership

Identify which of the seven modules owns the operation and which modules it may call.

### Step 3 — Inspect existing code

Before creating abstractions, inspect relevant:

- routes;
- controllers;
- requests;
- models;
- migrations;
- policies;
- actions/services;
- jobs;
- events/listeners;
- tests;
- config.

Do not duplicate an existing abstraction.

### Step 4 — Design

State briefly:

- files/components affected;
- schema impact;
- authorization;
- transactions/concurrency;
- jobs/events;
- external integrations;
- tests.

### Step 5 — Implement

Write production-quality code. Do not leave pseudo-code where working implementation is requested.

### Step 6 — Test

Run focused tests, then the required broader test suite.

### Step 7 — Verify

Run applicable formatting/static checks, migrations, tests, and frontend build before calling work production-ready.

### Step 8 — Report

Report:

- requirement/use case;
- changed files;
- migrations;
- tests;
- verification results;
- security/deployment impact;
- unresolved decisions.

---

## 30. Coding Standards

Use Laravel/PHP conventions, clear naming, cohesive methods, and supported typed signatures.

Comments should explain *why*, invariants, or unusual constraints—not obvious syntax.

Do not leave production-bound:

- `dd()`;
- `dump()`;
- `var_dump()`;
- unexplained `TODO`/`FIXME`;
- temporary bypasses;
- hard-coded credentials;
- dead code.

Do not fabricate Laravel APIs, package methods, provider fields, or config keys. Inspect the installed version/documentation before relying on them.

When adding a package:

1. justify why it is needed;
2. verify Laravel/PHP compatibility;
3. add it through dependency management;
4. commit lock-file changes;
5. configure it securely;
6. test it.

Prefer Laravel core capabilities where sufficient.

---

## 31. Definition of Done

A feature is complete only when applicable:

- SRS behavior is implemented;
- SDD/module boundaries are respected;
- authorization is enforced;
- validation is implemented;
- database constraints/migrations exist;
- transactions/concurrency risks are handled;
- external failures are handled;
- tests cover main, alternate, and security-sensitive paths;
- CI passes;
- no secrets are committed;
- logging is safe;
- deployment impact is understood;
- traceability is updated.

A release is deployable only when:

- CI passes;
- migrations are reviewed;
- release is tied to an immutable Git SHA;
- configuration/secrets exist;
- backup/recovery expectations are satisfied;
- rollback is understood;
- queue workers/scheduler are configured;
- health checks exist;
- deployment smoke tests pass.

---

## 32. Initial Engineering Order

The product amendment above changes product sequencing: establish a playable
game hub and reusable template contract now, then layer creator learning content,
verified progression and monetization over it. Maintain the technical dependencies
below for privileged publishing, evaluation and financial effects. Do not wait
until the final gamification phase to make the user experience game-first.

Unless project management explicitly changes sequencing, prefer this dependency order:

1. engineering foundation: PostgreSQL, environments, CI, testing, RBAC conventions, audit conventions;
2. Account and Authentication (A01-A10);
3. administration primitives needed by role/content governance;
4. Content Management (D01-D09);
5. Coding Challenge Management (C01-C07);
6. relevant User Interaction flows (B);
7. Judge0 submission/evaluation integration;
8. Transaction and Earnings Management (F01-F05);
9. Gamification and Rewards (E01-E06);
10. remaining Administration/Governance/reporting (G).

This is an engineering dependency order, not permission to alter SRS scope.

---

## 33. Core Principle

Build Kody so another engineer can understand, audit, deploy, recover, debug, and prove traceability back to the SRS.

Prefer:

- explicit correctness over cleverness;
- database-enforced invariants over assumptions;
- idempotent operations over "this should only happen once";
- reversible deployments over manual production edits;
- measured performance over claims;
- documented requirements decisions over hidden guesses.

The target is not merely to make Kody work under XAMPP.

The target is to **develop locally with a reproducible engineering workflow, continuously verify every production-bound change, and deploy the same Laravel application safely to its approved cloud architecture without redesigning it at deployment time.**
