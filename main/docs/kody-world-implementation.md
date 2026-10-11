# Kody assets, guided pages and ascent — 2026-10-11

This owner-approved presentation amendment covers B01/B02/B05, B11, A06 and
shared interface NFRs. It changes no account, access, completion, reward or
financial rules. No migrations, provider calls or new dependencies are required.

## Reusable art

`config/branding.php` owns the logo and the typed asset-name map.
`<x-kody-art>` renders decorative, dimensioned images; visible text and accessible
control names still explain their meaning. Replace one path to update shared art.
The original SVG mark remains available as `public/images/kody-mark.svg`.

The new original blue axolotl mascot (wave, celebration and thinking), hexagonal KodeBit and ascending cartoon
world use the built-in imagegen tool. Transparent originals and optimized WebP
delivery files live under `public/images/kody/`. Sources are replaceable design
placeholders, not user uploads. XP, rank medals, Run, warning and satellite assets
are editable original SVGs matching the existing code-native icon system.
See [art provenance and exact generation prompts](../public/images/kody/README.md).

The logo, header guide, companion, Help, loading and result surfaces reuse the
mascot. Wallet balances/packages use the coin; progress uses XP; leaderboard
medals distinguish the first three positions; game/submission buttons use Run.
Amounts, rank names and leaderboard positions remain server-owned.

`kody-world.css` owns brief greeting, celebration, coin-hover, button and panel
animations. CSS/JS animations were chosen instead of GIFs to retain alpha,
sharp scaling, small transfers and reduced-motion control. No external asset
hosts, tracking, continuous particle timers or generated executable content.

## Tutorial and companion

The first guest arrival on a public page opens a native modal welcome, with Click
to begin, Skip and Log in. Authentication and error pages stay focused. Subsequent
steps use circular spotlight windows and mascot text for visible controls on the
current page. Next, Previous, the spotlight and Escape advance/dismiss the guide;
it never submits forms, grants progress or executes a privileged action.
The header Kody button and Help tour action replay it for all roles.

Native modal focus containment, return focus, readable mobile cards and reduced
motion apply. The header remains revealed during the tour. `kody-tour-v1` stores
dismissal only in this browser; it is not an account completion record. Blocked
localStorage degrades to in-memory behavior. No JS means ordinary navigation.

The companion is a draggable cosmetic mascot, not a reward/pet economy. Pointer
capture bounds mouse/touch movement; arrow keys move the focused handle. Clicking
opens help/hide controls, while dragging does not accidentally open them. Normalized
position and hidden preference use `kody-companion-v1`; resize clamps it to the
viewport. Header guide restores a hidden companion. Modal tasks hide it.

The custom navigation loader appears after 350 ms on real same-origin document
links. It never delays navigation, masks form submissions, intercepts downloads,
external links, hash changes or modified clicks. It resets on pagehide/pageshow
and after six seconds if navigation is cancelled. The welcome is an explicit
user-dismissed screen; the loader is not a fabricated progress percentage.

## Tower, Help and profile

Up/down controls now use accessible icons, with a target button to jump to the
current authorized/guest floor. Dots retain passive mouse/touch interaction with
lower opacity. A bounded illustrated backdrop pans from meadow/forest through
mountains/clouds to moon orbit and planets; ten-floor zone captions and a satellite
provide environmental distinction. Animation uses one coalesced frame per scroll
update, not a perpetual loop. Reduced motion keeps the backdrop static. No new
unlock, clearance, paid gate or reward policy is implied by scenic altitude.

Sidebar labels now translate in the same direction as their revealing bars.
Help uses a sticky search/topic column, grouped guides and a responsive reading
column. Pagination/search/filtering and Active-only visibility remain intact.
An empty result retains a Reset search action. Profiles use centered identity and
account-detail columns; hover/focus reveals a pencil over the photo, linking to
the existing private, version-checked upload editor. Touch has a visible Edit
picture cue. Personal fields and security flows retain existing authorization.

Custom 403/404/419/429 screens keep the shared shell and safe exit links. 500/503
use a standalone, database-independent fallback without querying account chrome
or exposing exception details. Framework status codes and retry headers remain.

## Verification

Dedicated Chromium tests cover first-visit/replay/skip/Escape, spotlight geometry,
keyboard focus, bounded companion movement/preferences, land-to-space scrolling,
present-floor jump, reduced motion, Help grouping, profile editing, sidebar
direction, custom errors and delayed navigation/back restoration. Existing
returning-user journeys explicitly dismiss cosmetic onboarding; first-visit
coverage remains independent. Browser fixtures create Active guides through the
real Administrator management service only in a guarded isolated database.

Local verification passed 87 focused account/FAQ/Tower PostgreSQL tests with
805 assertions, all 54 frontend tests, the production build and Pint. Full PR CI
passed all 32 browser journeys and the 79-table PostgreSQL upgrade/restore
rehearsal. The backend run reports 1,030 passed and 177 risky tests (8,921
assertions); cached configuration/routes report 644 passed and 131 risky tests
(5,865 assertions). These Pest warnings remain visible; a green workflow does
not mean the suite is warning-free. Download regression coverage confirms native
resource/credential downloads bypass the navigation loader.
Browser evidence is Chromium desktop and
emulated mobile only; physical touch devices and other engines still need staging
verification. Live integrations, cloud deployment and production launch stay in
the deferred register.
