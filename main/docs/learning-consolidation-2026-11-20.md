# Learning and administration consolidation - 20 November 2026

## Scope and ownership

Owner-requested corrective pass across A01/A02 onboarding, B05 games, public
learning discovery, G01 account browsing, G08 presets and creator qualification
review. No provider activation, new game engines, floors or reward policy.

The base is fetched main `192a115af5ab4588668fdc6237156845fc2ae695` (PR #52).
Its GitHub Actions run 44556159401 completed successfully. PR #51 is present in
that ancestry; a feature-branch merge badge alone was not integration evidence.

## Games

The primary button is Run. Garden uses `kody.move_right()` and explicit collection
calls. Arithmetic uses programming operators; pixels and stacks show named calls.
The command-text editor retains the existing validated instruction format; no
JavaScript, Python or other source is evaluated.

Desktop workspaces fit the remaining viewport below their heading. The scene stays
visible while the keyboard-focusable instructions/program pane scrolls. Narrow
screens and embedded narrow resource workspaces use ordinary document scrolling.
The editable program remains bounded. Hidden Tower stages fit when revealed.

A reproducible startup defect used `crypto.randomUUID()` merely to name answer
radio groups. Browsers on untrusted HTTP development hosts may omit that API,
preventing all guided games from mounting. The regression fails against the old
bundle with the API disabled; document-local counters remove that requirement.
This demonstrates one failure mode, not proof of the owner's exact environment.

## Tower placement

Verified signed-in Learners with no saved preference see an accessible native
choice dialog explaining Beginner, Intermediate and Expert. Submit persists a
checked account preference under the current-session lock, selects that track
and opens its next eligible level. Returning sessions fall back to the saved
preference. Explore first dismisses the prompt for that visit; the track picker
remains available. Existing accounts receive no fabricated preference or clears.
Proficiency is self-reported, not a badge or qualification. Existing per-track
admission and reward replay remain authoritative.

## Credibility and administration

Catalog items now have separate content and creator links, avoiding nested anchors.
Public attribution uses the existing public username and explicit profile fields;
private email, identity and credential files stay private. Qualifications reviewed
is a visible labelled indicator, distinct from a role pill.

Instructor/Contributor creators can request a badge review from Public creator
profile using their existing approved application and available private documents.
Requests are idempotent while pending and enter the policy-filtered staff inbox.
A request grants nothing. Existing password/session/proof/version checks govern
grant/revoke. Staff may decline a current request with a private audited reason;
the request timestamp prevents a stale decline from closing a newer request.
Deleted accounts remove the request and reset their proficiency. Inactive/changed
roles are excluded from pending badge review.

Game presets opens as a searchable status-filtered browser, with saved lesson
revision counts and a paginated usage list showing lesson ID/title, lesson version,
preset version and review state. Editing creates a new preset version; historical
lesson assessments remain pinned. The existing authorized creation endpoint is
retained for compatibility, but creation is no longer the browser's primary action.
Built-in activities remain available when no custom presets exist.

Notifications provide an owned read/unread filter, unread count, Manila timestamps,
clear status and authorized detail links. Account support has section navigation,
readable labels, flatter result rows and state colors plus text. Newest 10 accounts
replaces the ambiguous Recent label; active searches operate over All accounts.
The desktop rail uses bounded internal scrolling and remains hidden at mobile
breakpoints. Repeated staff workspace panels are removed from account browsing.

## Routed list and filter audit

| Page family | Filtering decision |
| --- | --- |
| Module catalog | Existing literal title/concept and activity filters retained; linked attribution added. |
| Course catalog | Existing search plus validated difficulty and clear action. |
| Coding challenges | Existing language/difficulty/topic/concept filters retained; terminology and linked attribution clarified. |
| Library | Literal enrolled-title search, latest 12 opened Modules, owned content/Tower bookmarks and type filters; enrollment pagination is retained. |
| Lesson/course/challenge studios | Added owned literal title and lifecycle status filters with query-preserving pagination. |
| Publication review queues | Added literal title search; exclude own content and superseded submissions so linked latest review matches the queue. |
| Instructor/contributor review | Added applicant username and decision status filters; private documents remain authorized downloads. |
| Review inbox | Existing category filter extended to pending qualifications requests. |
| Community accounts | Existing role/status/literal username and retained filters; clarified scope and page-size labels. |
| Presets | Name/status filters and paginated pinned lesson usage. |
| Notifications | Owned all/read/unread filter. |
| Help | Existing topic/search filters plus clear action; staff FAQ question search added. |
| Wallet history | Added received/spent display filter; balance, reservations, earnings and reconciliation remain unfiltered. |
| Tower map/list/studio | Existing independent track and map/list filters retained. |
| Weekly, leaderboards, reports, finance, moderation, erasure | Retained specialized bounded period/state/category controls and server policies. |
| Login, registration, verification, recovery, legal, contact, profile/edit/detail and decision forms | No artificial list filter; preserve contextual actions and field validation. |
| Game, lesson, challenge and resource detail | Keep activity/input controls rather than adding unrelated search bars. |

## Library, profile previews and Help follow-up

The sidebar retains its own scroll position across navigation in per-user tab
session storage. Mobile hidden rails do not overwrite it. Navigation uses short
labels: Modules, Courses, Challenges, Progress, Library, Studio and Reviews.

Library combines enrolled Courses, the latest 12 opened Modules, content bookmarks
and the existing saved Tower levels. Title search and content-type controls share
one location. The old Review later address redirects to Bookmarks. Saving is an
owned, idempotent reminder: it grants no enrollment, access, XP or clearance.
Draft/withdrawn content cannot be newly bookmarked; unavailable saved items hide
content titles and remain removable. Account erasure removes owned bookmarks.

Administrators read and edit FAQs on Help, beside a live plain-text preview.
Existing policy gates, version conflicts, audited changes and delete confirmation
remain in force. Guests and other roles receive published answers only. Legacy
management routes render the combined interface, preserving existing links.
Account and public creator forms offer optional live previews. They use text nodes
rather than HTML, never submit automatically and omit private names/email from
the public identity preview. Badges reflect existing reviewed status only.

Progress keeps one Tower continuation, a compact activity summary and recent
learning. Reward rules and optional starter history are expandable. Display copy
uses Modules for individual units and Courses for collections; route/model names
and versioned legal documents retain their existing identifiers.

## Visual comparison

Actual public screenshots were captured on 20 November from
https://flexboxfroggy.com/ and https://flukeout.github.io/.
Froggy pairs instructions/code with a persistent scene. CSS Diner presents a short
task, a visible source editor and a separate contextual reference. Kody adopts the
same task/code/result relationship, visible code and optional reference without
copying their art or relying on a screenshot as proof of teaching effectiveness.
An Exercism track capture remained visually in a loading state despite resolved
links; it is not used as evidence of completed catalog layout.

Ignored local artifacts are under
`storage/app/private/learning-consolidation-evidence/`: reference screenshots,
desktop/mobile workspace, proficiency dialog, account, notification and preset
screens. Browser tests also produce theme/narrow-layout evidence in their report.
A rendered GitHub Docs Get started screenshot also provides a reference for
short navigation labels, persistent sidebar navigation and expandable sections.
Kody Help uses concise question rows and a separate editor/preview relationship;
this is a visual comparison, not proof of equivalent usability.
Some diagnostic screenshots predate the final layout corrections; final candidate
verification below identifies the usable captures.

## Migration, deployment and verification

Three additive migrations add nullable checked `users.coding_proficiency`, the
owned `creator_badge_requests` table and checked, uniquely owned `learning_bookmarks`. Deploy migrations and the complete Vite build
together. The owner database on port 5432 has not been migrated or reset.
Verification uses a disposable PostgreSQL cluster on port 51629 and independently
created browser databases, with providers disabled. The sanitized PHP configuration
is task-local; global Herd configuration is unchanged.

Initial checks: 66 frontend tests and production build passed; focused backend
145 tests / 1307 assertions passed before final filter additions. Scoped PHPStan
passed with a process-local 512 MB limit (the first default-memory run exhausted
128 MB). The first full browser diagnostic was stopped after finding stale UI
selectors, a wheel test targeting the newly independent pane, and a mobile rail
breakpoint regression. It is not a full-suite pass.

Final candidate verification: pending. Human teaching quality, real-device
performance, staged operations and live providers remain separate release evidence.
