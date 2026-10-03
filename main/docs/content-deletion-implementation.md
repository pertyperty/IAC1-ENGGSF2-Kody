# D04 / D09 / C07 — Permanent creator content deletion

## Approved requirements and ownership

The SRS alternates between no active dependencies and no dependencies. The owner
approved preserving every retained dependency even after it becomes inactive.
Only verified Active owning Instructors may delete modules/courses; verified
Active owning Contributors or Instructors may delete coding challenges. Staff
governance access grants no creator deletion privilege. Draft, Published and
Archived definitions are eligible only when dependency-free.

Content Management owns module/course dependencies and definition cleanup through
`ContentDeletion`; Challenge Management owns challenge dependencies and test
cleanup through `ChallengeDeletion`. Their shared `OwnedContentDeletion` base
handles account/session, object authorization, confirmation, version checks,
transaction boundaries, common history and durable audit. No ledger, rewards,
external evaluation or moderation state is modified by deletion.

## Retention and user flow

Creator editors link to a dedicated permanent-deletion warning. The warning
shows retained dependency categories without exposing another learner's records.
It does not delete anything. Cancellation returns to the editor. Dependency-free
deletion requires explicit confirmation and the displayed record version.
Confirmed deletion redirects to the studio with a saved confirmation. Reloads
and duplicate requests for a removed definition return 404 without another audit.

Deletion is blocked by any staff withdrawal/moderation action, trusted opening,
reaction history (including removed-choice version rows), and:

- modules: every historic course revision assignment, validated module activity
  or module completion;
- courses: enrollment, which preserves its pinned revision and learner progress,
  or a retained enrollment audit after privacy erasure;
- challenges: participation and its submissions/evaluations, or any weekly event
  including ended/unavailable events, or retained submission audits after privacy
  erasure. Removing personal enrollment/submission rows does not erase these
  minimal historical dependencies.

Weekly planning audits also retain the former challenge reference after a future
event changes its selection; that history blocks deletion of the former source.

Published content offers the existing archive-confirmation route when blocked.
Previously archived content and blocked drafts retain their histories. Module
archival still blocks module learning; course archival still preserves existing
enrollees; staff withdrawal still requires staff restoration.

A deleted course removes only its own revisions/composition and keeps reusable
modules. A deleted challenge removes its own revisions/test cases; it cannot have
submission or weekly references. A deleted module removes its own revisions and
preserves referenced preset definitions. Ordinary creator review notifications
for the removed definition are cleaned up in the same transaction. Authoring and
deletion audits keep minimal durable actor/content-ID/version/status references;
they do not copy private content, titles, source or tests into new deletion logs.
Content rows are physically removed, rather than falsely claiming permanent
deletion through a hidden lifecycle status. IDs are not reused by this flow.

## Concurrency, schema and deployment

Existing foreign keys restrict deletion and existing tables supply all current
dependency checks. Additive migration
`2026_10_03_000026_index_audited_content_dependencies.php` indexes course enrollment
and challenge submission/weekly planning audit references. It changes no stored history or foreign
keys; rolling it back drops only those indexes. The workflow locks the current
account then the content row, rechecks the session/ownership and expected version,
and rechecks dependencies at confirmation. Existing enrollment, publication,
assessment, submission, weekly configuration and feedback writers lock the same
resources before adding references. PostgreSQL foreign keys also reject a missed
or future reference. A foreign-key failure rolls back and returns a safe dependency
warning; other storage failures log only kind/SQLSTATE, never bindings.

The publication pointer cycle is broken by a temporary Draft/null-pointer update
inside the locked transaction, before deleting definition children and parent.
Other requests cannot observe that intermediate state. Any failure, including
audit failure, restores publication, revisions, notifications and parent. Existing
audits remain readable. No ad hoc constraint disabling, cascaded learner deletion,
schema reset, new provider, scheduler or queue is introduced.

Routes use authentication, current-account middleware, CSRF and a shared 5/minute
mutation limit. Policy checks happen again under locks. Deploy the tested code and
apply ordinary migrations and rebuild caches/assets; application rollback does
not resurrect deleted content.
Restore from a tested backup would be a separately assessed recovery operation.
When financial/ranking/challenge-module relations are introduced later, extend
dependency checks and restrictive database references before enabling those writes.

## Verification

Tests cover all three content kinds/lifecycles, roles/ownership, warning and
cancellation semantics, confirmation, stale/revoked state, every current retained
dependency, feedback tombstones, completed failures and ended events, new references
after a warning, notification/revision/audit rollback, foreign-key protection,
CSRF, throttling and repeat deletion. Independent PostgreSQL processes verify
one deletion/audit for simultaneous confirmations and no broken references when
a learner opening races deletion.

Verification passed: focused deletion/publication checks 157 tests / 1,266
assertions; full PostgreSQL regression 827 tests / 6,122 assertions. The additional
historic weekly-selection case was verified in the final cached configuration,
routes and views run: 248 tests / 2,051 assertions. All 29 migrations execute on
clean test databases. Pint, 20 frontend tests, production Vite build, workflow
YAML parsing and diff whitespace checks passed. The editor rendering defect has
regression coverage for empty forms, saved editors and post-deletion confirmations.

Remote CI, deployment and browser visual checks remain unverified.

## Next implementation prompt

The owner approved explicit password-confirmed Google linking for existing
verified accounts, preserving registration, verification, account status and
session policies. See [Google authentication](google-authentication-implementation.md).
Provider credentials and real OAuth round trips must be configured and verified
privately before activation.
Keep purchases, numeric ratings, reward formulas and live Judge0 in the deferred
register; do not re-request already deferred credentials.
