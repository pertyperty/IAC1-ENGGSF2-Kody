# UI consistency audit

Owner-requested follow-up, 2026-10-11. Presentation changes support A01/A02/A06,
B01–B11, creator D/C workflows and staff G workflows. Existing access, immutable
revisions, rewards and financial contracts stay with their owning services.

## Shared controls and links

Action arrows across account, learner, creator, moderation, finance, Help and
pagination views use `x-action-icon` and the existing public SVG sprite. Dynamic
Tower, tutorial, result-dialog and toast actions use the same JS icon helpers.
Icons are decorative; controls keep descriptive text or accessible names.
The shared icon size, baseline and flex alignment keep arrows centered beside
labels. Code/CLI output can retain textual operators and execution traces.

`quiet-link` anchors and sentence links now use underlines with a consistent
offset and hover treatment. Explicit `.button` actions retain button styling.
Profile action groups and other intentional navigation cards keep their existing
component surfaces. Decorative periods were removed from the wordmark and Tower/
Arcade headings; ordinary sentence punctuation is preserved.

## Courses and progress chrome

The compressed `@else@include` sequence prevented Blade from recognizing the
unjoined-course progress include. Browse Courses now uses readable separated
directives for enrolled and unjoined cards. The same adjacency pattern was
removed elsewhere. Revision-aware progress and policy-filtered destinations
remain server-owned. Card labels use SVG arrows instead of Unicode variants.
Direct catalog headings have a display-size baseline. Catalog cards now use
content-driven spacing rather than applying a 65px minimum to every paragraph,
which had created empty space under prices. Flex columns keep the final action
aligned across a row while shorter price/status text stays compact.

The progress strip centers its three groups across the viewport, with rounded,
quiet hover surfaces and a Tower icon. Narrow-screen wrapping, hidden secondary
labels and focused-editing exclusions retain their existing behavior. No staff
participant HUD or browser-derived rewards are introduced.

## Tower decoration

The standalone floating satellite overlay was removed. The original ascent
backdrop and small zone scenery remain. The canvas uses 2px dots at a minimum
36px spacing, capped at 4,000 points, with the existing bounded pixel allocation.
Five meadow/forest/cloud/orbit/planet colors have separate light/dark palettes.
A filtered attribute observer redraws when the ascent zone changes; the theme
observer redraws on theme changes. Pointer repulsion and passive touch scrolling
remain, animation stops at rest, and reduced motion hides the animated grid.
Decoration grants no progress and makes no network or provider requests.

## Verification

Browser regression coverage checks unjoined course rendering without leaked
directives, centered HUD, arrow alignment, focused-profile HUD exclusion, both
viewport widths, and actual grid-pixel changes on theme/ascent changes. Existing
all-role navigation/workflows, accessibility, media, games and reduced-motion
checks run alongside these. Results are recorded in the pull request after checks
finish. No schema, package, provider configuration or production setup changes.
