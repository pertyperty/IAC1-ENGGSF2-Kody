# Main integration and CI repair ? 19 November 2026

## State found

Remote main was `df8d119ceee265a4eba0761ccef0dd7b919b0d1b`, the merge of
[PR #50](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/pull/50).
[PR #51](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/pull/51) was merged into
`perf/page-specific-assets`, not main, eleven seconds later. Its merge commit
`b68956f6f143b38fe591d1c0711b14e9fad878d2` was absent from main's ancestry.
A merged PR badge therefore did not mean that main contained the game UI or
same-tab legal links. The changes remained intact on the feature branch.

The repair starts from main and merges that actual feature-branch head. It does
not cherry-pick or recreate the reviewed application changes. Delivery is directly
to main through [PR #52](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/pull/52).
The remote main remains unchanged until that PR is accepted.

## CI failures

The [main run](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/actions/runs/44544535439)
and [PR #51 run](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/actions/runs/44517962799)
both failed at the backend test step: 10 failed, 1,375 passed, 10,786 assertions.
Formatting, scoped analysis, both dependency audits, frontend tests and builds
had passed. Cached-mode, browser, additional-engine and restore checks were
skipped after the backend failure; they had not supplied release evidence.

One assertion expected the former lesson-example heading. Nine language/example
combinations expected the former challenge-example warning. Update these to the
approved interface and additionally check the discoverable lesson-example link
and selected starter language. Role/selector denial, no record on opening, draft
status, publication pointer and hidden test-case assertions remain enforced.
No application validation, CI step or test case is disabled to obtain a pass.

The full browser run also exposed helpers that ignored active-session takeover
when fixture accounts were reused in later tests. A shared test helper follows
the real confirmation screen and retains landing-page assertions. It does not
change the application's single-session rule or manipulate sessions in the DB.

## Verification record

Code/test revision: `bf5d0a273f565c17b496c17db3886aee0f44e117`.
Local environment: Windows, PHP 8.4.27, Node 22.20.0 and disposable PostgreSQL 18.6.
CI uses Ubuntu, PHP 8.4, Node 22 and PostgreSQL 17; evidence is dated 19 November.

- The first repair run on GitHub passed all 1,385 backend tests / 10,841 assertions
  at `318ffc3b8a7a74160efd4b4de9e3298d7527ad96`. This exact result is recorded in
  [run 128](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/actions/runs/44548774675).
  The run was superseded by the browser-helper push; it is not a green full run.
- [Run 129](https://github.com/pertyperty/IAC1-ENGGSF2-Kody/actions/runs/44550365261)
  at the code/test revision passed dependency validation/audits, formatting,
  analysis, frontend tests, builds, migrations, the full backend step and cached
  configuration/routes/views verification. Its browser/recovery stages were still
  pending or running when recorded. The later documentation head requires its
  own completed CI run; partial results do not establish an all-green candidate.
- The 28 focused backend cases passed locally with 370 assertions. All 65 frontend
  tests, full Pint and scoped static analysis passed locally.
- The diagnostic full Chromium run recorded 83 passes and the two active-session
  helper failures. Five ordered rechecks passed after the correction, including
  preceding authoring sessions and both previously failing journeys. A single
  clean final 85-case local run is not claimed.
- All 33 configured cross-browser smoke checks passed across Chromium, Firefox
  and WebKit on the code/test revision.
- The populated upgrade and 85-table restore rehearsal passed, including encrypted
  fixtures, validated progress and ledger integrity. Restore took 4,799 ms on
  local PostgreSQL; this is not staged recovery or an RPO/RTO claim.
- Diff checks passed. The recovered application resources match PR #51; this
  repair changes tests and delivery records, not the reviewed game implementation.

An initial local attempt overlapped two backend runners on the same disposable
database. Those results were invalidated and discarded. Subsequent backend runs
are serial; browser fixtures use separately generated databases. The redundant
long Windows full-backend attempt was stopped after GitHub supplied full-suite
evidence; it is not counted as a local pass. The owner's port-5432 database is
not migrated or reset.

A later local PHP startup failure referenced missing Herd debug-loader/extensions.
Verification used a task-only `PHPRC` in ignored private storage, retaining the
standard required extensions. The global PHP configuration was not edited.
Ignored `storage/app/private/main-ci-*.log` files contain local run evidence.

## Delivery rule

Before reporting that a child PR has reached main, fetch main and check that the
intended commit is its ancestor. If a parent PR has already merged, retarget the
child to main before merging, or open a direct-to-main integration PR. Do not
infer integration from a closed/merged badge or from a feature branch's contents.
For intentional squash/rebase merges, check the resulting integration commit and
verify its intended changes, rather than expecting the original feature head
to remain an ancestor.

This repair preserves A01, B04/B05, D01/D02 and E01 behavior from the reviewed
work. It introduces no dependencies, schema changes, provider implementation or
activation. Build/deploy the complete Vite manifest and chunks. Curriculum,
human testing and staged operations remain separate from a passing CI run.
