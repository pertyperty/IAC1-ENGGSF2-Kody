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
Screenshot review also found the floating companion covering a mobile filter.
It now starts hidden until requested, while preserving saved show/hide choices
and the guide button. Existing drag, keyboard and tutorial controls remain.
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

Follow-up local evidence (20 November 2026; reviewer: Codex): application source
`f8c0af26127109d8dce9758ae2aaee5d722a8ddc`, disposable PostgreSQL and Playwright
browsers; no owner database or live providers. Evidence-only follow-ups preserve
this application source. Backend checks ran on ecf0c16; the later companion
default changes only frontend behavior. The PR identifies the final immutable candidate.

- Production asset build and all 66 frontend tests pass.
- Broader backend batch: 161 tests / 1507 assertions pass. Final focused batch,
  including Library, FAQ, profile, history and course paths: 134 / 1321 pass.
  Another account/profile-inclusive batch passed 130 / 1219. These sets overlap;
  their counts must not be added. Before this follow-up, the full backend suite
  passed 1394 / 10955; it was not rerun in full for the latest source.
- Pint and configured level-5 PHPStan pass. Five touched controllers now join the
  explicit analysis scope; no ignores or baseline were introduced.
- Broad Chromium diagnostic: 91 pass / 4 fail across 95 cases. Three failures were
  obsolete labels and one exposed stale Help tour selectors. After correction,
  all 10 affected UI reruns pass, including the four failures, course feedback,
  live previews and Library. This is not a single clean full-suite result.
- Final companion-default build: 16 workspace/guide/Library/creator regressions
  pass, preserving the five pre-existing workspace regressions in a separate spec.
- 48 focused cross-engine cases pass in Chromium, Firefox and WebKit: all seven
  Arcade execution/reset flows, bounded scrolling, HTTP startup fallback, saved
  proficiency, staff filters, Help spacing, sidebar scroll and profile preview.
- Populated legacy upgrade and an 87-table PostgreSQL dump/restore pass, including
  retained row fingerprints, encrypted fixtures, progress and ledger checks.
  Restore took 5194 ms locally; this does not establish production recovery time.

Logs remain ignored under `storage/app/private/followup-*`; current screenshots
include `help-edit-preview.png`, `profile-live-preview.png` and
`library-bookmarks.png`. GitHub Actions must validate the pushed PR head before
review readiness. Human teaching quality, real-device performance, staged
operations and live providers remain separate release evidence.


### PR 53 CI repair

CI run 44695189708 on b028692 stopped at the backend suite: 1396 passed,
two failed. The Progress label changed to Streak, leaving an obsolete assertion;
the redesign also dropped the current weekly challenge link while still loading
its authorized summary. Restore that link inside the collapsed Challenge results
section. Verify the current streak data and labels, and assert that archived
weekly events have no dashboard link. Judge0 remains disabled; preview navigation
must not consume an attempt or call a provider.

Local verification: production asset build and Pint pass; all 43 progression,
weekly-event and dashboard tests pass (311 assertions) on disposable PostgreSQL
port 51629. No schema, reward, admission or integration changes. The full CI
pipeline must rerun on the correction; later browser/restore stages were skipped
in the failed run and have no new CI result yet.


### Sidebar CI assertion follow-up

Run 44730496168 on 49e87aa passed all 1398 backend tests (11023 assertions)
and 1037 cached-configuration tests (7953 assertions). Chromium passed 100 of
101 browser cases; the sidebar test expected an offset above 743 and received
730. Later cross-engine and restore stages did not run.

The old sidebar assertion compared against the offset before Playwright activated
the link and assumed identical scroll bounds on both pages. The test now waits
for fonts, records the actual activation offset and compares it with the
destination's valid range. A second check reloads from an interior offset and
requires exact restoration within three pixels, so a reset still fails. No
application behavior or persistence safeguards are changed. The original case
passed locally in Chromium, Firefox and WebKit; this does not reproduce the CI
runner's exact geometry.

The corrected navigation/reload case passes in all three local engines (3 tests,
33.1 seconds). JavaScript syntax and whitespace checks pass. Logs are ignored at
`storage/app/private/ci53-rail-final.log`; no new full-suite pass is claimed.


### Sidebar restoration timing repair

Run 44734111071 on 6d4b234 again passed 1398 backend and 1037 cached tests,
but the stricter sidebar assertion still found a 16-pixel loss (100 other
Chromium cases passed). The earlier test-only correction was insufficient.

Restoring before font/header layout settles can clamp the saved offset to a
temporarily smaller scroll range. Navigation now reapplies the original saved
offset after font readiness and two layout frames. Pointer, wheel or keyboard
input cancels this late restoration, preserving user control. Existing per-user
session storage and hidden-mobile-rail protection remain unchanged.

A deterministic frontend regression forces a temporary 730-pixel maximum before
expanding it to 746; it fails against the original implementation and passes
with this fix. Three additional cases preserve user input during late layout.
All 70 frontend tests and the production build pass. No backend, schema, reward
or provider changes are included.

Final local browser verification: all 9 tagged workspace cases pass across
Chromium, Firefox and WebKit (54.9 seconds), including sidebar navigation/reload,
profile previews and companion opt-in. Ignored evidence: `ci53-layout-frontend.log`,
`ci53-layout-build.log` and `ci53-layout-browser.log` under `storage/app/private`.
The full CI pipeline must validate the new application source.


### Completed candidate CI and notification navigation

GitHub Actions run [44737790756](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/actions/runs/44737790756)
passed on ef6b2f6fa5212629c296cd0fdf6891f70e328df7. Dependency audits,
formatting/static analysis, frontend tests/build, clean migrations, backend and
cached-configuration tests, Chromium/accessibility, cross-engine learning checks
and populated upgrade/restore all passed. This closes the CI follow-up above for
that revision; it does not establish production operations or human learning
outcomes. PR 53 remains draft and unmerged.

The notification follow-up preserves the selected Read/Unread filter and page
after Mark as read. If removing the last unread item empties a page, navigation
returns to the last populated page; clearing the last unread item keeps the
Unread empty state. The redirect uses only validated local filter/page values.
Existing ownership, active-session locking and idempotent read handling remain.
First-page actions avoid an extra count query; pagination and page correction
share one page-size constant. The controller joins the scoped level-5 PHPStan
check. No migration, provider or visual layout change is needed.

Local follow-up verification on 20 November: all 10 focused notification,
ownership and publishing regressions pass (98 assertions) against disposable
PostgreSQL on port 51629; production assets, Pint and scoped PHPStan pass.
Evidence is ignored under `storage/app/private/notification-build.log` and
`notification-tests.log`. The new revision still requires its own CI result.
No additional browser run was performed for these hidden form-context fields
and server redirect changes.
