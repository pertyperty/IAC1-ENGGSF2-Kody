# Tower and staff review navigation

The 18 November owner request groups related improvements into focused PRs.
This batch improves finding existing work: a Tower list and a pending staff inbox.
It follows the [learner consolidation](consolidated-learner-flow.md) and preserves
[current learning policy](current-learning-policy.md). It supports existing Tower
play and A09/A10, D01/D05 and C01 publication/application review workflows; it adds
no admission, approval, reward or credential-verification policy.

## Learner navigation

Use **List view** on the map to scan the selected track in ascending order. Each
row contains its number, published title, concept and current state. Cleared levels
stay replayable; later locked levels have no play link. The first uncleared level
is marked as the current step. Map/list preference is session-only, like track
choice, and does not grant progress. The list is a server-rendered page; signed-in
navigation works without JavaScript. Guests retain the existing browser-only trial
clearances and three-level sequencing. Later levels link to the account invitation.

Both views use `TowerProgression::snapshot`; direct play still runs the existing
server authorization. Unpublished floors are omitted. Empty tracks are explicit.
No extra floors or progression systems are introduced.

## Staff navigation

**Review inbox** replaces five primary navigation links. Its GET filter chooses
all pending items, lessons, courses, coding challenges, Instructor applications or
Contributor applications. Results are paginated at 15 and ordered by submission
update time, category and ID. Content must still be Draft/Published with a Pending
latest revision. Own content/applications and superseded pending revisions are
excluded. Query projections contain only routing IDs, category, title, username,
revision number and time; they do not load bodies, hidden cases, personal names,
email or credential paths.

Existing verified Active Moderator/Administrator policies and current-session
middleware govern the inbox. Each item opens its existing specialized review
page, where current authorization and review/version/credential controls still
apply. The expandable history section keeps every specialized queue accessible.
Privacy and financial reviews retain their separate destinations.

## PR batches and remaining work

1. Learner integration: bring the previously verified learner branch into `main`
   through PR #41; #40 had merged into its parent after that parent reached main.
2. Navigation discovery: this Tower list/inbox batch, based on the learner branch.
3. Engineering tooling: a separately reviewable scoped static-analysis check.
4. Measured optimization: collect representative device/query evidence, then
   consider page-specific loading or financial-report aggregation in focused PRs.

Reviewed beginner content and human learning observations need a curriculum owner.
Terms acceptance needs approved operator facts and immutable reviewed text first.
Staged operations and production Administrator recovery need authorized operators
and environment-specific evidence. Provider activation retains the independent
[release gates](release-evidence-index.md). These are not completed by UI changes.

## Verification

On 18 November 2026, using PHP 8.4.27 and a disposable PostgreSQL 18 cluster:

- 174 affected backend tests / 2,006 assertions passed, and the same 174 / 2,006
  passed with isolated testing configuration and route caches. These are separate
  runs, not additive coverage totals. Temporary cache files were cleared.
- Both new Chromium workflows passed: guest/list/track/keyboard navigation and
  submission/filter/existing-review-page navigation. All five role navigation
  workflows also passed in desktop/mobile light/dark modes.
- The new Tower workflow passed in Chromium, Firefox and WebKit, including
  320/390/1280px accessibility and overflow checks. The inbox workflow passed
  light/dark checks at all three widths. Synthetic screenshots remain in ignored
  browser evidence storage.
- All 64 frontend tests, normal/Office production builds and full Pint passed.
  All 86 relative Markdown targets across changed records resolve.

Browser verification caught a click-through map overlay and then cramped bottom
controls at 320px. The view switch now sits in the wrapping title row, where real
clicks pass in every engine. The WebKit check uses focused-link Enter activation,
without assuming browser-specific Tab preferences. A new identity-state test was
corrected to obey database activation constraints and use a fresh suspended
account. The first cached run used local configuration; the recorded passing run
explicitly builds testing/array-session configuration. No protections were relaxed.

These are focused local development checks. The complete backend/browser/restore
suites remain required in the new PR's CI; this record does not claim their final
result or real-device/screen-reader/production readiness. This batch requires
no schema migration, environment key or provider activation. It inherits the queue
probe migration and deployment order from the earlier consolidation.
