# Three-track Tower expansion

Owner request and delegated implementation: 18 October 2026. This extends the
[panel learning record](panel-learning-and-onboarding.md), which owns the original
fifty floors and readable simulator programs.

## Scope and progression

The Tower now contains **80 free levels and 111 stages**. Thirty original lessons
add ten floors for each track. Expert is a content difficulty label, not a
placement result or certification. Formative code-reading questions do not
execute the displayed Python or pseudocode; Judge0 remains disabled.

| Track | Retained floors | New floors | Learning focus |
| --- | --- | --- | --- |
| Beginner | 1–25 | 51–60 | Boundaries, accumulation, aliases, strings, coordinates, functions, duplicate values, virtual CLI and Boolean rules |
| Intermediate practice | 26–50 | 61–70 | Search, two pointers, frequencies, invariants, queues, failure tests, prefix sums, recursion and growth |
| Expert | None | 71–80 | Invariants, amortization, graph assumptions, topological processing, DP dependencies, hashing and sorting tradeoffs |

Floor numbers are stable identities, not inter-track prerequisites. Eligible
signed-in participants may start any track, then clear published floors in its
ascending order. Beginner 25 continues to 51, Intermediate 50 to 61, and Expert
starts at 71. No earlier clears are fabricated. Guest trials remain 1–3; later
floors lead to welcome. Only validated final-stage wins qualify Manila daily
activity; no extra Tower XP or KodeBits are issued.

The additive, database-checked nullable `tower_levels.skill_track` preserves the
historical classification when NULL: 1–25 Beginner, higher floors Intermediate.
This includes pre-existing custom floors at an expansion position. Only newly
inserted seed floors receive explicit metadata. Seeding skips every existing
position, never replaces revisions or reclassifies a custom floor. Rollback
refuses to discard explicit metadata because that would change progression gates.
Administrator revisions keep the level's track and stable position.

Decorative altitude captions now derive their floor ranges from installed map
nodes instead of assuming fifty levels, including custom/gapped installations.
The five scenery zones and reduced-motion behavior remain cosmetic.

Track choice is cosmetic session state. Continue, Jump to current level and
returning from an authorized lesson use the chosen track. Completed tracks show
a finished state; each track shows its own saved count. Studio filters help
Administrators locate the new floors without paging through unrelated content.

## New lessons

Each quiz has editable plain-text objective, explanation, different worked
example, learner task and success criteria. Notes survive immutable publication
and appear in saved lessons and unsaved previews. Existing MCQ, accepted short
answer and matching graders check every answer; no manual grading is introduced.

| Floor | Lesson | Assessment |
| --- | --- | --- |
| 51 | Decisions at the boundary | Inclusive comparisons and equality |
| 52 | Count before you repeat | Accumulation and empty-loop predictions |
| 53 | Two names, one list | Python alias versus shallow copy |
| 54 | Make a clean username | String Forge and pipeline explanation |
| 55 | Coordinates leave a trail | Pixel Studio and coordinate matching |
| 56 | A function is a contract | Composition and unused return values |
| 57 | Equal values still belong | Sort Lab duplicates and multiset preservation |
| 58 | Inspect a release note | Virtual terminal and command matching |
| 59 | Say exactly what you mean | Switchboard and composed conditions |
| 60 | Boss: the foundation workshop | Number Machine and mixed testing checkpoint |
| 61 | Search a sorted signal | Midpoint, interval and precondition |
| 62 | Two pointers, one sum | Pointer choice and distinct indexes |
| 63 | Count without losing data | Frequency and representation |
| 64 | Protect the stack contract | Memory Trail and delimiter invariants |
| 65 | A queue keeps its promise | FIFO trace and container matching |
| 66 | Failure is a test case | Negative/empty inputs and limits of tests |
| 67 | Answer many range sums | Half-open prefix sums and query cost |
| 68 | Recursion needs a way home | Base case, value, calls and progress |
| 69 | Count growth, not milliseconds | Bound matching and nested work |
| 70 | Boss: the strategy observatory | Representation and runtime review |
| 71 | Prove the search boundary | Lower-bound invariant, duplicates and absence |
| 72 | One expensive append | Geometric copies and amortized versus worst-case cost |
| 73 | Find the fewest hops | BFS, discovery marking and weighted counterexample |
| 74 | Schedule without a cycle | Topological order, indegrees and stalled processing |
| 75 | Weights change the promise | Weighted paths and negative-edge counterexample |
| 76 | When greedy change fails | Coin DP, base/unreachable states and cost |
| 77 | Do not reuse the item | Zero-one knapsack update direction |
| 78 | A hash promise has assumptions | Chaining, equality and expected bounds |
| 79 | Sort records without losing intent | Stable ties, merge choice and space |
| 80 | Boss: defend the algorithm | Graph-method matching, negative cycles, DP and disproof |

