# Four creator-configurable game templates

Owner request, 2026-10-04: add more than three games and a CLI environment, with
content controlled by creators. This delivers four additional version-1 templates:

| Template | Concept | Creator scenario | Objective |
| --- | --- | --- | --- |
| Pixel Studio | Coordinates and sequences | 1–9 target pixels on a 3×3 canvas, with mint/peach/lavender colors | Paint exactly the target; other cells remain blank |
| Number Machine | Variables and arithmetic | Integer start/target, each -100…100 | Reach the target using add/subtract/multiply |
| Sort Lab | Arrays and algorithms | 2–6 integers, each 0…99 | Swap indexed positions into ascending order |
| Terminal Quest | CLI commands and file inspection | Up to 8 virtual files, a new destination and required contents | Copy matching contents to the destination and inspect it with cat |

Guests try the default scenarios at `/playground`; landing and signed-in Play
link there. Trials and creator previews have no completion endpoint. They do not
save activity or grant rewards. Published creator lessons use the existing
server-validated daily activity workflow. The three-step built-in ladder retains
its approved ordering; these new assessments do not invent ladder thresholds.

## Ownership and integration

D01/D02 module creation and editing use Instructor-owned immutable revisions and
Moderator/Administrator publication review. B04/B05 learner participation reads
the authorized module revision, including pinned course lessons. G08–G10 presets
support the four games with immutable snapshots and Deferred rewards. Existing
withdrawal, archival, ownership, enrollment, current-session and stale-write
checks remain authoritative. No new role or access policy is introduced.

`config/arcade.php` owns built-in template examples. `ArcadeGames` validates typed
scenario data and interprets a bounded instruction list. `GameAssessment`
dispatches existing Garden and new game assessment behavior. Browser interpreters
provide visible state and feedback; server interpreters independently replay the
submitted program against the approved revision. Browser success flags and
client-supplied objectives cannot grant progression. Existing account locks and
daily uniqueness prevent duplicate activity on retries.

Creators edit scenario fields instead of executable source. Titles, instructions,
hints and learning feedback are editable for every template. Scenarios serialize
to a strict, versioned JSON snapshot; extra keys, wrong types, unsupported colors,
duplicate coordinates/file names, unsafe file names and unreachable terminal
objectives are rejected. Examples are solvable within 12 commands: at most nine
paint operations, two bounded additions for any numeric target, five swaps for
six elements, or a copy and inspection for a terminal objective.

The virtual CLI supports `ls`, `cat filename` and `cp source destination` with an
Enter-key prompt and editable program history. It has no real filesystem access,
shell processes, pipes, networking or uploaded scripts. Real Python/Java/C++
execution remains in the Judge0 boundary and its live setup remains deferred.
Terminal contents are single-line plain text (up to 300 characters); this matches
the creator file editor's line format. Files are entirely assessment data.

## Schema and deployment

Migration `2026_10_04_000028_expand_game_preset_templates` widens the existing
PostgreSQL template allowlist without relaxing version, scoring, eligibility or
Deferred reward constraints. It retains all existing presets and references.
The migration is transactional and requires a table lock while changing the
constraint; schedule it appropriately for existing production traffic.

Run normal migrations, rebuild frontend assets, refresh config/routes/views and
restart workers for the release. No package, credentials or provider setup is
added. Rollback must use a release that understands these new template snapshots
once they exist. The down migration refuses incompatible rollback when new
presets exist; it does not delete learning content. Prefer a compatible rollback
release or forward fix over removing immutable published data.

## Provenance and verification

These four engines, controls and visuals are original Kody code, using existing
Vite/Laravel dependencies. No third-party game source or assets are imported.
[Blockly Games](https://github.com/blockly-games/blockly-games) and its
[build workflow](https://github.com/blockly-games/blockly-games/wiki/Build) were
rechecked as reuse candidates. Its broader game/editor build would require a
separate adaptation and server-grading contract. Small deterministic interpreters
fit this release without adding that dependency; a richer block editor can still
be evaluated later. No copied code means no imported revision or asset notice is
required for this change.

Tests cover winning and invalid programs, interpreter parity, strict scenarios,
draft rollback, publication/version preservation, presets, validated/idempotent
daily activity, course-pinned objectives and enrollment/withdrawal restrictions.
Browser checks use standalone renders of actual Blade views and built assets,
with no database requests or submitted forms while PostgreSQL tests run. This
checks playable controls, customized scenarios and responsive layout; it does
not claim a full accessibility audit or live production validation.

Verification passed locally on PHP 8.4.26/PostgreSQL 17: full suite 944 tests /
6,783 assertions; cached config/routes/views suite 207 tests / 1,401 assertions;
32 frontend tests; Pint, production Vite build, workflow YAML and whitespace
checks. All 31 migrations execute during clean PostgreSQL test setup. Browser
checks cover all four default wins and custom objectives, terminal Enter-key
commands, template switching and 390px layouts without horizontal overflow.
Remote CI and production deployment have not been verified by this task.
