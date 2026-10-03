# Coding-challenge studio — C01/C02/C05/C06

## Approved requirements decisions

On 2026-10-03 the owner approved:

- Draft/Published/Archived/Deleted challenge lifecycle with separate
  Draft/Pending/Approved/Rejected revision review states. Published means
  approved and archive-eligible. This resolves C01's Draft, C02's Approved and
  Published, C06's Active, and the dictionary's Pending/Approved-only lifecycle.
- Moderator/Admin approval for published replacements. The last approved
  revision remains available during review; future submissions retain the
  original challenge revision. This also resolves B06's Under Review exclusion
  for a pending replacement of an otherwise Published challenge.

C01 and the dictionary permit Contributor and Instructor authors. The approved
Instructor-only module authoring rule does not remove Instructor challenge
authorship. Version 1 challenge languages remain Python, Java and C++ only.
The approved source files themselves are unchanged; amendments live in AGENTS.md
and these Markdown records.

## Delivered workflows and scope

`/create/challenges` gives verified Active Contributors/Instructors an owned
studio. Drafts require a title, problem, language, Easy/Medium/Hard difficulty,
rules, input/output formats, CPU/memory settings and at least one test case.
Each save creates a new definition and ordered test snapshot. Input/output may
be intentionally empty; whitespace is preserved through HTTP normalization and
the browser textarea's initial-newline behavior. Sample/hidden flags are explicit.
Contradictory expected outputs for identical input are rejected. Schema/format
validation cannot prove semantic correctness without a reference execution;
reviewers must inspect the problem and test consistency manually in this slice.

The test editor adds/removes bounded checks and renumbers their form fields.
Server validation remains authoritative. Operational authoring safeguards allow
1–20 cases, 16,384 Unicode characters per input/output, CPU settings of 100–5,000
ms and memory settings of 16,384–262,144 KiB. PostgreSQL also caps each payload
at 65,536 bytes and positions at 20. These are conservative Kody authoring bounds,
not provider defaults or approved NFR measurements. Future provider integration
must validate/tighten them against the configured Judge0 service. Raising schema
caps requires a migration; configuration may tighten the limits.

Submitting a complete draft marks its latest revision Pending and prevents
edits while review is open. `/manage/challenges` lists pending revisions only
for Draft/Published challenges. Moderators/Admins view every case, including
hidden cases, and explicitly confirm their decision. Rejection requires feedback
and leaves the last publication untouched. Approval rechecks the author, changes
the publication pointer atomically, records reviewer/time/version/audit and
delivers a noncritical private in-app update. Author self-review is prohibited,
including after a role change. Rejected drafts can be corrected in a new revision.

The public `/challenges` catalog provides bounded literal title search and
predefined language/difficulty filters over only the current approved publication.
Guests can browse metadata; selection redirects to sign-in. The authenticated
problem preview selects explicit reviewed fields and SQL-filters public samples.
Hidden test data is never loaded into learner views. No code submission route,
execution grant, evaluator, provider call, submission record, grade, leaderboard
or reward is created by browsing or viewing a problem. Thus C02's governance
workflow is delivered, but its execution-pipeline postcondition and full B06/B07
scope remain incomplete. Category/tag/access filters are not invented.

C06 permits the owning Contributor/Instructor to confirm archiving a Published
challenge with its current version. The challenge leaves public browsing and
review queues, blocks future editing/public access, and retains all revisions,
test snapshots, publication pointer, notifications and audit records. The owner
can still view the archived studio. No restore or C07 permanent deletion exists.
There are no learner submissions yet; their future schema must reference stable
challenge revisions and preserve existing archive/history protections.

## Ownership, integrity and security

Challenge Management owns `ChallengePublishing`, policies and test definitions.
Administration owns audit recording; Notifications owns private in-app delivery.
Existing module/course review and notification conventions are reused without
granting content-module authorization to Contributors.

One additive migration creates `coding_challenges`, `coding_challenge_revisions`
and `challenge_test_cases`, plus durable notification uniqueness per recipient
and revision. Foreign keys preserve authors/revisions/tests; composite publication
keys prohibit pointers to another challenge. Schema checks enforce lifecycle,
review status, supported languages, difficulty, positive versions, execution
bounds, test positions/payload sizes and unique revision/position numbers.

Saves/submissions/archives lock the fresh account then challenge. Reviews lock
actor/owner accounts in ascending order then challenge. Policies, current session
fingerprints/expiry, record versions and current states are rechecked under lock.
Definitions, test cases, audit, publication and inbox delivery commit together.
Clients cannot set ownership, lifecycle, publication pointers or review state.
Every mutation uses CSRF and a separate throttle. Canonical numeric IDs reject
malformed PostgreSQL lookups; private views use no-store and escaped Blade text.

Hidden inputs/outputs are excluded from model serialization by default and are
rendered only for authorized creators/reviewers. Audit and inbox records contain
IDs, versions, decisions and titles, not case payloads. Database write exceptions
are caught after rollback: only SQLSTATE is logged, and a generic exception with
no original SQL/bindings attached is reported. Test payloads reject NUL bytes;
an after-validation check enforces bounds even on whitespace-only strings that
Laravel skips for ordinary nonimplicit validation rules. No external service or
untrusted code is executed by this feature.

Deploy with normal migrations, frontend build and Laravel caches. No new package,
secret, queue worker or scheduler change is required. Preserve these tables on
application rollback. Real Judge0, SMTP, production NFRs and deployment remain
unverified.

## Verification

Feature tests cover both author roles, ownership and moderation authorization,
complete definitions and invalid languages/limits/cases, exact whitespace and
empty payloads, hidden-test exclusion, escaped problem/feedback, explicit review
confirmation, rejection/replacement histories, stale/pending writes, role/session
rechecks, archiving, CSRF, predefined search filters, rollback and PostgreSQL
constraints. Real overlapping PostgreSQL edits/reviews prove one replacement
snapshot and one publication/audit/inbox update. Final local verification: 323
PostgreSQL tests with 2,287 assertions passed; 155 cached-configuration, route
and view tests with 1,215 assertions passed; all 13 existing frontend game tests
passed. Pint, Vite build and diff checks passed. All fourteen migrations execute
on clean disposable PostgreSQL databases through concurrency tests. Remote CI,
authenticated browser visual checks and deployment remain unverified.

## Submission follow-up

The owner resolved C03/C04/B07: every confirmed submission is evaluated, one
evaluation per user/challenge context may be active, and committed work continues
after browser closure. Three standard attempts span all publications; future
weekly events have separate three-attempt budgets. Current challenge participation
is free for verified Active Learners, Contributors and Instructors. See
[challenge submissions](challenge-submission-implementation.md) for durable records,
provider-faked evaluation and the remaining live-provider activation requirement.
