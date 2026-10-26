# Simpler learning and creator credibility

Owner amendment, 25 October 2026: simplify the shared interface and wording,
show pseudocode directly, bound instruction growth, identify content creators,
and let staff issue reviewed qualifications badges. This also implements the
independent follow-up work in the [22 October review](improvement-review-2026-10-22.md).

## Shared interface and games

The final shared stylesheet establishes readable body/help text, larger control
labels and consistent spacing. Help search, topics and tour actions are separated.
Garden board-help text no longer inherits a low-contrast terminal color. Quiz
feedback is readable, with detailed reward rules in an optional disclosure.
Primary catalogs use lessons and courses instead of repeated adventure/journey
metaphors. Single-stage Tower lessons omit duplicate navigation and stage labels.
Guides remain keyboard accessible and replayable, but open only when requested.

Every game shows pseudocode above Run. Instruction buttons remain shortcuts;
they no longer substitute for a readable program. The pseudocode viewport has a
fixed height and scrolls internally. Reordering and simulator command-text editing
are secondary panels, and variable state is available on demand. Existing
Step/Replay/Restart use the same bounded interpreters and cannot save progress.
Only Run submits an assessment. The Tower stays free, preserves old clears and
does not issue new XP/KodeBit rewards. Existing lesson/weekly rewards are unchanged.

Quiz authors can supply optional retry feedback explaining a misconception or
another example. This is typed plain text, bounded to 1000 characters. New quizzes
using it use version 3; existing version 1/2/3 snapshots and grading remain valid.
Correct answers keep their normal explanation. No learner answers are collected
for analytics by this work.

## Creator identity and credentials — A06/A09/A10, B03/B04/B07, G05

Published lesson/course/challenge cards identify their creator and current role.
Lesson, enrollment/access and challenge pages link to the public creator profile.
Cards avoid nested links; the independently linked byline is on the content page.
Catalogs eager-load badge data so author attribution does not add a query per card.

Public profiles show username, current role, optional line of work, bio and
experience/accomplishments, plus counts and up to six recent publications per
content type. Private names, email, application messages, qualification documents,
review notes and financial/attempt histories are excluded. Creator-provided claims
are labelled as such. Fields start empty and require explicit publication. They
can be cleared later; updates recheck the current session and a locked version.
Only verified Active Contributor/Instructor/Moderator/Administrator accounts have
public creator pages. Suspended/Archived/Deleted identities return 404. Draft,
archived and staff-withdrawn publications are excluded from discovery.

The role label and the blue qualifications check are distinct signals. Only
Moderators/Administrators can issue or revoke a check for a current Instructor or
Contributor, and nobody can review themselves. An approved role application with
an available private supporting document, unchanged application/badge versions,
manual qualifications acknowledgment, current reviewer password, confirmation and
private review reason are required. Approval of a role alone never grants a badge.
The locked account/session/policy is checked again inside the transaction. The
public check states that qualifications were reviewed, not that every profile
claim or lesson is certified. Badge visibility follows the current account status
and matching role; staff role labels are not self-issued qualifications checks.

The existing application upload boundaries remain: actual PDF/JPEG/PNG validation,
configured 5 MiB default limit, generated private paths, no public document URLs,
staff-only downloads, durable versions and deletion through the existing private
file-erasure workflow. Retained Instructor submissions now have a paginated staff
history with application-scoped downloads and an audit for every download. Both
current and historical paths reject traversal and public disks. Contributor
resubmissions retain their existing separate application/decision records.
This is manual qualifications review, not an automated authenticity or malware
verification service.

Account deletion removes the public profile, badge proof references and personal
review list before removing applications and queuing document erasure. Existing
audit records and legally retained financial/content references keep their prior
policies. No retention expiry or credential purge is invented here.

## Personal review list — B01/B04/B05

Eligible participants can save an unlocked Tower level with “Review later”. The
owned list is bounded to 100 items, idempotent, and stores the saved revision.
Links recheck availability and progression, open the current version and identify
updated lessons. Removing an item affects only the current user's list. Saving
an item never creates a clearance, assessment pass, streak, XP or KodeBits. It is
a learner-selected practice list, not a mastery diagnosis or adaptive placement.

## Operations and verification

