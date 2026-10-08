# Navigation, role usability and feedback — 2026-10-09

This implements the owner's application-wide usability request: simpler surfaces,
fewer navigation detours, visible action choices, toast feedback and persistent
learner progression. It extends [the shared interface](interface-implementation.md)
and [sticky navigation](navigation-and-visual-polish.md), without changing role,
access, reward or provider policies.

## Navigation and role corrections

- Both layouts provide a visible context-aware back button. Guest login,
  registration and recovery pages explicitly offer Back to home; authenticated
  pages also have a Home shortcut. Course lessons return to their course.
- Courses are directly available in the primary navigation. Workspace navigation
  directly exposes courses, weekly quests, leaderboards, each publication and
  role review queue, presets, FAQ management, privacy reviews, wallet and updates
  under their existing policies. A menu now has only one active destination.
- Staff dashboards emphasize community work and omit participant-only journeys,
  starter trail and achievement cards. Staff course catalog cards lead to the
  authorized course review page rather than a learner outline that returns 403.
  Course catalogs show My journeys only to eligible participants.
- Account menus provide sign-out and an unread updates indicator. Both account
  and mobile workspace disclosures close with Escape and restore summary focus,
  or close on an outside click. Existing focus/open-menu scroll guards remain.

## Persistent server-owned progression

Verified Active Learners, Contributors and Instructors see their daily streak,
XP/rank and cleared starter levels above the sticky header, including when the
header hides during scrolling. Compact mobile values retain streak, XP and levels.
Profile/security editing, account lifecycle confirmations and content/preset
editors hide this strip to avoid distracting from the current task. Staff have
no learner HUD. The level count explicitly describes the starter trail; it is
separate from XP rank and creator course completion.

`ApplicationChrome` reads existing progression/achievement services and derives
policy-filtered page navigation. `GET /play/progress` uses authentication, current
account-session checks, participant authorization and a rate limit. It returns
only the current account's canonical snapshot with private/no-store caching.
It accepts no browser identity, progress or reward values and performs no grants.

Successful server-confirmed game/quiz saves refresh the strip. Returning to a
visible page or restoring it from the browser page cache also refreshes it.
Network/authorization failures retain the last server snapshot and expose a
Refresh progress button. There is no periodic polling or reward calculation in
JavaScript. Local guest practice and creator previews remain unsaved.

## Visual hierarchy and feedback

`usability.css` adds flatter, readable shared surfaces and 44px action targets.
Blue identifies primary actions, neutral grey secondary navigation, green explicit
approval, amber return-with-feedback, and red destructive actions. Hover/focus
changes avoid changing button dimensions. Account fields and hints are more
readable; report tables scroll within their container on narrow screens.

Both layouts own one escaped notification region. Session success and validation
summary messages are dismissible toasts; field-specific errors remain at inputs.
Game save failures/successes, reaction updates and coding evaluation status changes
use the same layer while retaining their relevant inline game/assessment context.
Messages use text nodes, status/alert roles and keyboard-operable dismiss buttons.
They remain until dismissed, deduplicate currently visible messages and bound the
stack to three without removing a focused notification. Without JavaScript,
server messages remain readable in normal page flow. Toasts have a bounded scroll
area and sit above content without blocking the rest of the page.

## Verification and limits

- Feature regressions cover all five role HUD rules, editing focus, guest back
  links, escaped single-render feedback, identity-scoped read-only snapshots,
  and staff course destinations while retaining forbidden learner access.
- Frontend tests cover malformed snapshots, confirmed-save refresh, offline
  preservation and manual retry. Existing games/security tests remain applicable.
- Isolated PostgreSQL browser journeys cover guest registration through saved
  play/enrollment, creator draft/review/publication, all five role workspaces,
  keyboard dismissals, mobile menus, HUD refresh and 320px layouts. Every exposed
  workspace destination is checked for successful rendering, accessibility and
  overflow; both themes are checked on role workspace surfaces.
- Vite build, Pint and the full PostgreSQL suite/CI verify the final tree. Browser
  screenshots remain ignored local evidence under `storage/app/browser-results`.

This is a usability pass, not evidence of every production NFR or live integration.
Provider credentials, production/staging verification and owner-deferred setup
remain in [the deferred register](deferred-features.md). No schema, economy,
authorization grants, credentials or production data changes are introduced.
