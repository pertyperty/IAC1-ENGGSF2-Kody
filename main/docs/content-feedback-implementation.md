# B10 — Content reactions

## Approved contract and ownership

User Interaction owns feedback and consumes Content's current entitlement rules.
The owner chose one replaceable Like/Helpful/Favorite reaction and deferred numeric
ratings, resolving B10's toggled Like versus overwritten rating ambiguity. The
owner then delegated the eligibility decision to platform judgment. Verified
Active Learners, Contributors and Instructors need current access plus a
server-recorded authorized content opening or validated completion; a course also
requires enrollment. No guessed numeric scale, reward or rank formula is added.

Module and challenge delivery record the first authorized opening. Weekly play
qualifies for its source challenge. Enrolled course outlines qualify for the
course; trusted pinned lesson visits qualify for the module and course. Existing
course progress, validated module activity and completed Passed submissions are
recognized without inventing historical visit rows. Enrollment alone, catalog
browsing, creator/staff previews, another user's history and browser claims do not
qualify. Reviewer roles may see aggregate module/challenge feedback but cannot
react or record learner participation through previews.

Eligibility is checked again under account/content locks at every change. Staff
withdrawal and unavailable module/challenge lifecycles block changes. Archived
courses remain available to their existing enrollees, consistently with B03/B04.
Reaction identity belongs to the content, across approved revisions and course
placements. Counts describe retained accepted reactions, including accounts later
archived or suspended; account deletion removes their reactions and access proof.
There is no public list of reactors or personal information in feedback responses.

## Implementation and persistence

`ContentFeedback` centralizes eligibility, proof, state and transactional writes.
Form Request validation, participant authorization, current-session enforcement,
CSRF protection and a 20/minute authenticated route protect the shared endpoint.
Native POST forms work without JavaScript; progressive enhancement updates counts
without losing a learner's current game practice. Aggregate values and labels are
rendered as text. Response targets/versions/choices/counts are validated; stale
or revoked access requires a reload. Redirects use fixed internal routes.

Additive migration `2026_10_03_000025_create_content_feedback.php` introduces
typed `content_accesses` and `content_reactions` tables. Exactly one matching
module/course/challenge foreign key, per-user target uniqueness, valid reaction
values and positive versions are enforced by PostgreSQL. First-opening proof
stores no IP, browser fingerprint or repeated visit history. Content references
restrict deletion; account deletion's existing atomic cleanup removes these rows.

Account then content locks serialize concurrent proof and reaction mutations;
different learners never overwrite each other's choices. Expected versions reject
stale changes. Matching immediate retries are harmless. Removing a reaction keeps
a nullable versioned row so old forms cannot recreate it after removal. Counts
come from indexed stored records, excluding null reactions. No rewards, streaks,
money, notifications or numeric ratings are granted by feedback.

Migration rollback refuses to erase populated feedback. Apply ordinary migrations
and rebuild assets/caches; no provider credential, queue or scheduler change is
needed. Production CI/deployment and browser visual checks remain unverified.

## Verification

Feature tests cover participant roles, all three target kinds, replacement,
removal, retry/stale versions, course enrollment and legacy lesson proof, staff
withdrawal/lifecycle changes, privileged input, unavailable sessions, PostgreSQL
constraints and atomic account-deletion cleanup/rollback. Independent PostgreSQL
processes exercise duplicate retries, competing choices and independent users.
Frontend tests cover in-place changes, access/stale failures, same-origin requests,
concurrent clicks, uncertain network retries and malformed responses.

Verification passed: full PostgreSQL regression 781 tests / 5,669 assertions;
final focused B10 checks 36 tests / 326 assertions (including the three additional
completion/weekly/security checks); cached configuration/routes/views 193 tests /
1,491 assertions. All 28 migrations execute on clean test databases. Pint,
20 frontend tests, production Vite build and diff whitespace checks passed.

## Next implementation prompt

Resolve permanent creator content deletion (D04/D09/C07): published content may
have pinned course revisions, enrollment, evaluations, weekly events, reactions
and audit history. Determine deletion eligibility and retained learner access
before implementing irreversible removal. Keep paid purchases, reward formulas,
numeric ratings and live Judge0 activation in the deferred-feature register.