`kody:operations-heartbeat` is scheduled every minute. It records scheduler
activity and transactionally dispatches one outstanding probe to the database
queue. A stopped worker cannot accumulate probe jobs. Only execution of the
probe records worker success. Administrator site management shows Unknown,
Recent (within three minutes) or Stale, separately from queue totals. No public
probe gains provider calls or private diagnostics. This monitors the database
queue consumed by the normal worker, not every possible custom queue. A failed
probe needs the existing failed-job recovery procedure; no automatic retry loop
issues extra probes indefinitely. Real alert delivery and supervision still need
staging verification.

All PHP-backed frontend tests use the same `KODY_TEST_PHP` override. This fixes the
Windows discrepancy between shell `php.bat` and Node's direct executable lookup.
A fixture that does not return JSON fails with a runtime/extension diagnostic.
Failures are not hidden by stripping PHP warnings.

`npm run test:browser:engines` creates an isolated database and runs the small
`@cross-browser` smoke set in Chromium, Firefox and WebKit. The full Chromium
suite remains separate. CI installs all three engines and runs both sets. Actual
Safari/iOS/Android, screen-reader sessions, learning-effectiveness studies and SRS
capacity/availability measurements remain separate evidence requirements.

## Migration and rollout

`2026_10_25_000001_add_creator_profiles_and_learning_support` creates four additive
tables: `creator_profiles`, `creator_verifications`, `tower_review_items` and
`operations_heartbeats`. It rewrites no accounts, content, credentials, clearances
or ledger rows. Constraints protect lengths, versions, review ownership, proof
kind, and revision/level relationships. Migrate before serving the new code.
Rollback drops only the new feature data; prefer a compatible application release
and reviewed backup when these features have real records.

No live provider, deployment or purchase is enabled. Reviewed legal text/operator
facts are still required before Terms acceptance can be implemented and activated.
Actual launch-course publication needs designated creators/staff. Static analysis
and a release-evidence index remain separate maintainability/operations proposals;
they are not substituted for the concrete learning changes in this amendment.

## Verification record

Final checks on 26 October used PHP 8.4.27 and a separate loopback PostgreSQL 18
cluster. CI retains PostgreSQL 17 as its reference environment. No live providers
were contacted. Results are separate runs, not summed coverage totals:

- Complete backend: **1,297 passed**, 10,235 assertions.
- Cached configuration/routes: **95 passed**, 949 assertions, including new
  profile/badge/erasure, review-list, heartbeat and quiz-remediation paths. Cache
  files were isolated from the running application and removed afterward.
- Frontend: **64 passed** with the explicit PHP executable.
- Composer metadata/platform requirements and Pint passed; both normal and Office
  production bundles built successfully.
- Populated legacy upgrade and **85-table** custom-format restore passed, retaining
  encrypted fixtures, progress and ledger integrity. Newly added feature tables
  start empty in that upgrade rehearsal; focused tests separately exercise their
  populated workflows and erasure.

The complete Chromium suite passed **67 workflows**, including all five roles,
mobile themes/accessibility, creator publication, private media, role applications,
badge grant/revoke and owned review lists. The final Chromium/Firefox/WebKit
learning matrix passed **6 checks**. After the last copy/readability and empty
profile refinements, **4 affected browser workflows** and **38 interface/quiz
backend checks** passed again. The mobile Garden and creator profile screenshots
were inspected visually. Reports/screenshots are retained in ignored private
verification evidence; these are local results, not a claim of remote CI success.

PR #34's first CI run stopped at the dependency audit: the retained Vite 8.3.1
lock entry matched new development-server/editor advisories, including
[GHSA-587h-7wmj-h5pm](https://github.com/advisories/GHSA-587h-7wmj-h5pm).
The follow-up raises the Vite requirement and lock to patched 8.3.5 with its
compatible build dependencies. A clean isolated `npm ci`, zero-vulnerability
`npm audit` and both production builds passed with the patched lock. CI is rerun
through the ordinary PR checks; the security gate remains enabled.

Early runs found stale text locators, a duplicate footer-link locator and
an inaccessible scrollable output region. Those were corrected without disabling
accessibility or workflow checks. Concurrent asset rebuilding also invalidated an
early backend render; the recorded full backend pass used a stable asset build.

### Local installation

A new private custom-format backup was checked with `pg_restore --list` before
installing the single additive migration on loopback local database `kody`.
Fingerprints of **80 pre-existing application tables** were byte-equivalent
before/after migration, excluding the migration registry itself. Accounts,
passwords, sessions, roles, credentials, learner history and financial rows were
not rewritten. The four new tables start empty; no public bio or qualifications
badge was automatically created for existing users. Private backups and test
evidence remain ignored local files.
