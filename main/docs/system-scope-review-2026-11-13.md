# Kody system scope and complexity review

Reviewed 13 November 2026 against checkout `bda248c`, the current implementation map, approved amendments, source code, test infrastructure and operating examples. This report proposes development priorities; it does not adopt new business rules, remove approved requirements or activate providers.

## Recommendation

Pause feature expansion and finish a smaller, coherent learning release. Preserve the modular monolith, existing security controls, immutable history and financial safeguards. Concentrate the next work on dependency maintenance, correct worker configuration, one clear learner journey, reviewed teaching material and release evidence.

The main complexity is the number of product responsibilities and interacting policies, rather than an obviously unsuitable framework. Kody combines a game platform, course system, publishing workflow, credential review, competition system and financial marketplace. Each is plausible individually; operating all of them together is a substantial responsibility for a four-person team. More features would currently add more value to the maintenance backlog than to the learner experience.

A free learning pilot is the recommended first release boundary. It would still require reliable email, legal publication/acceptance and staged operational checks. It would not constitute completion of the full SRS, or establish its capacity and availability targets. Keep challenge execution and financial activation as separately accepted releases.

## Evidence and review limits

Inspected all seven modules through their architecture/implementation records and representative controllers, services, policies/test coverage, game entry points, Blade journeys, provider gates, CI and operating examples. This is a system-wide scope and risk review, not a line-by-line security audit or a new formal certification against every original SRS/SDD clause. Requirement references use the repository's approved amendment and acceptance records.

Fresh checks in this checkout:

| Check | Result |
| --- | --- |
| Frontend/game tests with explicit PHP 8.4.27 fixture executable | 64 passed; zero failed |
| Composer metadata validation | Passed |
| Installed Composer platform requirements | Passed |
| Pint formatting | Passed |
| npm dependency audit | No known vulnerabilities reported |
| Installed and locked Composer audits | Failed: ten advisories across seven packages; six packages are runtime dependencies and one is development-only |

No application database was reset, provider contacted for live operation, or application code changed. Dependency advisory registries were queried. Backend/database, browser, restore and production-build suites were not rerun for this report. The previous refactoring record reports 1,328 backend tests and separate cached/browser/restore evidence; those remain historical results, not fresh passes. UX findings below come from source and existing acceptance records, not a new observed usability session. Current target-environment publications, infrastructure and live services were not inspected.

## Findings to address first

### R01 — Dependency security maintenance is now required

**Priority: before another release candidate. Confirmed audit failure.**

The locked packages currently reported by Composer are:

| Package | Locked version | Scope | Advisory count |
| --- | --- | --- | --- |
| phpseclib/phpseclib | 4.0.1 | Runtime | 1 |
| symfony/filesystem | 8.1.6 | Runtime | 1 |
| symfony/http-foundation | 8.1.8 | Runtime | 4 |
| symfony/http-kernel | 8.1.8 | Runtime | 1 |
| symfony/mime | 8.1.7 | Runtime | 1 |
| symfony/routing | 8.1.8 | Runtime | 1 |
| symfony/yaml | 8.1.8 | Development | 1 |

The advisories concern key-parser CPU exhaustion, path handling, request/URL/proxy behavior, MIME header injection and YAML parser exhaustion. An affected dependency version does not prove that every advisory is exploitable through Kody: some describe Symfony features or configurations that Laravel may not use. Do not label all ten as demonstrated application vulnerabilities or ignore them because tests pass.

Use a focused, compatible dependency update, review applicability and release notes, and rerun audit, backend/cached-route tests, frontend tests, builds and affected browser journeys. Do not suppress the audit gate to restore green CI. The existing CI audit would encounter the new advisories if rerun against this lock; no remote CI run was inspected here.

