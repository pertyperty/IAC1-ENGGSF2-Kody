# Creator garden designer — D01/D02/B05

This step extends the approved game-first creator direction: creators can change
the game world as well as its prompts. It uses original Kody code and the existing
version-1 Command Garden interpreter; no external code/assets were imported and
no arbitrary uploaded code is evaluated.

## Creator experience

Instructor-owned module drafts offer a visual five-column/four-row board. Choose
Path, Kody's start, Goal flag or Crystal, then click/tap a tile. Arrow keys move
focus between neighboring cells; Enter/Space paints the focused cell. Objectives
remain walkable and cannot overlap. Crystal painting is limited to conditional
games, up to four crystals, outside the starting tile. Removing a path tile also
removes its crystal. Reset restores the selected built-in trail.

Try this level mounts an interactive preview using the current layout and prompts.
It supports the same sequence, twice-repeated loop and crystal-condition mechanics
as learner games. Preview wins have no completion endpoint and do not save streaks,
ladder clearance, XP, ranks or KodeBits. Saving a draft does not publish it; the
existing Moderator/Administrator approval and immutable revision process remains.
Published and pinned course revisions retain their previous world during editing.

The first designer keeps the existing 5x4 boards and instruction caps: 12 for
sequences/conditions, 3 instructions repeated twice for loops. These are bounded
template implementation limits, not new reward or learner-access rules. Workshop
presets remain immutable references; choose Garden game for a directly authored
world. This step does not add configurable workshop-preset geometry.

## Server contract and integrity

Content Management delegates layout handling to the Games-owned `GardenLayout`.
The optional `game_layout` form field is bounded JSON containing exactly `start`,
`goal`, `path` and `crystals`. Coordinates are integer pairs inside the fixed board;
lists must contain unique positions. Start/goal must be distinct and on the path,
crystals must be on the path, and non-conditional worlds cannot contain crystals.
Unknown keys, executable source, malformed JSON, oversized input and unsupported
geometry are rejected. Missing layout preserves the existing template defaults for
older callers. Non-game assessments exclude the layout field.

The server checks solvability with bounded search and the existing authoritative
interpreter. Sequence/conditional search deduplicates position/crystal states
(at most 20 x 16), while loops retain distinct patterns of at most three moves
(at most 84 nonempty candidate patterns). An unsolvable draft is rejected without
persisting its parent, revision or audit. The solver's program is not persisted
or trusted from the browser. Submitted learner programs are still validated
against their authorized approved revision before recording any daily activity.

Existing JSONB assessment snapshots hold the normalized layout. No schema change,
new queue, provider, permissions, credential or deployment service is required.
The existing account/content locks, optimistic version checks, review states,
course revision pinning and staff withdrawal remain intact. Rendered data is
escaped; preview prompt updates use textContent. Browser assertions cannot grant
access or progression. Build frontend assets and refresh view/config caches when
deploying. No production or remote-CI readiness claim is made.

## Verification and next step

Tests cover all three concepts, default compatibility, strict geometry/input
validation, unreachable goals and incompatible loop patterns, draft rollback,
save/reload, immutable published revisions, server-validated/idempotent wins and
non-game exclusion. Frontend tests cover painting, objective safety, immutable
inputs, crystal removal and replay through the existing interpreter.

Browser checks use a standalone render of the actual creator view and built assets,
with no database access or form submissions, while PostgreSQL tests run separately.
They verify editing an objective, successful preview, mode controls, keyboard
painting, the accessible preview title and a 390px mobile breakpoint without
horizontal overflow. This is not a full accessibility audit or a production
browser workflow claim.

Verification passed: full PostgreSQL suite 907 tests / 6,633 assertions; cached
configuration/routes/views 467 tests / 3,618 assertions; 24 frontend tests; Pint,
Vite production build, workflow YAML parsing and diff checks. All 30 existing
migrations continue to execute on clean test databases; this step adds none.

Next independent step: broaden the reusable assessment library with a distinct
visual coding game, using a typed versioned data contract and server validation,
then integrate it into creator review, learning and presets. Reuse suitable
licensed code when it improves adaptation cost; preserve provenance. Keep the
explicitly deferred Google/Judge0/payment setup and reward decisions deferred.
