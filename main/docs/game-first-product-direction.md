# Game-first product direction

Approved by the project owner in chat on 2026-10-03. This later amendment changes
product presentation and sequencing. It does not rewrite the original SRS/SDD
or waive their security, data integrity, approval and financial requirements.

## Experience

Kody starts as a game platform for learning to code. The landing page must offer
a game guests can actually play, with clear feedback and approachable controls.
The signed-in play hub will center daily activity, a streak and a ladder of
levels. Learning, creator content management and monetization extend this core.
The Learning tab has a catalog users can browse and search. Guests can view the
catalog; opening modules routes them to sign-in, where registration is available.

Instructors are content creators who compose learning modules and attach game
or quiz assessments. Creating an assessment means configuring a reusable game
template, not asking every instructor to build a game engine. The interface
should welcome both technical and nontechnical learners.

Coddy's playful introduction and catalog and Duolingo's streak/progression
patterns are references, not branding or code to copy. Use original visuals;
never fabricate user counts, earned XP, streaks, certificates or leaderboard data.

## Template contract

Use a versioned template ID, immutable published instance version and validated
placeholder data. Initial slots: title, instructions, board/scenario, starting
state, goal, allowed commands, limits, hint, success feedback and learning idea.
Quiz templates additionally define prompt, options, answer key and explanation.
Private assessment answers and hidden cases stay server-side.

Template mechanics stay separate from content. Reject unknown template IDs,
unsupported schema versions, invalid geometry, oversized text/boards and arbitrary
scripts. Render text with escaping/textContent. No eval, new Function, uploaded
scripts, arbitrary remote frames or creator HTML. Creators own drafts; publication
uses authorization, moderation and immutable versions to avoid stale grading.

A trial emits only local practice results. Future graded activity needs durable
attempts, concurrency-safe limits, authoritative evaluation and exactly-once
progression. Never convert browser success into direct XP or ledger writes.

## Reuse research

Creator-approval decisions are also resolved: Pending/Approved/Rejected,
rejection preserving existing access, Moderator/Administrator review, and format
checks plus manual credibility review. See [review scope](creator-review-implementation.md).

The project owner prefers adapting suitable internet-sourced games and permits
scratch games. [Blockly Games](https://github.com/blockly-games/blockly-games)
is an Apache-2.0 candidate with Maze, Turtle and other programming games.
Its [Maze source](https://github.com/blockly-games/blockly-games/tree/master/appengine/maze)
teaches control flow. Its [build documentation](https://github.com/blockly-games/blockly-games/wiki/Build)
requires a dedicated build pipeline; importing the full project is not a small
drop-in change. Evaluate it for a richer block editor/template adaptation before
adding dependencies. The first small command-grid template is implemented
locally to establish the content contract and a playable experience immediately;
no Blockly source or assets are copied. Reuse decisions must include license and
asset notices, pinned revision, dependency review and a tested adaptation.

## Phases and traceability

1. Playable guest game, shared playful visual language, separate searchable
   Learning catalog and reusable configurable game templates (this amendment).
2. A06/A07 security and audit primitives; authorized creator drafts, uploads,
   module composition and template configuration (D01–D09, A09/A10, G governance).
3. Durable evaluated assessments and learner participation (C/B), plus validated
   activity driving daily streaks and the level ladder (E).
4. Creator publishing/moderation and monetization (D/G/F), with ledger-backed
   access and rewards rather than mutable UI counters.

Existing A01–A05 account behavior remains part of the foundation. Do not claim
the trial completes C/E/D/F workflows, authoring tools or course progress storage.

## Decisions needed before persisted progression

The owner subsequently approved: at least one server-validated game or quiz
completion qualifies a day; days end at midnight Asia/Manila; a missed day resets
the streak; no freezes in the first release. Clear levels to unlock the next,
keeping XP/ranks separate. Initial levels follow Sequences → Loops → Conditions.
The level's game objective clears it; an associated practice quiz reinforces
learning and qualifies daily activity without skipping a game objective.

- Qualifying activity: which verified game/quiz/module completion counts?
- Daily boundary: account timezone or a fixed business timezone, and whether
  missed days have grace/freeze behavior.
- Ladder thresholds, reset behavior, prerequisite/unlock rules and relationship
  to the SRS rank/XP system; avoid granting the same achievement twice.
- Creator moderation, allowed attachments and approved quiz/game scoring rules.
- Existing SRS language-list, application-state and account-deletion conflicts.

Streak and level-unlock rules above are now resolved and implemented; the
remaining scoring/publication decisions do not block the public trial,
template contract or catalog UI. See [implementation scope](play-implementation.md).
Record an explicit amendment when they are resolved.

## Creator publication amendment — approved 2026-10-03

Approved Instructors author modules; Contributors author coding challenges.
Moderator/Administrator approval gates both first publication and revisions of
published modules. D03's undefined Active label means Published for archiving;
archived modules block learner access while preserving history. These decisions
supersede the older authoring/publication ambiguity described above. See the
[creator studio implementation](creator-studio-implementation.md) for the
versioned studio, template attachments, review queue, inbox and delivered scope.

The owner also approved applying the same review policy to new courses and
published course revisions. Version 1 coding challenges support Python, Java
and C++; provider compiler IDs remain an integration configuration task.

## Course learning amendment — approved 2026-10-03

Published content and verified Active accounts resolve B03/B04's undefined
Active content state. D08 permits owning Instructors to archive Published courses;
existing enrollees retain access and progress while browsing/new enrollment stop.
Individual archived modules remain blocked. Currently authored courses offer
free enrollment; paid enrollment awaits pricing and the KodeBit ledger. See
[course learning implementation](course-learning-implementation.md).

## Challenge publication amendment — approved 2026-10-03

Coding challenges use Draft/Published/Archived/Deleted lifecycle states and
separate Draft/Pending/Approved/Rejected revision review states. Published is
approved and archive-eligible. Published replacements require Moderator/Admin
review; the approved version remains available while the replacement is pending,
including in B06 browsing. Future submissions retain their original revision.
See [challenge studio](challenge-studio-implementation.md).

## Challenge participation amendment — approved 2026-10-03

Every confirmed submission receives evaluation/feedback. Only one evaluation may
be active per user/challenge context; committed work continues after leaving the
browser. Standard attempts are capped at three across all published revisions;
each future weekly event receives an independent three-attempt budget. The current
release offers free participation to verified Active Learners, Contributors and
Instructors. Paid access, rank gates and prerequisites await supporting modules.
See [challenge submissions](challenge-submission-implementation.md) for delivered
scope and provider activation requirements. The weekly workflow is implemented
in the [weekly event follow-up](weekly-challenge-plan.md).

## Weekly event amendment — approved 2026-10-03

Weekly windows run Sunday 00:00 to the following Sunday 00:00 in Asia/Manila,
including the start and excluding the closing time. The first event release offers
free participation with independent three-attempt budgets and verified results;
XP, rank changes and KodeBit rewards stay deferred. Judge0 plan/credential setup
is also deferred. The owner approved future Scheduled events while one current
event is active; each event becomes immutable at its start. See
[weekly events](weekly-challenge-plan.md) for implementation.
