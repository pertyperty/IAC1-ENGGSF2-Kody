# Next step: weekly coding events — E02, C03/C04/B07

Judge0 API-plan setup and credentials are deferred by the owner. Keep live code
execution unavailable; independent event management can be implemented with
provider fakes once the following business decisions are resolved.

## Confirmed rules and source traceability

E02 names the Moderator and scheduled Event Trigger. An event selects an approved
Published challenge, gets a unique Weekly ID, stores its schedule/rules/reward
configuration and is auditable. Only one weekly event may be active at once.
The system-defined cycle starts Sundays at midnight. Prior standard completion
does not block weekly participation, and weekly submissions must be isolated from
historical standard submissions.

The owner has approved free weekly participation and verified results first,
with XP, rank changes and KodeBit rewards deferred until their formulas and ledger
are implemented. The owner has also approved a separate three-attempt budget for each weekly event,
every confirmed submission receiving evaluation, and one active evaluation per
user/challenge context. Committed evaluations continue after browser closure.
Standard challenge attempts span publications; never reuse their participation
row or reset that budget when a weekly event begins.

## Required owner decisions — pending

1. E02 does not define timezone or the exact closing boundary. Proposed:
   Sunday 00:00 to next Sunday 00:00 in Asia/Manila, inclusive start and exclusive
   end. Daily streak approval of Asia/Manila does not automatically settle this.

The question was presented to the owner on 2026-10-03. The proposed window is
not approved yet. Do not persist guessed time windows or reward amounts.

## Proposed implementation after approval

Gamification owns version-pinned event configuration and schedule lifecycle;
Challenge Management owns weekly attempt admission and evaluation. Reuse the
Judge0 boundary, encrypted source, safe queue creation and private results rather
than building another execution engine. The Play hub presents the current event;
Learning retains its separate catalog.

Introduce events with approved challenge revision, UTC start/end, rules, lifecycle
and record version. Database constraints and a shared locked scheduling record
must prevent overlapping activation and stale manual overrides. Authorize fresh
Moderator account/session and audit configuration/state changes. Scheduler work
uses Laravel's existing scheduler and durable locks, not another host cron entry.

Introduce weekly participations unique per user/event with independent attempt
counters. Bind each weekly submission to the event's pinned revision and validate
its window server-side immediately before admission. Preserve results if the
challenge's publication changes later. Require explicit confirmation and source
limits; never trust a client-selected event/revision to bypass access or budgets.
Keep standard and weekly results clearly distinguished in private history.

Tests must prove Sunday boundaries, no overlapping events, pinned revisions,
Moderator authorization, fresh sessions, stale changes, independent budgets,
concurrent final attempts, durable queue rollback, interrupted evaluations and
hidden-test privacy. Tests use PostgreSQL and provider fakes. No new external
credentials or monetary effects should be required for this first event scope.

This file is a next-step implementation prompt and requirements record; it does
not claim weekly events, automated selection or rewards are implemented.
