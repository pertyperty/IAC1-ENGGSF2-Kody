# Tower, media and workspace redesign — 2026-10-10

This record owns the owner's latest game-first interface amendment and the two
explicit tower decisions received on 2026-10-10. Start with the
[implementation map](implementation-status.md) for the rest of the platform.
It supplements B01/B02/B05, A01/A03/A06, D01/D02 and Administration game authoring;
it does not claim the original SRS or SDD files have been rewritten.

## Delivered journey

`/` is a numbered, chaptered 25-level tower for guests and Learners. Successful
Learner sign-in, including explicitly linked Google identity when later enabled,
lands here. Contributor, Instructor, Moderator and Administrator sign-in lands on
their workspace dashboard. Creators retain optional learning access, but their
dashboards show authoring/review/administrative work rather than participant
missions, streaks or learner statistics. Existing three starter lessons, courses,
saved goals and arcade trials remain available through their existing routes.
`/welcome` explains Kody and offers registration/sign-in.

Guests play levels 1–3 with local storage used only as a browser convenience.
Level 4 opens the welcome page. Modifying guest storage cannot save clearance,
streak, XP or KodeBits. Signed-in participant access uses existing verified Active
account/session policies and sequential server-validated unlocks. Staff cannot
submit participant attempts. A client assertion alone never qualifies completion.

The curriculum mixes sequence/loop/condition gardens, painting, arithmetic,
sorting, virtual CLI and Python/Java/C++ practice quizzes. Complexity grows through
larger scenarios and multiple stages. Levels 10 and 20 are bosses with at least
two ordered stages. The terminal remains a bounded virtual filesystem, never a
host shell or language execution substitute. Initial levels are free, with
optional Learning catalog links. No new paid prerequisite or purchase is enabled.

Final-stage wins qualify the existing Asia/Manila daily streak. Intermediate boss
stages do not. Tower wins grant **no additional XP or KodeBits**, including replay;
existing module/challenge reward policies remain intact. Published edits preserve
old cleared levels while an open attempt on an older revision receives a reload
conflict. Stage checkpoints belong to their exact revision. Unique constraints,
current-user/current-level locks and transactional writes protect duplicate clears.

## Authoring and persistence

Administrators edit existing levels at `/manage/tower`: story/objective, typed
stage templates, prompts, scenarios and source notes. Labelled stage controls,
garden painting and unsaved playable previews avoid requiring raw JSON editing.
Advanced JSON remains available for quiz composition or bulk editorial changes.
Preview wins have no completion endpoint and never auto-publish. Publication
rebuilds each instance through the existing game/quiz validation boundaries,
checks garden reachability and uses version checks plus a durable audit record.
The saved-revision comparison is a separate disclosure; the story and source
controls share one column without stretching apart as draft previews grow.

Migration `2026_10_10_000001_create_tower_levels` adds `tower_levels`, immutable
`tower_revisions`, `tower_clearances` and `tower_stage_completions`. Composite
foreign keys bind current/cleared revisions to their own level. Stages are bounded
to 1–4; quizzes to existing 1–10-question/2–6-choice contracts. The existing game
command and scenario limits still apply; this release adds complexity without
arbitrary executable rules. Account erasure removes participant tower history.

`TowerLevelSeeder` installs only missing positions under a PostgreSQL advisory
lock. Reseeding does not replace Administrator edits, identities or progress.
The initial 25 scenarios/questions are original Kody content, not imported course
material. See [the editorial guide](tower-content-guide.md). Administrators can
edit the seeded levels; this release does not implement infinite generation,
position reordering or a separate add/delete-level workflow.

```sh
php artisan migrate --force
php artisan db:seed --class=TowerLevelSeeder --force
```

Run the explicit curriculum seeder during release installation after migrations;
it creates no accounts and contacts no provider. Keep the tables on application
rollback. Default `db:seed` also calls it; local role fixtures retain their existing
local/testing and loopback guards. Do not use local account seeders as production
bootstrap.

## Lesson media

Migration `2026_10_10_000002_add_module_media` adds revision-owned attachment
metadata with an empty-array default, preserving old modules. Instructors may
attach up to five PDF/DOCX/PPTX files, each up to 20 MiB, independently of lesson
format, text, embedded video and existing game/quiz assessments. Explicit retained
attachment selections are copied into the next immutable draft; changing a draft
does not modify published or course-pinned resources.

Private storage defaults to `storage/app/private/module-media`. Select private
local or S3 storage with `MODULE_MEDIA_DISK`; never link this directory into
`public/`. Metadata contains generated paths, original display names, SHA-256,
size, type and escaped-preview text. Downloads stream through authenticated
policy checks with private/no-store and nosniff headers. Learners must retain
current access to the exact published revision, or an authorized course-pinned
lesson. Forged lesson contexts, staff withdrawal, unrelated drafts and old
standalone revisions cannot bypass access. Owners/staff retain authorized review
previews; resource views themselves grant no XP or streak.

