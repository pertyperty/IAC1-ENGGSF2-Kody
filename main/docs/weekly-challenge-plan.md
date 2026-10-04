# Weekly coding events — E02, C03/C04/B07

> Later amendment (2026-10-04): the owner delegated economy, XP/rank/reward,
> reviewed paid access and creator-retention decisions. See [current policies](economy-and-launch-decisions.md)
> and [implementation](economy-implementation.md). Earlier first-release deferrals below
> are historical; live providers/staging and numeric ratings remain deferred.

## Approved scope and decisions

Gamification owns weekly configuration and lifecycle; Challenge Management owns
attempt admission and evaluation through the existing Judge0 boundary. E02 names
Moderators and the scheduled Event Trigger. Only verified Active Moderators may
configure events; this does not infer an Administrator configuration permission.

The owner approved Sunday 00:00 through the next Sunday 00:00 in Asia/Manila,
inclusive start and exclusive end. Canonical timestamps are UTC. The owner also
approved future Scheduled events alongside the current Active event, superseding
E02's conflicting no-active-event precondition. Only one event may be Active;
each event's configuration becomes immutable when its window starts.

Free participation, separate three-attempt budgets and verified results are the
first release. XP, rank changes and KodeBit rewards remain unavailable pending
approved formulas and ledger implementation. Events explicitly store Deferred
reward mode; passing a coding event does not currently grant streak progress.
Standard attempts span publications and never share a weekly participation row.

## Implemented behavior

Moderators search approved Published challenges, choose a week, confirm rules
and pin its approved revision. Draft changes to a future week require its current
record version. Published replacement revisions do not change an existing event.
The Play hub links the current quest and private weekly history; Learning retains
its separate catalog. Challenge statements and rules are escaped plain text.

`kody:weekly-events-sync` runs every minute through Laravel's scheduler with
overlap protection. It closes expired events, activates scheduled events, and
randomly selects an eligible approved Published challenge if the current week
has no configuration. With no eligible challenge it creates no event and retries
on a later tick. A manual future configuration wins over automatic selection.
An archived challenge makes its event unavailable; private past results remain.
Read-time eligibility and submission admission enforce the actual window even
before the scheduler updates lifecycle status.

Weekly submissions reuse confirmation, source/language limits, provider readiness,
encrypted source, durable database queue creation, evaluation leases and private
result rendering. Each user/event has three attempts and at most one active
evaluation. Identical confirmation retries return the same durable submission;
standard and different weekly contexts can each have an active evaluation.
Evaluations already committed continue after the event closes or browser exits.
Trusted routes bind the event and pinned revision; client-selected weekly IDs
cannot redirect standard admission or bypass budgets. Hidden cases and provider
output never enter normal client payloads.

## Integrity and authorization

Migration `2026_10_03_000013_add_weekly_challenge_events.php` adds events and one
calendar lock row. It adds nullable event references and generated context keys
to existing participation/submission tables. Context zero preserves standard
records; event IDs distinguish weekly budgets. Composite foreign keys bind
participations to the event's challenge and submissions to its pinned revision.
Unique starts, Sunday/length checks, a partial unique Active constraint and
Deferred-only reward checks protect the calendar in PostgreSQL.

Configuration locks the fresh actor, calendar and challenge before changing an
event. Admission additionally locks the scoped participation. State changes and
audits commit atomically; actor session validity is checked again after waiting
for locks. Scheduler operations use the same calendar lock and durable system
audits. Concurrent scheduler/configuration runs and final attempts cannot create
duplicate events or overspend an attempt budget. Result/history access remains
owner-only under current verified Active account authorization.

## Deployment and rollback

Run the additive migration, retain the existing database code-execution workers,
and run Laravel's scheduler. No new package or provider credential is introduced.
The selected Judge0 endpoint is `https://judge0-ce.p.rapidapi.com`; API-plan/key
setup remains explicitly deferred by the owner. Keep execution disabled until
private credentials, verified compiler IDs and sandbox failure drills are ready.
Event planning and browsing work independently of live execution readiness.

The down migration refuses to remove weekly participation history. Application
rollback must preserve this schema and data; never automatically run
`migrate:rollback`. Older admission code assumes one participation per challenge
and is incompatible with weekly history: disable submission admission/execution
before reverting to a pre-weekly release, and assess compatibility explicitly.

## Verification

PostgreSQL and provider-fake tests cover window boundaries, scheduler selection,
manual overrides, future planning, immutable started events, pinned revisions,
role/session restrictions, stale writes, archive behavior, independent budgets,
private history, disabled execution, transaction rollback, schema constraints and
the guarded down migration. Separate PostgreSQL processes exercise competing
Moderators, overlapping scheduler runs and final-attempt races.

Final local verification: 404 PostgreSQL tests / 2,779 assertions passed, and
80 focused tests / 500 assertions passed with cached configuration/routes/views.
All sixteen migrations execute on clean disposable PostgreSQL databases.
Pint, all 16 frontend tests, Vite build and diff checks passed. A timing-dependent
A03 lockout assertion now freezes its clock instead of racing a second boundary.
Remote CI, live Judge0 execution, production latency and deployment
remain unverified. Automated correctness checks do not establish the SRS NFRs.

## Follow-up prompt

Keep the game-first hub and reusable creator templates. Continue independent
account/content work while the Judge0 plan is deferred. When the owner is ready,
configure credentials privately, discover supported compiler IDs, verify provider
limits, and run sandbox success/failure drills before enabling execution. Add
rewards only after their formulas and financial ledger rules are approved.
