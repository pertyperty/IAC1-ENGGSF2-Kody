# Creator insights, site support and workspace polish

Owner-requested follow-up, 2026-10-12. This record extends the shared interface;
it does not activate deferred providers or change purchase packages, rewards,
moderation decisions or published assessment rules.

## Creator dashboards and instructor play

Instructor and Contributor dashboards have private aggregate reports with 7-,
30- and 90-day windows. `CreatorInsights` rechecks the current account/session
and creator policy, scopes every query to owned content and excludes the owner's
own participation. Charts use Asia/Manila calendar days, fill missing days with
zero, and include expandable daily tables for keyboard and screen-reader access.
No learner identities, source code or other creators' content are returned.

Instructor reports show distinct first-time readers, recorded game/quiz activity,
first article completions and course starts. The first-opening chart counts the
first recorded authorized module opening, not every page view. Activity can count
again on another day; it is not a count of unique completed modules. Article
completion is reported separately from assessed activity.

Contributor reports show distinct quest participants, confirmed submissions,
current verified passes and `Passed / (Passed + Failed)` pass rate. Queued,
Evaluating and unavailable results are excluded from that rate. Charts group
attempts by original submission date, including weekly contexts. These are
current retained records, including older revisions, rather than immutable
historical financial reports. Erased records no longer contribute.

The [26 October refactor](refactoring-review-2026-10-26.md) retains these metric
definitions while reducing reads. Instructor reports skip submission queries;
Contributor headline counts share one PostgreSQL filtered aggregate, with the
two daily charts queried separately. Regression checks cover empty windows,
returning participants, excluded dates and the bounded submission-query count.

Instructors can open the Tower from their sidebar and creator dashboard. Their
dashboard remains a creator workspace; the participant HUD appears only during
Tower play. Existing server-validated sequential progression and streak rules
apply, with no new Tower XP or KodeBit grants. Other staff roles retain their
existing workspace destinations.

## Safe Administrator site management

`/manage/site` is available only to verified Active Administrators. It provides
one plain-text public announcement and read-only operational summaries.

Announcement edits require the current password, explicit confirmation, a locked
current account/session and singleton record, and an unchanged record version.
An audit event preserves the before/after public fields. Title/message bounds are
80/500 characters. Links are restricted to Help, Contact or Learning; arbitrary
URLs, uploaded markup and settings are unsupported. Blade escapes notice text.
Notices are excluded from authentication, focused editing, errors and Tower play.

The operational view separates ready, delayed and reserved queue records, with
stored totals/oldest timestamps, seven-day job
failure counts, four integration configuration switches and recent announcement
changes. It exposes no job payloads, private error logs or provider credentials,
and performs no provider calls. A switch is not proof of provider readiness;
stored queue totals are not a worker health check. These controls cannot alter
roles, balances, deployment secrets or live integration settings.

The additive `2026_10_12_000002_add_site_announcement.php` migration creates a
single inactive default record. PostgreSQL checks enforce singleton identity,
positive version, permitted destinations and nonempty active notices. The actor
foreign key and durable audits retain accountability. Rollback removes an unused
default table, but refuses to drop managed state after any edit; retain the table
when rolling application code back. Apply through the normal reviewed migration
process. No production or owner database is reset by verification.

## Public support and suspension appeals

`/contact` is public and uses `config/support.php` for
`kody.support@gmail.com`. Its links open the user's email app, with separate
support and account-appeal subjects. Users may copy the address into webmail.
The page supplies useful request details and directs creators to existing Help.
It does not store requests, send through SendGrid or claim automated review.

Sign-in and suspension notices link to Contact/appeals without disclosing account
existence. An appeal does not restore access. Staff still investigate through
existing governance history and use authorized reinstatement actions. Passwords,
verification codes, keys and full payment details must not be included in email.
Support staffing and response times are not promised by the UI.

## Shared interface changes

- Wallet packages use distinct blue/purple/amber accents, explicit tier labels
  and reusable coin art without invented popularity claims or changed prices.
  The KodeBit component supplies exact server amounts; presentation decoration
  adds the configured icon to currency mentions while leaving lesson text,
  input values, code, games and file-size `KB` labels intact.
- Quizzes use collapsible questions and a bounded editor; all field values remain
  serialized even when collapsed. New questions open directly. Program tracks,
  instruction lists and test-case collections scroll within bounded regions.
  Creator save actions stay visible without covering the step navigation.
- Creator/staff cards, report grids, Welcome paths and Contact topics use wider
  multi-column layouts that stack at mobile widths. Adjacent actions have explicit
  spacing; shared SVG icons and semantic theme tokens remain authoritative.
- Page guides illuminate a rectangular outline of the actual target. Long forms
  and collections introduce a visible heading/control rather than a huge circle.
  Guide definitions are route-specific, including Welcome, Contact and site
  management. Focus trapping, Escape, skip/replay and native modal isolation remain
  intact. Companion hide/reveal uses short opacity/scale transitions, with immediate
  reduced-motion behavior and persisted visibility preferences.

## Verification

Focused PostgreSQL checks cover authorization, password/confirmation/stale edits,
replaced sessions, escaped notices, secret-free summaries, owned chart data,
Manila date boundaries and instructor progression. Frontend replay checks and
both application/Office builds pass. The upgrade/restore rehearsal preserves
existing history and verifies the inactive notice default across 81 tables.
The reviewed additive notice migration was applied to local `kody` at
`127.0.0.1:5432`, preserving existing records; the announcement remains inactive.

Browser regression coverage includes role workflows, desktop/mobile layouts,
keyboard accessibility, both themes, instructor charts and Tower wins, mixed
quiz editing, announcement preview/publication, external-email appeals and
rectangular guide alignment. Final suite counts are recorded in the PR evidence.
All tests use isolated synthetic accounts/databases and disabled providers.
