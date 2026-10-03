# Play hub, templates and progression

Implements the owner's 2026-10-03 game-first amendment, supporting the direction
of B01/B02/B05 and E01/E05/E06 without claiming their full original scope.
The public landing game is a real command-grid exercise. Learning has its own
searchable catalog; modules require current authenticated account sessions.
Instructors are presented as content creators, with publication clearly marked
as future functionality.

## Template slots

`config/learning.php` contains three built-in version-1 practice instances. The
`command-garden` template accepts title, instructions, grid, path, start, goal,
crystals, command limit, mode, hint and learning idea. Sequences, loops and
conditions share one runtime. `choice-quiz` accepts title, question, options,
answer and explanation. Creator authoring will replace the built-in instance
data with authorized, validated, immutable published versions. No upload or
publication endpoint exists yet.

Browser validation rejects unsupported IDs/versions, invalid geometry, duplicate
quiz options and oversized data. Text uses escaped Blade and textContent.
No arbitrary source code executes. The bounded PHP game evaluator is authoritative
for signed-in wins. Frontend tests compare browser and PHP outcomes for 96 cases.
Practice quiz answers are deliberately public; they are not academic grades.
Future graded quizzes must keep their private keys server-side.

## Approved progression

The owner approved at least one server-validated game/quiz completion per Manila
calendar day, a midnight Asia/Manila boundary, reset after a missed day and no
freezes. Game-objective clearance unlocks the next level in the initial
Sequences → Loops → Conditions ladder. Quizzes qualify daily activity without
skipping the associated game. XP, rank and KodeBits remain separate.

Migration `2026_10_03_000004_add_learning_progression` adds streak state, unique
level completions and unique per-level/type daily activity records. Exact input
and template version are retained for accepted outcomes. No balance or reward
ledger is modified. Constraints prevent negative/inconsistent streaks and
duplicate records. Account deletion cascades this learner-specific state.

Every write locks and rechecks the account's current Active/verified status,
session fingerprint and expiry, then validates prerequisites and replays the
submitted instructions or checks the practice answer. Activity, clearance and
streak updates commit atomically. Replays can qualify a new day; retries on the
same day cannot increment the streak or grant another clearance. The hub shows
zero for an expired streak without discarding its historical longest streak.
Endpoints require CSRF and independent rate limits. Client-submitted owner,
streak, score, role and success flags have no authority.

## Verification and operation

Local full PostgreSQL suite: 154 tests, 989 assertions. Eleven frontend template
tests pass, including browser/server agreement. Actual overlapping PostgreSQL
requests prove progression idempotence. Tests cover midnight, missed days, stale
authorization, wrong outcomes, level skipping, other-user input, rollback and
constraints. Cached routes/config/views pass the focused account/progression
suite. All seven migrations execute on empty test databases in concurrency tests.
Pint and Vite production build pass. CI now runs template tests and cached account
and learning checks; remote CI and production deployment are not yet verified.
The existing optional fontaine warning remains non-blocking.

Browser checks confirm the public trial wins, off-path failure/retry feedback,
search behavior and guest-to-login routing. Keyboard controls and reduced-motion
behavior are supported. This is not a claim of a completed accessibility audit.
The implementation uses original code/assets; Blockly Games was researched and
documented as a reuse candidate, not imported.

## Next implementation prompt

Implement A10 instructor/creator approval and audited review access. The owner
approved Pending/Approved/Rejected labels, rejection preserving Learner access,
Moderator/Administrator review and format checks plus manual credibility review
without an invented institutional-domain list. Keep credentials private, recheck
authorization/state under locks, prevent duplicate review, queue notifications
atomically and cover security/error/concurrency paths. Then add creator-owned
module drafts and template-instance attachment under the Content module, resolving
source publication/moderation conflicts before persisting their transitions.

Creator-published adventures now join Learning and use the same server-validated
daily-activity writer. They do not skip the built-in ladder. See
[creator studio implementation](creator-studio-implementation.md).
