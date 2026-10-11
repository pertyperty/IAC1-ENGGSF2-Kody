# Platform polish and local learning fixtures

Owner-requested follow-up, 2026-10-11. This supplements the
[tower and media implementation](tower-and-media-implementation.md) and is the
current record for navigation, pictures, the 50-floor tower and local showcases.

## Accounts and private pictures

Sign-in accepts the current exact-case username, including spaces, or email.
Renaming a profile changes the username used for sign-in; the old name is not
an alias. The local account at `learner@kody.local` was renamed to `Learner 1`.
Its earlier generated fixture passwords no longer match its current hash. No
password, lockout, identity, session or history was reset during diagnosis.
Use the current password or the existing recovery flow.

All five verified Active roles can upload/remove their own profile picture.
JPEG/PNG uploads are limited to 2 MB, 6 megapixels and 4096 pixels per side.
GD decodes and crops to a 256-pixel square, re-encodes as JPEG and removes metadata.
Uploaded SVGs and executable payloads are rejected. Generated names and private
local/S3 storage keep original filenames and paths out of the browser. Photos
are visible through an authenticated current-session route only while their
owner is verified Active; there is no anonymous avatar route. Initials remain
the fallback. Profile-version locking prevents stale replacement, and replacement,
removal, rollback cleanup and account deletion use durable Account-owned erasure.
PostgreSQL also constrains picture metadata to private generated paths.
The shared erasure service replaces duplicated module/deletion queue code.

PHP requires `ext-gd`. `PROFILE_PICTURE_DISK=local` is the default; private S3
can be configured later. Existing provider credentials remain deferred.

## Navigation and feedback

The sidebar is anchored at the viewport edge. Its reveal animates the label
with the bar, preserving neighboring pulls, pinning, keyboard focus and touch.
Below 1100 pixels, a header hamburger opens the role-filtered workspace menu
directly beneath the header. Its scroll area is bounded; Escape/outside click
close it. The reveal button keeps its hit target when approached by pointer
or touch; hovering it cannot remove it before activation. Header
scroll/focus/open-menu guards remain intact. On mobile the
hamburger replaces duplicate primary workspace navigation.

The header shows current page context, a photo/username, update icon and a theme
button whose icon, label and accessible name reflect the next theme action.
Contextual back buttons stick below the shared navigation offset. Footer and
workspace widths now use the same full-width shell. Wallets, rankings and Help
have dedicated wide-screen layouts that collapse on smaller screens. Creator
earnings panels remain relevant to creator roles or retained earnings; staff
wallets do not present participant XP. Learning notes have a reading surface
distinct from game/quiz controls. Reduced motion disables reveal animations.

Server feedback is available in a dismissible toast and a persistent inline
receipt. Existing field errors and game output remain in place. Tower stage/win
feedback no longer calls page `scrollIntoView`; learners decide when to continue.

## Tower and new activities

The seed curriculum appends positions 26–50 without overwriting existing
Administrator revisions. The map ascends visually from level 1 at the bottom,
within a keyboard/touch-scrollable region with hidden visual scrollbars and
explicit Higher/Lower controls. It initially centers the next floor. Nodes stay
centered; descriptions reveal on hover/focus or a dedicated information button.
Original replaceable decorative SVG art lives in `public/images/`.

Bosses at 10, 20, 30, 40 and 50 require multiple ordered stages. The free-access,
guest 1–3, sequential unlock, retained-clearance and final-win streak policies
remain unchanged. Tower grants no extra XP/KodeBits. Three new typed version-1
templates work in arcade, module/preset authoring and Tower studio:

| Activity | Creator data | Confirmed instructions |
| --- | --- | --- |
| Logic Switchboard | 2–4 Boolean inputs, AND/OR/XOR gate, Boolean target | Flip an indexed input; server recomputes the output |
| String Forge | ASCII text start/target up to 40 characters | Upper/lower/reverse/trim/append; bounded text state and reachable targets |
| Memory Trail | Start/target stacks of up to six integers, 0–9 | Push/pop/duplicate/swap; enforce capacity and underflow |

All programs are bounded to 12 steps. Local previews never count as saved wins.
These are original Kody implementations; no external game code/assets were imported.
The [editorial guide](tower-content-guide.md) remains the sourcing/review workflow.

## Removable development showcases

`LocalLearnerAccountsSeeder` creates `kody_learner_two`, `kody_learner_three`,
`kody_learner_four` and `kody_learner_five` with emails `learner2` through
`learner5@kody.local`. Generated credentials remain in ignored private storage.
Reruns preserve existing passwords, status, verification, renamed profiles and
history; identity/role conflicts never promote or overwrite accounts.

`LocalShowcaseSeeder` requires local/testing and loopback PostgreSQL plus the
verified Active local role fixtures. It authors and approves 14 modules in
three sequential courses, nine Python/Java/C++ challenges and eight public FAQ
guides using the normal publishing/review services. It never seeds completions,
rewards, purchases or balances. Durable audit fixture keys and an advisory lock
make reruns preserve prior content even after edits. Existing role-account
sessions are scoped only inside the uncommitted fixture transaction and restored
exactly before commit; no public authoring bypass is introduced.

The two introductory lessons include original PDF/DOCX/PPTX resources, safe text
previews/downloads and an optional embedded
[Harvard CS50P Functions and Variables lecture](https://www.youtube.com/watch?v=JP7ITIXGpHk),
listed on the [official Week 0 course page](https://cs50.harvard.edu/python/weeks/0/).
The external video is linked/embedded, not redistributed. Provider availability
is outside local verification. All Kody lesson text and downloadable exercises
are original. Fixture sources live outside `public/`; private copies use normal
attachment storage and authorization. DOCX/PPTX text previews do not reproduce
the full original layout; download for native layout.

Run locally with `php artisan db:seed` or explicitly seed the role accounts,
extra learners and showcase in that order. Production/staging defaults provision
no development accounts or showcase content. Remove development fixture seeders
before deployment if desired; never apply a blanket delete to content with
retained learner/moderation history.

## Verification

Coverage includes renamed-fixture preservation, each role's private picture
flow, invalid/stale files, rollback erasure, deterministic browser/server puzzle
agreement, all 50 level solutions, real showcase publication/media previews,
responsive menus/theme/feedback, upgrade and restore rehearsal. The downloaded
PDF was rendered with Poppler, DOCX through a hidden read-only Word export,
and every PPTX slide through the bundled presentation renderer with package,
layout and import validation. Live services/staging remain deferred.

The local additive migrations and guarded seeds were applied without resetting
accounts or progress. Port 8009 uses a PHP 8.4.26 runtime with GD.
The 2026-10-11 PostgreSQL 18 upgrade/restore drill matched 79 table fingerprints
and preserved its encrypted fixture, XP and ledger; restoration took 3,682 ms.
The full CLI test suite uses a 512 MB memory limit in `phpunit.xml` for accumulated
provider-adapter fixtures; this does not change the web PHP memory limit.
