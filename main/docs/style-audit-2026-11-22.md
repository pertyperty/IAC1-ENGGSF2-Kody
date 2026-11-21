# Shared style and learning layout audit, 22 November 2026

The owner requested a site-wide CSS review, removal of obstructing Back/Home
controls, focused games by default, clearer learning context, distinct Modules
and Courses icons, and a simpler quiz experience. This is a presentation and
interaction change: no schema, rewards, admission or provider contracts change.
Actors are guests, learners, creators and staff. Existing A01/A02, B03/B05 and
D01/D05/D07 browser journeys anchor the account, learning and publication
regressions; this presentation pass adds no new use-case or permission policy.

## Findings and changes

The application imports nineteen CSS files. Repeated generic button rules and
later page overrides made appearance depend on import order. The global page
navigation was sticky beneath an already sticky header, duplicating navigation
and covering content and editor controls. Mobile labels were also reduced by
older rules to 11-12px despite newer page-specific fixes.

- `design-system.css` now owns shared typography, spacing, buttons, forms, flat
  surfaces and focus states. It loads once after component styles. Thirty stale
  shared/navigation rules were retired from the older files. Specialized game
  controls, charts, document viewers and semantic colors retain their owners.
  Forty legacy quiz rules were also consolidated into game-missions.css; these
  previously mixed a multi-card grid with the stepped question layout.
- The global Back/Home partial and its include are removed. Header navigation,
  the sidebar, and useful contextual links such as Level map remain. The focus
  menu gives access to Tower (Overview for staff), Arcade, Modules, Courses and Help without a fixed
  quick-action overlay. Its unused server-side route/label mapping is retired too.
- Shared buttons use a 44px minimum target, one border and consistent padding.
  Primary, secondary, quiet, destructive and disabled states remain distinct.
  Forms use 16px inputs and readable labels; secondary instructions use 15px.
  Sections use whitespace and separators instead of nested panel borders.
  Catalogs omit repeated decorative banners and their search panel is flat.
- Modules use an SVG document icon; Courses use an SVG graduation-cap icon.
  The same sprite is reused by desktop and mobile navigation.

## Focused activities

Learner Arcade, Tower and module activities open focused automatically. Creator
and staff previews stay embedded. The same live DOM and completion context are
retained. The header has Menu, Learn and Exit focus; Escape also restores the
page. Click activation works on touch, pointer and keyboard without relying on
hover. The ordinary navigation is available after exiting focus. Menu also exposes
theme switching and, for Tower play, the existing track selector. Closed supporting
sections cannot open invisible dialogs; a manual exit suppresses other automatic
focus entries on the same page.

Learn opens the existing explanation/example and a related-module link. It
preserves the short goal and Hint controls. Desktop keeps the scene beside an
independently scrolling program; narrow screens keep it above the controls.
Garden's scrolling scene is keyboard focusable. A closed focus view does not
immediately reopen, and Tower stage/result dialogs retain their own continuation.

Quizzes show one question at a time with a question count, large answer rows,
answered/unanswered navigation, Previous/Next controls and an explicit Check
answers action in a pinned footer. Focused quizzes omit repeated introductions
and secondary progress prose. Answers persist while navigating. Checking all answers reveals
the first incorrect or unanswered question; editing clears stale feedback.
Choice, short-answer and matching formats still use the existing evaluator and
server replay. Closing the result restores focus to the submitting action.
There is no invented timer, speed bonus, leaderboard or service.

## CSS ownership review