PDFs use a same-origin browser viewer with a sandboxed response; original files
download separately. DOCX/PPTX have escaped paragraph/slide **text previews**, not
full office rendering. Images, charts, layout and animations remain in the original
download. Native browser PDF support varies by device; the download remains
available. No external office converter receives private files.

PDF checks reject obvious script/launch/embedded/encrypted content and mismatched
bytes. Office checks bound ZIP entries/decompressed size/XML/text, reject macros,
embedded objects and traversal, and parse XML with network/entity resolution
disabled. These are structural checks, not a certified malware scanner. A future
managed scanner/full-fidelity converter would require separate deployment work.
New uploads from failed saves and dependency-free permanent deletions use the
existing durable private-file erasure queue. Retained revisions and privacy review
inventories keep their attachment metadata/content hashes; never erase a shared
published asset merely because a new draft omitted it.

YouTube URLs become `youtube-nocookie.com` embeds; Vimeo URLs become canonical
player embeds. Exact hosts/IDs are allowed, not uploaded iframe HTML. Other HTTPS
resources open as external links. The server never fetches these URLs. Embedded
playback remains subject to provider availability and the author's embed rights.

PHP needs `zip`, `dom`, `fileinfo` and existing extensions. To allow five maximum
attachments, configure `upload_max_filesize=20M`, `post_max_size=110M` and matching
proxy/request limits, or deliberately use lower infrastructure limits. Production
S3 access/IAM/version erasure, worker supervision and managed restore still need
the existing staged operations verification.

## Interface and identity

Both shells share the icon rail, header, role-filtered links and blue/grey themes.
The desktop rail is collapsed by default, revealing the hovered/focused item and
part of neighboring labels. The expansion toggle persists across pages with a
prepaint preference to avoid width flashes. Bounded scrolling, keyboard focus,
touch expansion and reduced-motion behavior remain available; mobile navigation
uses explicit labelled controls. Header navigation keeps its hide/reveal behavior.

The logo and username now anchor the header. The attached Learner-only HUD shows
daily streak, existing XP/rank and tower clearances; security/editing screens keep
their focused layout. Sticky creator/tower editing steps and responsive split
forms use the available width. Notifications retain toasts plus field/game context.
No cross-page animation or script-based navigation interception is added.

`config/branding.php` is the single logo path/name. `public/images/kody-mark.svg`
remains the current replaceable placeholder; `public/images/navigation.svg` is
original placeholder icon artwork shared by every rail item through one partial.
The rail keeps each icon's width while its text label reveals. Replace assets through configuration
without editing every template. Font files remain pinned local assets.

Name validation now accepts Unicode letters/combining marks and single spaces,
hyphens or straight/curly apostrophes within first/last names. Existing length
limits remain. Login accepts an email or exact-case username using the compatible
`email` POST field. Email lookup is case-insensitive and takes precedence if a
legacy username resembles another account's email; passwords, lockouts, generic
failures, status checks and replacement-session consent remain unchanged.

## Verification

`TowerTest`, `ModuleMediaTest` and `InclusiveIdentityTest` cover all seeded working
solutions, ordered unlocks/boss stages, idempotency/no rewards, revision edits,
role filtering, private/pinned media, unsafe documents, embedded-host spoofing and
inclusive identity rules. Browser journeys add local guest climbing, username
Learner landing/HUD refresh, rail pinning and unsaved Administrator previews,
alongside existing role/publication/learning/accessibility workflows.

Local verification passed 126 focused PostgreSQL checks (1,195 assertions),
including simultaneous tower clears, revision conflicts, erasure, all 25 seeded
solutions and the six failures found by the broader 1,175-test diagnostic run.
Frontend checks passed 51 tests; the production build and Pint passed. All 18
browser workflows passed across the complete run and targeted correction runs,
including private-document publication/download and unsaved quiz/game previews.
The populated upgrade/restore drill matched all 79 table fingerprints on local
PostgreSQL 18; restoration plus verification took 2,788 ms. CI repeats the full
suite, cached routes/configuration, browser workflows and restore on the proposed
commit. Consult its result before merging; the diagnostic run is not a claim that
the final full suite passed locally. These checks are not live-provider,
real-device, capacity or production-readiness evidence.

The new tower/media/identity slice also passed 37 checks with cached routes and
configuration (289 assertions). The final shared rail-icon correction passed
three desktop/mobile/staff/Administrator browser workflows and the rebuilt assets.
