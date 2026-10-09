# Gamified journey flow — 2026-10-09

The owner requested continued gap-finding, convenient navigation and a more
game-first experience. This addresses an interaction gap in B01/B03–B05: course
wins saved successfully, but learners had to return to the outline and locate
their next lesson. It extends the [workflow audit](workflow-quality-audit.md) and
[approved sequential paths](platform-completion-implementation.md).

## Delivered

- A participant-only mission board shows today's saved win, starter trail and
  progress toward the next approved XP rank. It uses existing owned snapshots,
  adds no queries or new reward rules, and replaces the separate starter
  continuation card. Staff keep their governance workspace.
- Creator courses have an ordered trail with numbered/checkmarked steps,
  readable Ready/Cleared/Locked/Unavailable states, lesson style and explicit
  play/revisit buttons. Creator content remains escaped, typed and revision-pinned.
- Course outlines and lessons share a continuation panel. After a confirmed
  game/quiz completion, the response includes saved counts and the first available
  unfinished lesson. The page reveals the direct next-lesson action without
  automatic navigation or focus movement. A finished journey offers discovery
  of another course. Reading still uses an explicit server POST and returns to
  the trail, where the same next action is available.
- `CourseTrail` owns presentation of an authorized course snapshot;
  `CourseLearning` reuses one path-state reader for outlines, lessons and confirmed
  completions. All completion writes and returned progress remain inside the
  existing transaction. The next GET rechecks current authorization/availability.

## Boundaries

Mission cards are progress displays, not a new daily quest/reward system. No new
XP, KodeBits, badges, streak freezes or unlock rules are introduced. Saved reading
does not qualify a daily win; local practice and failed/uncertain saves cannot
advance the displayed course trail. Existing enrollments keep their path policy.

Locked/unavailable steps cannot be recommended. Withdrawn titles remain hidden.
A blocked unfinished course is not presented as finished. Frontend continuation
rejects another course, malformed/impossible counts and external or unrelated
destinations; title/feedback rendering uses text. UI recommendations do not grant
access. There are no migrations, new dependencies or live-provider activation.

## Evidence

Feature regressions cover all five role mission visibility rules, saved daily
qualification/Manila rollover, approved XP thresholds, sequential reading/game
continuation, invalid attempts, finished courses and withdrawn/locked lessons.
Existing course, privacy, enrollment, immutable revision and query-bound tests
remain applicable. Frontend checks reject unconfirmed/malformed continuation and
verify the next/finished states without a navigation jump.

The isolated browser registration/play/enrollment journey follows the real next
button after reading, verifies the new next action after a saved game and checks
mobile layouts, themes and accessibility. Final broader CI evidence is recorded
on the associated PR. Deferred live setup remains in the
[deferred register](deferred-features.md).
