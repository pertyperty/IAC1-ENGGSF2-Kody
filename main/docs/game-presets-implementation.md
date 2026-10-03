# Administrator game preset workshop — G08–G10

## Approved scope

The owner approved the first preset release using existing server-validated
game/quiz win rules, rewards explicitly Deferred, and no XP/KodeBit grants. This
amends G08's requirement to define reward structures for this release. G09 updates
preserve existing module instances; G10 inactivation prevents new use and retains
references. It does not invent monetary formulas, badges or rank progression.

Administration authorizes management at `/manage/game-presets`; the Games service
owns typed configuration and snapshot selection. Content owns module instances
and publication review. Gamification continues to replay validated outcomes using
the existing Manila streak writer. Presets never grant access independently.

## Configuration and creator experience

Verified Active Administrators create presets with a unique 100-character name,
activity title and one of the three existing garden trail rule sets or a two-choice
practice quiz. Gardens configure instructions, hints and learning feedback;
geometry, objectives, movement, loop repeat and conditional collection are the
existing runtime's rules. Quizzes configure question, distinct choices, correct
answer and explanation. Text bounds match the existing template contract.

Scoring means a server-validated objective/answer win. Participation retains the
existing module/course policies for verified Active participant roles. Administrators
cannot upload executable rules or override reward amounts, roles, ownership or
publication status. General custom game engines, custom geometry, graded quizzes
and arbitrary scoring/participation rules remain outside this release.

The workshop lists current/retired presets and has a saved playable preview with
no completion endpoint. Dynamic fields follow the selected game/quiz type; server
validation works independently of JavaScript. Prompts render escaped/plain text.
Practice answer keys remain visible; they are not academic grades.

Instructors may choose a workshop preset when saving a module, override the activity
title and keep all other preset defaults as a full assessment snapshot. Existing
creator garden prompt customization and manual quiz authoring remain available.
The selector lists the first 100 Active presets by name, labeled with exact current
version and assessment type. Saving checks that version is still current/Active.
A changed/inactive selection needs a reload/new selection; already-saved drafts,
publication review, pinned course lessons and completion keep their old snapshot.
Inactivation does not withdraw published lessons; G06 staff withdrawal owns that.

## Integrity and security

An additive migration creates `game_presets`, `game_preset_revisions` and nullable
`module_revisions.game_preset_revision_id`; built-in instances need no backfill.
Foreign keys retain references; a composite pointer prevents cross-preset current
revisions. Positive/unique revision numbers, case-insensitive trimmed name
uniqueness, constrained status and fixed reward/scoring/participation fields guard
PostgreSQL invariants. A trigger rejects UPDATE/DELETE of immutable preset revisions.

Every save creates a new revision, even when unused. Inactivation is always soft,
also for unused presets; inactive names remain reserved for history. No hard-delete
or reactivation endpoint is supplied. Administrators can create a separately named
replacement. Version and account/session checks occur under locks. Module snapshot
selection uses the same preset lock as update/inactivation inside the module write
transaction, preventing a new stale or inactive instance. Existing completions use
their assessment snapshot rather than live preset configuration.

Preset changes and minimal audit records commit atomically; audit failures roll
back. Duplicate names produce a validation error after transaction rollback.
Admin routes enforce policies, current sessions, CSRF and shared ten-per-minute
mutation throttling. Creator selection never exposes administration to Instructors.
Lists/history are bounded/paginated, and privileged responses are private/no-store.

## Deployment and verification

Run normal migrations and rebuild assets/caches. No new dependency, provider secret,
queue, scheduler or seed credential. Migration rollback refuses to erase preset
history. Application rollback must retain inactivation-aware snapshot selection
if managed presets remain usable; existing saved module assessments remain usable
through the established game/quiz runtimes.

Focused preset/module checks passed 77 tests / 588 assertions, including real
overlapping PostgreSQL duplicate-name creation and update/inactivation races.
Tests also cover roles, stale/restricted sessions, invalid/injected configuration,
escaped previews, immutable/cross-preset constraints, atomic audit rollback,
confirmation/CSRF and retained module completion after update/inactivation.
Full PostgreSQL verification passed 717 tests / 5,158 assertions. Cached
configuration/routes/views passed 156 preset, moderation and learning tests /
1,289 assertions. All 26 migrations execute on clean PostgreSQL test databases.
Pint, 16 frontend tests, production asset build and diff whitespace checks passed.
Remote CI, production deployment and browser visual checks remain unverified.

## Next implementation prompt

The owner subsequently approved public categorized Help; see
[B11/G11–G13 implementation](faq-implementation.md). Follow its next prompt for
content feedback. Keep reward formulas, monetization and live Judge0 activation in
the deferred-feature register. Do not present the preset workshop as a custom game
engine or as completion of the deferred reward requirements.