| File | Responsibility / disposition |
| --- | --- |
| app.css | Import order; shared primitives load last |
| design-system.css | Shared typography, controls, forms, surfaces, spacing |
| theme.css | Light/dark semantic palette; redundant button rules retired |
| play.css | Original landing/game scaffolding; generic button rules retired |
| interface.css | Account/content scaffolding; generic primary rules retired |
| workspace.css | Header, sidebar and account menus |
| usability.css | Forms and list usability; obsolete quick-navigation rules retired |
| platform-polish.css | Profile/feedback surfaces; obsolete sticky page nav retired |
| journeys.css | Course journey and result presentation |
| tower-studio.css | Authoring layouts and editor jumps; global nav exception retired |
| tower-map.css | Map-specific presentation retained |
| learning-workspaces.css | Resource viewers, saved progress and results retained |
| kody-world.css | Brand artwork and companion presentation; generic buttons retired |
| quest-lobbies.css | Lobby-specific layouts retained |
| operations-studio.css | Staff/authoring layouts retained |
| learning-foundations.css | Lesson and learning foundations retained |
| simplify.css | Existing page refinements; shared/nav duplicates retired |
| game-play.css | Interpreter scene/control presentation retained |
| game-missions.css | Focus workspaces, teaching context and stepped quiz presentation |

Component CSS remains separate to keep the change reviewable. Further page work
should update its owning component and the shared primitive where appropriate,
without adding another last-loaded override file. Preserve reduced motion,
light/dark themes, focus visibility and meaning conveyed with text as well as color.

## Public reference comparison

Actual public pages were inspected in the shared browser on 22 November 2026:

- https://flexboxfroggy.com/ : a dedicated scene/program pair and concise task
  make the current learning action clear. Kody retains its own art, manual Run,
  interpreter and server-validated completion.
- https://www.w3schools.com/quiztest/quiztest.asp?qtest=JS : one question and large
  answer rows establish a clear reading order. Kody adopts the question focus and
  navigation while keeping answers revisitable. Advertisement overlays visible
  on the reference are not a pattern to copy.

This is a comparison of observed interfaces, not a claim that Kahoot or NetAcad
was authenticated or reproduced. Native keyboard controls and accessible labels
remain more important than copying decorative shapes or scoring behavior.

## Verification

Local checks passed: all 73 frontend tests; 27 focused backend tests with 397
assertions (navigation, notifications and dashboard behavior); the production
build; targeted Pint; scoped PHPStan, expanded to ApplicationChrome; component
CSS parsing; and whitespace checks. The initial targeted browser batch passed
all 25 tests, including all thirty practice tasks and light/dark public pages.

Regression review corrected a keyboard-inaccessible Garden scroller, an automatic
focus attempt from a closed support section, cascade ordering for existing game
artwork, and quiz result focus restoration. Full-page workflows now explicitly
exit focus before using bookmarks, resources or guides. Tests for the removed
Back/Home strip assert retained header/contextual routes instead. The long-help
keyboard test supplies genuinely overflowing text instead of requiring medium
help text to overflow the larger focused panel. No checks were disabled.

The last broad Chromium run passed 116 of 118 tests. The two remaining cases
needed updated full-page interactions: move the pointer away from the restored
header before testing auto-hide, and exit focus again after a bookmark redirect.
Both corrected flows passed subsequent checks. A fresh complete CI run remains
the final candidate-wide verification.

The larger cross-engine batch passed 86 of 87 executions. It exposed a WebKit
nested-dialog focus return difference. Return and Escape now restore the source
control explicitly without a delayed close event taking a later user's focus.
All 36 final executions across Chromium, Firefox and WebKit passed after that fix,
covering Run/reset, board/program positioning, menu/theme/learning context,
stepped quizzes and result focus. All three final targeted Chromium tests passed
for staff role-specific menus and module/Tower bookmarks. Updated 1440px, 390px
and 320px captures were inspected. These runs overlap; they are not learner-study
sample sizes.
Private screenshots and logs are under `storage/app/private/` with the
`styles-audit-2026-11-22` and `styles-` prefixes. The CI evidence artifact
includes only the explicit synthetic style screenshot PNGs from that private
directory, alongside its existing browser report/results; no database or private
application storage is uploaded. Checks use the disposable
PostgreSQL cluster on port 51629, never the owner's development database.

Automated visual/accessibility and workflow checks do not establish learning
transfer, engagement or production readiness. An observed first-time learner
pilot, curriculum/target-device review and operational evidence remain open.
Third-party activation remains paused. The owner's unrelated Docker/deployment
changes are excluded from this PR.