Boss floors 60, 70 and 80 have two ordered complementary stages. Practice keys
remain visible in client data for formative feedback; this is not a secure exam.

## Sources and technical review

Wording, graphs, examples, scenarios and keys are original Kody material. No
third-party exercises, code, diagrams or assets were imported. Source reading
checks facts and offers further study, without institutional endorsement:

- [Python 3.14 data structures](https://docs.python.org/3.14/tutorial/datastructures.html), [bisect](https://docs.python.org/3.14/library/bisect.html) and [deque](https://docs.python.org/3.14/library/collections.html#collections.deque): references for alias/copy behavior, lower-bound partitions and queue operations.
- [MIT 6.006 Spring 2020 notes](https://ocw.mit.edu/courses/6-006-introduction-to-algorithms-spring-2020/pages/lecture-notes/): independent algorithm reading, particularly [BFS](https://ocw.mit.edu/courses/6-006-introduction-to-algorithms-spring-2020/resources/mit6_006s20_lec9/), [Dijkstra](https://ocw.mit.edu/courses/6-006-introduction-to-algorithms-spring-2020/resources/mit6_006s20_lec13/) and [DP subproblems](https://ocw.mit.edu/courses/6-006-introduction-to-algorithms-spring-2020/resources/mit6_006s20_lec16/).

MIT redistribution terms still apply; a link does not permit copying its exercises
into a monetized platform. Follow the [sourcing guide](tower-content-guide.md).

The private audit replays reference submissions through actual server graders.
Independent tests also calculate lower-bound outputs, geometric copy totals,
weighted path cost, minimum coins and both knapsack update orders. Every new quiz
question has a rejected incorrect answer. Human learning-effectiveness, diagnostic
placement and a broader coding examination still need separate evidence.

The audit exits unsuccessfully if a retained revision cannot be reference-verified
or the bounded report is truncated. A valid seed proposal no longer hides a
retained/custom failure. A bounded reference-finder failure requests editorial
review; it does not prove that a custom puzzle is impossible.

## Release and verification

Migrate before deploying code that queries track metadata. Explicitly run
`php artisan db:seed --class=TowerLevelSeeder --force` to add missing floors.
Existing positions/revisions remain untouched; review custom-position conflicts
before claiming an installation exactly matches the reference curriculum.
`kody:tower-curriculum-audit` checks all retained revisions; its output contains
practice answers and belongs in private editorial storage, not a public endpoint.

The isolated populated upgrade/81-table restore passed. All 80 seeded floors
passed typed server replay, and focused progression/authoring checks, frontend
tests and both production bundles passed. Desktop/mobile checks cover track
continuation, mixed-quiz saving, notes add/remove, isolated preview and
Administrator publication. The final backend, cached-route, complete browser and
restore gates are tracked in [PR #33 checks](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/pull/33/checks).
Live providers and production operations remain deferred. An empty/uninstalled
track is shown as being prepared, rather than falsely marked cleared.

The complete local browser pass finished with **63 passing workflows**, including
all five role workspaces, reports, Office previews, registration, role approvals,
game interactions and the new Expert path. Frontend tests passed 62 cases, with
an additional installed-floor altitude regression added afterward.

### Shared error-page repair

The broad backend review exposed a joined `Kody@endsection` directive in the
shared branded error layout. Blade treated the directive as text, leaving its
title section/output buffer open and emitting the status/title before the HTML
document. Separating the directive restores the intended title and balanced
rendering. Six status-specific checks cover 403/404/419/429/500/503, including the
independent database-light 500/503 fallback. The Tower tests are also rerun to
verify that the former buffer warnings are removed, rather than suppressed.

### Local installation

After a new private custom-format backup, the reviewed additive migration and
append-only seed were applied transactionally to loopback local PostgreSQL
`kody`. Thirty new floors were inserted; all 77 protected tables and all existing
Tower level/revision rows were unchanged. The private post-install audit verifies
all 130 retained revisions, without truncation. An initial helper audit-identifier
type error rolled its transaction back; the corrected operation passed all guards.
No account, password, role, session, learner history or financial state was reset.
