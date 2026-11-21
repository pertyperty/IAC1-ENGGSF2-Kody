# Game experience overhaul, 21 November 2026

The owner's request is to overhaul every existing game for clarity, engagement,
difficulty and learning value before third-party integrations. This pass changes
the shared play experience and existing practice tasks; it adds no game engines,
Tower floors, currencies, dependencies or provider activation.

## Play experience

- Each programming game shows a goal, a Build / Run / Explain flow, and a live
  comparison with its target. A preview match is not a completion. Explicit Run
  and the existing concept check remain necessary; saved activities still require
  authoritative replay before completion or rewards.
- Constrained arithmetic, text and stack tasks use one-click pseudocode buttons
  with short operation meanings. There is no select-then-add cycle. Custom games
  retain their parameter inputs and command editor.
- Undo is visible alongside Run, Reset and Hint. Arcade instructions can be
  reordered using Alt + Up/Down and removed with Delete; Garden retains its
  existing instruction-selection tools and keyboard controls. Programs stay
  bounded as they grow.
- Pixel tasks compare all nine cells, including blanks. Sort tasks show the
  target array separately from clickable positions. Number tasks display the
  stored value and an optional step-by-step value trail. Text and stack tasks
  distinguish matching and different values; stack columns mark TOP and BOTTOM.
  Switches show the gate's rule and input/output state. Terminal filenames can
  queue inspection commands while the command input still teaches real argument
  order in the bounded simulator.
- Wrong concept answers ask learners to trace the example before retrying.
  Successful explanations remain available after the correct answer. This avoids
  immediately printing the answer explanation on the first wrong selection.
- Quizzes show answered/unanswered question navigation, retain choices while
  navigating, mark per-question feedback and clear stale feedback after edits.
  The form is bounded rather than growing through the whole page.

Focus game opens the same live game in a native modal, with both board and
program controls fitted to the screen. Desktop uses two columns; narrow screens
use a stationary board above an independently scrolling controls pane. Escape or
Exit focus restores the game to its original position, preserving instructions,
assessment state, completion routing and keyboard focus. Browser fullscreen
permission is unnecessary.

## Practice difficulty

Practice offers Warm-up, Challenge and Stretch for all seven Arcade types and
the three Garden modes: sequences, loops and conditions. These are thirty tasks,
including the existing ten challenge tasks. Learners can choose any difficulty;
it is not another account progression system or a proficiency certification.

`PracticeChallenges` composes each variation from an existing trusted assessment
and explicit practice-only scenario/limit overrides. It never delivers reference
programs to HTTP clients. Task limits and command restrictions use the same
browser interpreters and authoritative server evaluation as existing content.
Difficulty review also checks shortcuts: the stretch route needs at least eight
moves, the loop needs a four-instruction body, and sorting's six-value cycle needs
five swaps versus four in the existing challenge. Exhaustive shorter-program
checks cover constrained arithmetic, text and stack stretch tasks. This measures
minimum solution length under those task rules; it is not a proficiency score.

Published module and Tower snapshots are not rewritten. The practice route has
no completion endpoint and grants no rewards. A completed local task offers the
next difficulty without modifying Tower admission.

## Visual review

The shared browser's screenshot command failed twice with: "The browser is not
producing screenshot frames." The isolated Playwright browser captured the actual
public Flexbox Froggy interface and Kody's ten practice concepts instead. The
public comparison reinforces a visible destination, a short task and an editable
program beside the world, without copying its branding or automatic completion.

Initial Kody screenshots caught arithmetic history pushing mobile controls too
far down and a low-contrast Terminal execution label. The value trail is now
expandable, Terminal contrast is corrected, duplicate practice headings are
removed and mobile phase indicators show only the current step. Existing desktop
stationary scenes and independent control scrolling remain intact.

Reviewing the focused Pixel Studio screenshot also exposed the palette covering
the canvas's bottom row on mobile. The focused canvas now uses compact cells
with at least 44px touch targets and reserves its full height before the palette.
The regression checks every editable cell and its separation from the palette.

Private captures are under `storage/app/private/game-overhaul-2026-11-21/`.

## Verification and remaining evidence

All thirty reference programs pass both browser interpretation and authoritative
server evaluation. The first browser task pass completed every variation through
its actual controls; a keyboard/Undo assertion incorrectly expected 6 after
doubling the starting value 2 and was corrected to 4. The quiz navigation flow
passed. The full Chromium regression run passed 112 of 113 tests. The remaining
failure used an exact canvas heading selector that omitted its accessible
"editable" suffix. That selector was corrected; the game journeys are rechecked
across Chromium, Firefox and WebKit. All 66 focused cross-browser executions
passed: all thirty real-control task playthroughs, quiz navigation, first actions,
Run/reset, bounded programs, stationary desktop scenes and Focus game behavior.
After the final mobile canvas adjustment, all six additional Focus layout checks
passed across the same three browsers, including full canvas visibility and
palette clearance at 390px and 320px. Updated screenshots were inspected.
The production build was repeated successfully after that CSS change.

Seventy-four focused backend tests passed with 486 assertions, including guided
assessment replay, Tower behavior and practice validation. All 73 frontend tests,
the production asset build, Pint and scoped PHPStan (expanded to PracticeChallenges) passed. Verification
uses the disposable PostgreSQL cluster on port 51629, never the owner's database.
Unrelated Docker/deployment work remains excluded.

Automated completion establishes solvability and regressions, not fun or learning
transfer. Before integrations, observe first-time learners identifying controls,
predicting a changed state, recovering from one mistake and explaining the same
idea in a new example. Record task completion, requested help and misconceptions
without supplying the solution. Curriculum difficulty and actual target devices
still need human review. No API credentials block this development; provider
activation remains paused, with Judge0 intended first after owner review.
