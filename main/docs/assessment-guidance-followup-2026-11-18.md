# Assessment guidance and lesson workflow follow-up

This continues the [learner-first rework](learner-first-rework-2026-11-18.md)
within PR #47. It supports B03/B05 learning and assessment, D01-D08 lesson/course
publication and the existing A09/A10 role-transition presentation.

## Corrections

Guided number, string and stack tasks previously displayed the generic command
reference, including commands their pinned task rules reject. The reference now
lists the task's actual allowed instructions. Custom activities retain their
wider syntax. Different worked examples remain separate teaching material.

Once a program succeeds, checking a concept answer no longer replays its entire
animation. Both interpreters still recompute the result; eligible completion
still submits the program and answer for authoritative server replay. Editing
instructions, changing movement rules or resetting clears the selected concept
answer. A wrong answer keeps keyboard focus on the selected option; successful
checking restores the Run program label. Step, Replay and Restart remain local
previews and do not save results.
The movement game's Reset button now uses its visible text as its accessible
name, replacing the conflicting Start over label found by the browser regression.

The lesson studio, examples, publication queues, published lesson link and archive
confirmations now use lesson/course terminology. Create a lesson, Archive lesson,
Archive course and Cancel name the action directly. Role-transition buttons open
the lesson or challenge studio. Explanations are shorter, while archival effects,
staff-withdrawal distinctions and retained-history information remain explicit.
Existing authored titles, stored revisions, route identifiers and curriculum
snapshots are not rewritten.

## Boundaries

No schema, authorization, locking, queue, reward or provider configuration changes.
Private credentials, publication review and immutable assessment rules retain
their existing safeguards. The owner's development database was not migrated or
reset. Third-party/API implementation remains paused; Judge0 is the first intended
later activation.

This incremental correction follows the visible target/program and contextual
reference approach compared with CSS Diner and Flexbox Froggy in the original
rework. Their retained 18 November screenshots were inspected again; no new public
interaction study or learner study is claimed.

## Verification

Local verification uses Windows, PHP 8.4.27, Node 22.20.0 and disposable loopback
PostgreSQL 18.6 databases. Ignored logs use the
`storage/app/private/learner-followup-*` prefix.
The verified application/test source is
`c9b06c4cc831cc7ae5ec3dadb5a382b40b3c6558`; the following documentation commit
records these results.

- All 65 frontend tests passed, including all ten guided-task browser/server
  reference and hostile-input parity checks.
- All 145 affected backend cases passed with 1,231 assertions. Selection uses
  test-class filters with complete fixture discovery. The initial file-only
  selection omitted shared test fixtures and retained one old archive label;
  the corrected run covers those failures.
- Full Pint, the existing scoped level-5 static check and both production builds
  passed. Dependencies are unchanged; the inherited npm advisory disposition in
  the deferred register remains open.
- The broader Chromium run passed 22 of 24 workflows, including custom Arcade
  wins, creator/staff/learner publication, both themes and all five role screens.
  The two failures were a new test's incorrect studio URL and its instruction
  selector. The focused rerun then exposed Reset's conflicting accessible name.
  After correction, all four learner-first workflows passed, including the
  movement/Arcade answer checks, local-preview state, reset and custom syntax.
  This is not a claim of a single clean final 24-workflow run.
- The new Arcade answer-flow regression passed in Chromium, Firefox and WebKit
  (three checks), including focus, clearing answers after edits/reset, no replay
  during answer checks, no practice POSTs and 320px accessibility/overflow checks.

Current task-reference, studio and mobile concept-check captures are retained in
`storage/app/private/learner-first-evidence/followup-*.png` alongside the original
public references. They were visually inspected for the contextual command list,
clear authoring action and existing two-column program/world arrangement.

The earlier broad-suite limits in the original rework still apply. These local
checks do not establish remote CI, production readiness or teaching effectiveness.
Human observation of concept transfer, editorial sign-off, real-device checks,
production legal/retention review and staged operational evidence remain in the
[deferred register](deferred-features.md).
