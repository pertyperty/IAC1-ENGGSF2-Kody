# Learning workspace and saved progress — 2026-10-11

This follows the owner's requests for centered LMS-style reading, usable media
previews, richer My Journeys, progress in both catalogs and quieter game results.
It extends [the gamified journey](gamified-journey-flow.md) and
[platform polish](platform-polish-and-fixtures.md); existing publication, access,
reward and provider policies still apply.

## Reading and resources (B02/B04, D01)

Standalone and course lessons share a centered reading column. On screens at
least 1200 pixels wide, View opens an authorized document beside the lesson and
splits the available workspace. Enlarge uses a native fullscreen dialog; narrow
screens open that modal directly. Closing or Escape restores the View button's
focus without scrolling to a receipt. Switching modes keeps the same iframe.
PDF uses the browser's viewer; DOCX/PPTX use escaped text previews, with downloads
for complete native formatting. Original resource-page links remain a fallback.
The private media controller rechecks the same owner/staff/enrollment/current
revision authorization for pane requests. No document is sent to a third party.
The Practice Arcade card fills its workspace, with an adaptive scene/control
split. Game help and source editing retain their optional folded controls.

## Owned saved history (B01–B05)

LearningHistory combines own standalone server wins, course lesson records and
authorized openings in bounded batched queries. A module read or assessed inside
a course appears completed in Learning catalog. Reading has its own label and
continues to grant no streak, XP, ladder clearance or eligibility credit.
Earlier module editions are explicitly labeled; course cards show progress in
the enrolled, pinned edition when the published course changes. Starter game
clearance uses the existing progression snapshot. Guests/staff receive no
participant history. Personalized pages have no-store/private caching.

My Journeys shows completed/total counts, percentage, join date, last recorded
lesson activity and completion date (the latest first clearance across every
pinned lesson), plus recent modules. No visit is presented as a completion.
Read-only summaries never create admissions or awards. Revoked access retains
history but offers no direct continuation; withdrawn content hides titles and
links. CourseLearning reuses its sequential-unlock calculation and CourseTrail
for continuations; the destination GET reauthorizes. Reusing a module across
courses does not clear a different course's pinned assignment automatically.
Statuses use grey, blue or green surfaces alongside text and icons, never color
alone. Unrecorded dates are explicitly absent rather than estimated.

## Focused game outcomes and compact actions (B05/B10)

Confirmed game/quiz results open one centered native modal over a dimmed, blurred
backdrop. It distinguishes saved wins, local practice and failed/uncertain saves.
The existing inline output stays available after closing. Course next-lesson
links consume validated server trail responses; Tower supplies its stage/floor
continuation and starter games use the server's unlocked next starter. Browser
practice never gains saved progress. Hints and ordinary form notifications stay
non-blocking. Native modal focus containment, Escape, return focus and reduced
motion are retained. Tower result ownership does not suppress saved HUD refresh.

The existing reaction component moves into the result modal and returns to its
original place on close; it is not cloned, so counts, versions and event handlers
remain consistent. Heart/thumb/star buttons retain accessible names, tooltips,
counts and aria-pressed. Pressing the selected icon removes the reaction; choosing
another replaces it using the existing serialized/versioned server workflow.
Shared Home/document actions and game undo/reorder/reset/hint controls use compact
icons with accessible names; important submit/destructive actions retain clear
labels. Original placeholder icons remain in public/images/navigation.svg.

## Verification and deployment

No schema changes, dependencies, live provider setup or new rewards. Focused
PostgreSQL checks cover own history, reading/assessment distinction, previous
editions, standalone wins, revoked/withdrawn links and escaped private previews.
Browser acceptance covers wide/narrow split previews, fullscreen/Escape/downloads,
catalog synchronization, centered results, reaction toggles, continuation,
return focus, full-width arcade, both themes, axe and overflow checks. Full CI
also rechecks the remaining workflows and cached-route behavior before PR review.

Local focused verification passed 52 PostgreSQL tests / 512 assertions, 54 frontend
tests, the production build and Pint. Corrected browser checks passed for Tower
HUD refresh, terminal command exploration, split/fullscreen media, catalog/course
progress, result reactions and return focus; screenshots were inspected. Routine
terminal commands keep inline output without interrupting exploration; explicit
Run failures and confirmed completions use the result dialog. Board guidance and
starter supporting lesson/quiz material are folded to keep the game prominent.
