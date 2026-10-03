# G06 staff content withdrawal and restoration

## Approved policy and scope

The owner approved temporary Moderator/Administrator withdrawal of Published or
Archived modules, courses and challenges from all learners, including existing
course enrollees. Content, revisions, progress and audits remain intact; committed
coding evaluations finish. A separate staff block preserves owner lifecycle rules.
Only staff restoration removes it. Creator edits, republication and archival cannot
clear it. Existing self-review protection applies to staff-owned content.

G06 already has publication review for module/course/challenge revisions. This
release adds withdrawal/restoration at `/manage/content` under Administration and
Governance. Content, Challenge and Gamification modules enforce the block within
their existing access/transaction boundaries. No reward or financial effect is added.

G06 requires flagged or queued content. For this release, staff review the content
and attest that it is flagged before withdrawal; the action/audit records that
attestation. This is manual staff flagging, not evidence of an implemented community
report intake or abuse investigation system. Staff detail pages show the approved
revision (including staff-only hidden challenge cases), pinned course composition
and existing publication-review links. Inputs/content are escaped.

## State, authorization and concurrency

Each content table has nullable `staff_withdrawn_at`. A block does not replace
Published/Archived, change the approved revision pointer or erase references.
Withdrawal and restoration increment the existing record version. Gates require
verified Active Moderator/Administrator access, eligible lifecycle and non-self
review. Authentication/current-session middleware, CSRF, confirmation and a
ten-request per-minute budget protect action routes. The service locks staff and
immutable owner IDs in stable order, freshly rechecks the session and policy,
then locks the content and checks version/action/confirmation. Competing requests
commit one action; stale creator/reviewer forms must reload.

Block timestamp, version, constrained moderation history, minimal audit and
creator database-channel notification commit together. No remote mail blocks the
request. Notifications contain kind/ID/action references, not creator personal data,
free-text allegations or lesson/test content. They identify a historical action
time; later decisions may differ. Suspended/Archived creators retain a notice for
later permitted access; Deleted identities receive none. Audit/notification storage
failures roll back the entire action. History is paginated and includes actor,
content reference, original lifecycle, action and version/time.

Only the moderation service force-writes the block. Model mass-assignment guards
exclude it; publishing writers use fixed fields and retain it through revisions
and owner archival. Creators can prepare corrections under their existing Draft/
Published editing permissions. Staff publication review does not lift the block;
restoration is an explicit separate decision. Privileged owner/staff previews remain
available to inspect/correct content but cannot bypass learner progression guards.

## Learner access and retained records

- Catalogs and direct published pages omit/block withdrawn content.
- Course withdrawal blocks new enrollment, repeat enrollment, existing enrollees'
  outlines, pinned lessons and completion writes. My Courses retains an unavailable
  enrollment card without an access link. Restoration preserves pinned revisions
  and progress. Restored Archived courses retain only existing-enrollee access.
- Module withdrawal blocks standalone and pinned lesson/assessment access. Course
  outlines mark the affected assignment unavailable; new composition, approval
  using it and new enrollment reject unavailable modules. Other course assignments
  keep their existing permissions. Restoring an Archived module does not publish it.
- Challenge withdrawal blocks new standard and weekly attempts. Exact retries of
  an already committed confirmation return that same durable attempt; they create
  no new participation or evaluation. Existing result/source views remain private
  to the original submission owner. Evaluation does not re-check a live publication
  pointer, so committed work finishes against the original revision.
- Weekly selection/configuration/play and admission exclude withdrawn challenges.
  Temporary withdrawal keeps the original Scheduled/Active event and revision;
  restoration allows remaining play within its original window and attempt budget.
  The ordinary scheduler closes expired events. Owner archival retains its prior
  Unavailable behavior. Withdrawal does not extend a week or grant fresh attempts.

Completion writers check the locked course/module block before recording progress
or a streak. Prior validated learning remains saved. Content already delivered to
a browser cannot be retracted; subsequent server access and completion are checked.

## Migration, deployment and verification

`2026_10_03_000022_add_staff_content_withdrawals.php` adds nullable block timestamps
and `content_moderation_actions`. PostgreSQL enforces eligible blocked lifecycles,
exactly one typed content foreign key, valid action/lifecycle/version and unique
per-content action versions. Restricted references retain content/audit integrity.
The migration is additive and refuses destructive rollback after actions or blocks
exist. An application rollback to code that ignores this block would reopen access:
keep withdrawal-aware access guards in any rollback release while blocks exist.
No new secret, external integration, package or worker/scheduler configuration.

PostgreSQL tests cover all content kinds/lifecycles, actor hierarchy and self-review,
restricted/expired staff, confirmation/stale/protected input, creator republication,
archival, catalog/direct/pinned access, rollback, private notifications, constraints,
committed evaluation and weekly restoration. Independent PostgreSQL processes prove
simultaneous withdrawal/restoration commits one state transition and audit.
All 53 focused G06 tests passed / 446 assertions; the full PostgreSQL suite passed
676 tests / 4,853 assertions. All twenty-five migrations execute on clean test
databases. Cached configuration/routes/views checks passed 132 tests / 1,022
assertions. Pint, 16 frontend tests, the production asset build and diff whitespace
checks passed. Remote CI, browser visual checks and production deployment remain
unverified.

## Next implementation prompt

The owner subsequently approved G08–G10 using existing validated win rules and
explicit Deferred rewards. The [preset workshop](game-presets-implementation.md)
preserves immutable versions and module snapshots while inactivation blocks new
generation. Continue its next prompt; keep monetary features and live Judge0
activation in the deferred-feature register.
