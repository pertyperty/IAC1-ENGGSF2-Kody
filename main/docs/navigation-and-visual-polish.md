# Navigation, typography and visual polish

Owner-requested follow-up, 2026-10-07. This extends [interactive workspaces](interactive-workspaces.md)
and [interface implementation](interface-implementation.md) across account,
learner, creator and staff views. B01/B02/B05 remain game-first; A/D/G workflows
retain their existing validation, authorization and immutable content contracts.

## Navigation behavior

The shared top bar sticks to the viewport. After deliberate downward scrolling,
it hides; upward scrolling, hovering at the top edge or focusing its controls
reveals it. A labelled **Show navigation** button supplies a touch/keyboard route.
Keyboard activation returns focus to the first navigation link. Focused controls
and open Account/workspace menus prevent scrolling from hiding the bar. A focused
reveal button survives intermediate scroll events until activation or Tab.

The desktop workspace rail stays visible and scrolls independently when a short
viewport cannot contain its links. The mobile workspace summary also sticks;
expanded links have bounded vertical scrolling. The measured header height drives
their offsets, including font loading, viewport changes and multi-row mobile
headers. Anchor targets reserve space below navigation, and the skip link is above
the sticky layers. Without JavaScript, the bar remains sticky and native menus
remain operable. Reduced motion removes the slide transition.

## Visual system

`design-system.css` unifies typography, flatter surfaces, denser grids and tactile
buttons after the existing component styles. `workspace.css` owns sticky chrome;
`scroll-navigation.js` owns its progressive behavior. Existing broad shadows,
lifting hover transforms and unused CSS creator-window/sticker art were removed
from their original files. Borders, surface color and text contrast communicate
hover/selection; stable card positions avoid shifting the user's target. Button
bottom borders provide a restrained game-control cue, without a floating shadow.
Disabled controls retain their existing busy guards.

Space Grotesk supplies headings; Instrument Sans supplies body, navigation, form
and button text with explicit real weights. The seven normal WOFF2 variants are
checked into `resources/fonts`, with explicit weight mappings in the installed
Vite plugin's local-font API. Builds no longer fetch font providers. Only body
400 and heading 600 are preloaded; other weights load when used. Both SIL OFL 1.1
notices ship under `public/fonts`.

## Artwork and provenance

The logo/favicon, explorer, flag, crystal and two landing-page illustrations under
`public/images` are original Kody SVG drawings made for this change. They contain
no scripts, remote references, embedded HTML or uploaded creator code. The logo
connects coding chevrons to the explorer identity. Sized landing images reserve
their layout space and load lazily. Garden artwork preserves the accessible
board's current position/path/goal/crystal description and typed game-state colors.
These images are replaceable design assets; they do not become creator fields or
change assessment snapshots.

Font files were acquired through the existing Bunny font build integration on
2026-10-07, then pinned locally without modifying them. Primary sources:
[Space Grotesk](https://github.com/floriankarsten/space-grotesk),
[Bunny family/license](https://fonts.bunny.net/family/space-grotesk), and
[Instrument Sans license](https://github.com/google/fonts/blob/main/ofl/instrumentsans/OFL.txt).
The file-level hashes and license locations are recorded in
[the font provenance record](../resources/fonts/README.md).

## Verification and deployment scope

Browser regressions cover scroll direction, edge hover, keyboard reveal/focus,
the skip link, short-screen rail scrolling and narrow touch menus. The existing
role, theme, creator-review, enrollment and game-save checks remain required.
All fixtures use a guarded disposable database; separate navigation accounts
preserve the single-session rule rather than bypassing login confirmation.

There are no migrations, new permissions, dependencies or activated providers.
The static SVGs, font licenses and built local fonts must be included in release
artifacts. The measured build and browser results are recorded in
[interface implementation](interface-implementation.md#navigation-and-visual-polish--2026-10-07).
Local Chromium checks do not establish staging, real-device or full WCAG compliance.