References: [Composer lock](../composer.lock), [HTTP Foundation host advisory](https://github.com/symfony/symfony/security/advisories/GHSA-5mx8-mwrp-5693), [filesystem advisory](https://github.com/symfony/symfony/security/advisories/GHSA-6prc-h956-hxjq), [phpseclib advisory](https://github.com/phpseclib/phpseclib/security/advisories/GHSA-w3p9-g4p2-h3rr).

### R02 — The supplied worker configuration does not consume challenge evaluations

**Priority: fix independently now; required before Judge0 activation. Confirmed configuration mismatch. C03/C04/E03.**

[ChallengeSubmissions](../app/Services/Challenges/ChallengeSubmissions.php) pushes jobs to `code-execution`. The [Supervisor example](../ops/supervisor/kody-worker.conf.example) listens to `payments,notifications,default`, excluding that queue. README's general worker command listens only to the configured default queue, which also omits finance queues under the default configuration.

Following the supplied Supervisor example alone would strand evaluations. The expiry process can eventually mark them unavailable; it cannot perform the missing evaluation. The default-queue [heartbeat](../app/Console/Commands/OperationsHeartbeat.php) could still succeed, so “worker recent” is not proof that every workload is serviced. This is a source/configuration finding, not evidence of a current deployed outage; live execution remains deferred.

Document and supervise consumers for every queue actually produced. A dedicated execution worker is a reasonable small separation because polling should not delay verification mail or financial notices. Verify a synthetic evaluation reaches a terminal state through the real queue runner with a fake provider; retain no-attempt-on-disabled-provider checks. Add queue-specific age/processing evidence and keep heartbeat probes bounded. Preserve timeout/retry relationships and committed jobs after browser closure.

### R03 — Proxy trust is broader than the documented deployment boundary

**Priority: before any publicly reachable environment. Confirmed configuration; exposure depends on ingress.**

[Bootstrap](../bootstrap/app.php) uses `trustProxies(at: '*')` with a “Remove later” comment. Wildcard trust can be valid behind an enforced, header-sanitizing proxy, but that network boundary is not demonstrated in this checkout. If clients can reach the origin or supply forwarded headers through ingress, request IP/scheme/host interpretation can be misleading, affecting IP-based throttles and generated URLs.

Choose trusted proxy/header settings from the actual deployment topology, restrict direct origin access where applicable, and validate allowed hosts at the appropriate application/ingress boundary. Test direct spoofed forwarded headers, legitimate proxy traffic and generated account links. Treat the new request/URL dependency advisories separately; fixing proxy configuration does not substitute for patching dependencies.

### R04 — Public-launch legal acceptance remains an actual feature gap

**Priority: before public registration. Confirmed implementation gap. A01.**

[Registration validation](../app/Http/Requests/Account/RegisterAccountRequest.php), [account creation](../app/Actions/Account/RegisterAccount.php) and the [form](../resources/views/account/register.blade.php) have no Terms acceptance or accepted-version persistence. Draft text is explicitly unpublished.

Resolve operator/postal details, eligibility/geography, retention and reviewed text first. Then implement immutable published versions, an unchecked required Terms acknowledgment, server validation of the current version and atomic minimal acceptance evidence. Address existing-user/material-change handling explicitly. Keep Privacy notice and optional consent distinct. No automatic purge or assumed age policy should be introduced from this review.

Acceptance: missing acknowledgment, stale/forged versions and rollback are covered; the accepted text is retrievable. See [legal publication requirements](legal/review-and-publication.md).

### R05 — Release readiness needs evidence beyond configuration flags

**Priority: before the applicable pilot/public release. Known verification gap.**

[LaunchReadiness](../app/Console/Commands/LaunchReadiness.php) checks configuration, including a staging-attestation boolean. It does not verify current legal publication, curriculum ownership, queue consumption, recent restore success or alert delivery. Keep its documented configuration-only meaning.

Use one small release evidence index containing environment, Git SHA, evidence date, reviewer and scope. Connect it to staged HTTPS/private storage, worker/scheduler checks, named primary/backup responders, alert delivery, restore and rollback drills. Verify the first production Administrator and lost-admin recovery procedure: the existing [local provisioning command](../app/Console/Commands/CreateLocalAdministrator.php) is intentionally not a production bootstrap mechanism.

Do not treat local concurrency tests or a small pilot as proof of the unchanged 500-concurrent-user, latency or 99.9% availability targets. See [acceptance audit](requirements-acceptance-audit.md) and [operations](deployment-operations.md).

### R06 — The learner has competing progression systems and next actions

**Priority: next UX batch. Confirmed interface structure; user impact needs a pilot. B01/B02/B05/E05.**

The main entry is the 80-floor Tower, but the [overview](../resources/views/learning/hub.blade.php) still displays the older three-level ladder, “Your ladder” statistics and starter missions. [LearnerMissions](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/blob/bda248c6b436285f59a0a06228f39169eeed9424/main/app/Services/Engagement/LearnerMissions.php) directs daily/continue actions to those starter lessons. The HUD, meanwhile, shows Tower counts. A learner can reasonably wonder which progress display matters.

Make the selected Tower track the primary continue action. Keep the old starter lessons accessible as optional lessons or a secondary completed introduction. Preserve their IDs, clearances, XP history and routes until compatibility is deliberately handled. Relabel the three-level statistics clearly if they remain visible; do not migrate or invent clears.

Tower clears, XP/rank, daily streaks and KB are different concepts. The main Tower grants no extra XP/KB, while first article completion can grant 40 XP under the approved amendment. Explain those facts at relevant actions instead of making users infer them from several counters. Any reward-policy change needs a separate decision.

### R07 — Navigation exposes too many overlapping destinations

**Priority: next UX batch. Confirmed structure; proposed simplification.**

The [header](../resources/views/layouts/app-header.blade.php), [rail](../resources/views/layouts/workspace-nav.blade.php), account menu and overview repeat Play/Tower/Overview, Learn/catalog/Courses/My courses, Updates and account destinations. Multiple valid entry points increase decisions and make naming inconsistent.

Proposed learner navigation: **Play, Learn, My progress, Help**, with courses/lessons inside Learn, practice inside Play, and reviews/history inside My progress. Account/security remains in the account menu. This is information architecture, not permission expansion or a requirement to delete existing URLs. Keep creators' authoring and staff operations role-focused, and preserve deep links/current-access checks.

Show disabled execution/payment explanations where users encounter the relevant content. Avoid making unavailable providers a prominent recurring destination on a free learning journey. Existing balances, receipts, refunds, history and support routes must remain reachable.

## Scope disposition

These are recommended release boundaries, not permission to delete features or override the SRS.

| Area | Recommendation | Complexity driver / release condition |
| --- | --- | --- |
| Tower and three existing tracks | Keep as the primary play path; pause more floors | Curriculum quality, historical revisions and ordered tracks already need sustained maintenance |
| Existing bounded games and quizzes | Keep; improve explanation and consistency | Browser/server parity, typed scenarios and immutable versions make every new mechanic expensive |
| Learner progress, review list and streaks | Keep; consolidate presentation | Several independent definitions must remain clear and truthful |
| Free lessons and one reviewed beginner course | Prioritize for the first learning pilot | Real editorial ownership and learner transfer matter more than more templates |
| Creator authoring and staff publication review | Keep; simplify defaults and task flow | Versioning, withdrawal, archival and retained dependencies are legitimate requirements |
| Instructor/Contributor roles | Retain approved roles and applications | Removing roles changes authoring/settlement policy; hiding complexity is safer than merging it |
| Coding challenges | Prepare content and queue reliability; activate separately | Judge0 plan/compiler mapping, latency, errors, attempt rules and cost require live verification |
| Weekly competitions | Later activation, after standard challenges work | Depends on execution plus scheduling, result publication, ties and reward funding |
| Wallet, paid content, payouts and refunds | Freeze expansion and defer live activation | Highest combined financial, operational, support and legal burden; preserve existing ledger code/history |
| Google sign-in | Optional later convenience | Password-based onboarding does not need it; linking/session protections must remain |
| Creator qualifications badges | Keep narrowly defined; avoid expansion | Manual review capacity and private proof/erasure obligations grow with uptake |
| Reports and exports | Keep current business definitions; defer more dashboards | Reconciliation correctness matters more than chart variety |
| Mascot, scenery and tours | Keep lightweight and optional; pause more animation | Readability, mobile performance, focus and reduced motion come first |

The Contributor path also depends on live challenge evaluation: eligibility requires 50 distinct passed challenges in addition to account age and modules. With Judge0 disabled, ordinary users cannot complete that part through Tower play. Explain this dependency and sequence activation accordingly. Do not relax eligibility or turn synthetic demonstration history into a public workaround.

## What should be removed, hidden or retired

1. **Remove duplicate primary calls to action and misleading labels.** Retire the old starter ladder's role as a competing homepage progression system. Keep retained history intact.
2. **Hide inactive sales promotion in the free pilot.** The [wallet](../resources/views/transactions/wallet.blade.php) currently renders package promotions even when checkout is unavailable. Prefer a compact availability message and a useful history view; keep financial remedies accessible.
3. **Remove obsolete interface copy and dead branches after checking references.** Examples include the overview's “four practice templates” wording despite later additions, README's old signup/remote-font descriptions, and the legacy account-type/instructor-field toggle in [app.js](../resources/js/app.js) after username-first signup. These are small maintenance tasks.
4. **Reduce default decorative competition with the task.** Keep the companion/tours opt-in and avoid adding another celebration, badge counter or navigation overlay to an already busy lesson.
5. **Do not add numeric ratings, public chat/forums, certificates, placement exams, AI tutors, more currencies, subscription plans or more game engines in the next scope.** These are not current omissions to fix; they would introduce new moderation, policy, assessment or operating obligations. Reconsider only against observed demand and a separate decision.

Do not remove audit records, immutable revisions, account-erasure integration, server validation, hidden-test protections, ledger provenance or authorization/version checks to make code shorter. Do not delete approved financial implementations merely because activation is deferred.

## UX improvements by journey

### First visit and registration

- Make “Start first trial” the unmistakable primary action; expose the existing sign-in/registration routes without making guests understand the whole platform.
- Give each track a short “what you should already know” description using the existing track metadata. Keep tracks independently enterable and never imply Expert certification.
- Preserve username/email/password-only signup, password-manager support and optional later profile details. Keep exact username/case rules visible; changing identity semantics is a separate policy/migration task.
- At verification, make delivery state, resend cooldown, code entry and support after exhausted requests easy to understand. Test the existing security limits; do not silently reset or weaken them.
- Guest progress is local practice. Make the sign-up transition honest about what will be saved; never silently convert browser clearances into authoritative achievements.

### Playing and learning

- Show one next action and a consistent lesson structure: objective, short explanation/example, readable program, practice, explicit Run, result and next step.
- Preserve bounded visible pseudocode and Step/Replay/Restart. Use clear labels for local practice versus saved completion; show retryable network failure without losing the learner's work.
- Add a simple optional list view of the Tower as an alternative to the ascending map. Reuse the same authorized/unlocked data; this improves scanning, keyboard access and orientation without another progression engine.
- Explain locked content at the point of use: required earlier lesson, withdrawn material, rank/access condition or provider unavailable. Offer the relevant valid next action.
- Keep technical terms consistent: lesson, course, practice, coding challenge, role, rank and reviewed qualifications should each mean one thing. Use playful flavor around clear action labels.
- Test long instructions, zoom, small screens, focus restoration, non-color cues and actual screen-reader behavior. Existing automated axe and browser-engine checks are useful but do not establish human accessibility or learning effectiveness.

### Creating and reviewing

- Keep “teach one idea” as the default. Put optional media, price/prerequisites and advanced scenario controls behind clear secondary sections, retaining full server validation.
- Add an unsaved-change warning to long authoring forms. A warning is smaller and safer than introducing another draft synchronization system. If autosave is later justified, it must save owned drafts only and retain version/conflict checks; it must never auto-publish.
- Make Draft/Pending/Approved/Rejected and Published/Archived/Withdrawn states explain their consequences and allowed next action. Preserve the separate revision and content lifecycles internally.
- Offer one staff inbox with type/status filters and links to existing specialized review pages, rather than merging their distinct decision services. Add age/owner indicators before inventing automatic approval or service guarantees.
- Keep role labels, creator-supplied profile claims and manually issued qualifications badges visibly distinct. Avoid adding new credibility symbols without defining evidence, reviewer work and revocation behavior.

### Support and operations

- Reuse the existing lesson-issue/contact links and support mailbox; improve the included lesson/revision reference before building a ticketing product. Let users inspect the message before sending.
- Separate content issues, account help and payment remedies in wording and routing. Email appeals cannot reinstate accounts or bypass review.
- Display operational states as unknown/recent/stale for the specific component. A configured provider, default worker heartbeat or successful `/ready` database query cannot establish end-to-end service health.

## Engineering simplification without a rewrite

**Keep Laravel/PostgreSQL and the modular monolith.** Current ownership boundaries, policies, atomic jobs and ledger logic are useful foundations. No evidence here justifies microservices, a generic repository framework or replacing the frontend stack.

| Focused improvement | Evidence / implementation direction | Guard |
| --- | --- | --- |
| Load page-specific JavaScript on demand | [app.js](../resources/js/app.js) statically imports authoring tools, games, Tower editor and payment polling into the shared entry. Measure compressed bundles and low-end-device parse time, then split the largest unrelated surfaces | Preserve mounting, keyboard behavior, navigation restoration and browser/server parity; no claimed speedup before measurement |
| Narrow public creator queries | [CreatorProfileController](../app/Http/Controllers/Account/CreatorProfileController.php) loads full published revisions for recent-publication lists | Select only card fields; preserve publication/withdrawal filters and private identity boundaries |
| Reduce repeated reporting reads | [ReportFinancials](../app/Services/Administration/ReportFinancials.php) performs separate cash-window sums | Profile and consider a filtered aggregate; preserve signed amounts, settlement dates, estimates and liabilities |
| Establish page-level query budgets | Existing dashboard/catalog regressions provide a starting point | Include shared chrome/HUD, representative history and realistic data sizes; avoid authorization-sensitive caches |
| Improve readability in touched code | Several services/templates combine multiple state changes or long UI expressions on one line | Expand meaningful steps and extract named read projections where helpful; avoid broad formatting churn or arbitrary file-size targets |
| Add incremental static analysis | No configured PHP static analyzer; Pint only checks style | Pin Laravel-compatible tooling and start with a useful bounded scope, without mass suppressions |
| Consolidate current decisions | Historical records can still say reading grants no XP, while the later article-XP amendment supersedes that | Maintain a concise current policy/status table with links, owner/date and supersession; preserve historical records |

Financial and publishing transaction bodies should not be mechanically split just because they are long. Their lock order, invariants, immutable pointers and failure paths are often the important structure. Share genuine duplication after characterization, particularly authorized read projections; keep module-specific business decisions explicit.

## Feature additions worth considering

The strongest additions are small supports for existing work:

| Proposal | Value | Scope boundary |
| --- | --- | --- |
| Optional accessible Tower list | Easier scanning and navigation | Presentation of existing server-owned state only |
| Authoring unsaved-change warning | Prevents lost work | No auto-publication or browser-to-server progress claims |
| Filtered staff review inbox | Reduces switching between queues | Existing policies and specialized decisions remain authoritative |
| Release evidence index and queue coverage check | Prevents false readiness and stranded work | No new general operations platform |
| Short human learning pilot | Reveals confusing wording, transfer failures and next-action problems | Research evidence; no mastery score or new achievement grants |

Already implemented: the personal Tower review list, optional retry feedback, step previews, creator bylines/credentials history, worker/scheduler probes and Firefox/WebKit learning smoke checks. Improve or validate these rather than commissioning duplicates.

Do not add general learner analytics before defining event meaning, participant notice, retention and reporting limits. An initial pilot can use observed task completion and voluntary interviews. Practice failures are not authoritative achievement data; raw code/answers should not be collected merely to count retries.

## Recommended development batches and acceptance

Effort below is relative scope, not a delivery estimate. Provider contracts, legal decisions and staging can dominate elapsed time.

| Order | Batch | Relative effort | Exit condition |
| --- | --- | --- | --- |
| 1 | Dependency update and advisory triage | Small–medium | Compatible lock, clean audit or explicit reviewed applicability disposition, required regression/build checks pass |
| 2 | Worker queue coverage and ingress/proxy configuration | Small–medium code; environment verification separate | Every produced queue has a consumer; synthetic execution completes; ingress/header checks pass |
| 3 | One learner continue action, navigation grouping and obsolete copy cleanup | Medium | First-time and returning learners can identify the next action; old history/access/rewards unchanged |
| 4 | One coherent published beginner path and human pilot | Medium plus editorial/research time | Named ownership, approved pinned revisions, correct/incorrect checks and valid continuation; observed issues recorded |
| 5 | Published legal versions and registration acceptance | Medium after required decisions | Reviewed facts/text, atomic server-owned acceptance, stale/rollback tests |
| 6 | Release evidence, real mail and staged operations | Large / external dependencies | Delivery, queue, alert, restore, rollback and relevant load/device evidence tied to the release |
| 7 | Standard Judge0 challenges, then weekly execution | Large / provider dependent | Verified languages/limits, outage handling, terminal queued results, attempt safeguards and cost measurements |
| 8 | Financial activation | Very large combined acceptance burden | Merchant/tax contracts, callback/retrieval agreement, reconciliation/refund/payout drills and operational ownership verified |

Batches 3–5 may overlap when work and decisions are independent. Avoid changing policy, financial mutations, game grading and navigation in one branch. Continue focused refactoring after each stable batch.

## Decisions to settle before more feature development

1. **Release objective:** a coherent free learning pilot is recommended; retain the broader SRS as a separate acceptance obligation.
2. **Primary learner journey:** Tower first, with courses and the retained starter lessons as supporting paths.
3. **Content responsibility:** designate who owns curriculum accuracy, publication review and corrections; code correctness cannot replace teaching review.
4. **Operating responsibility:** designate deployment/security maintenance, primary/backup incident response, moderation and future financial reconciliation ownership. Map responsibilities to actual team members without inventing identities.
5. **Expansion gate:** add a feature only when it addresses observed user need, has an owner and support plan, fits current permissions/history rules, and has an explicit release boundary.

The next milestone should be a secure, understandable and verifiable existing learning experience. More floors, game mechanics, animations or monetization options should wait until that milestone is demonstrated.
