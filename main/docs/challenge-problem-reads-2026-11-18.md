# Challenge problem revision consistency

This focused consolidation batch addresses B06 problem presentation and E02 weekly
revision pinning. It branches from `main` at
`e4e7d33ba3a9fe3c09f2ebe7f5ef619364e9b565`, independently of the financial-report
PR. Challenge Management retains publication/admission ownership; Engagement only
renders the authorized problem and samples.

## Reproduced defects and changes

Standard challenge routes bind a model before `ContentAccess::open` locks and
reloads the current publication. If an approval completes between those reads,
the controller previously selected the earlier route-bound revision even though
admission checked the newer one. The controller now uses the target returned by
admission for its narrow public projection. The problem, metadata, public samples
and submission form therefore use the publication checked by that admission.
A later publication can still invalidate a displayed form; submission must retain
its existing fresh revision check and ask the learner to reload.

Weekly pages correctly select the event-pinned revision, but omitted `category`
and `tags` required by the shared problem partial. They now load those fields
from the pinned revision, including after a newer standard revision is approved.
No shared service was added for the few repeated queries: admission and revision
selection have different meanings in the two workflows.

Both problem projections still omit review/access-policy fields. Public samples
remain filtered in SQL and select only position, input and expected output.
Hidden cases, source code, attempt locks, idempotency, rewards and provider jobs
retain their existing boundaries. These reads create no submissions or evaluations.

The two controllers and `WeeklyEvent` join the level-5 static-analysis scope,
expanding it from 23 to 26 PHP files. The model's relationship annotations identify
the actual related classes; no baseline or ignored error was introduced.
Cached-route CI now explicitly selects the problem-read, submission and weekly
event regressions. The existing browser journey checks category/concept text on
both problem pages using a fixture with explicit metadata.

## Verification

Local development verification on 18 November 2026 uses PHP 8.4.27, Laravel 13.42,
disposable loopback PostgreSQL 18 and Chromium. The automated implementation
reviewer is Codex; the PR head records the resulting immutable revision. This is
development evidence, not release approval or human curriculum review.

- Before the fixes, both new HTTP regression variants failed: standard rendered
  the earlier title and weekly omitted the pinned category. After the fixes,
  both pass with 58 assertions, including metadata, samples, the form revision,
  private-field exclusion, no submissions and no external HTTP requests.
- Broader normal PostgreSQL checks: 244 challenge, weekly, economy and feedback
  tests passed with 1,821 assertions, including the existing concurrency coverage.
- Cached testing configuration/routes: 93 problem-read, discovery, submission and
  weekly-event tests passed with 576 assertions. Private relative cache paths were
  cleared afterward; the development database and normal caches were not changed.
- All 26 scoped PHP files pass normal and serial-debug static analysis with
  PostgreSQL unavailable. The initial scope expansion identified missing weekly
  relationship types; accurate model annotations resolved those findings.
- The affected Chromium journey passes, covering both problem pages, pending
  execution controls, guides, accessibility and a narrow viewport.
- Full Pint, whitespace checks and all 94 changed-document relative links pass.

Logs are retained under ignored private storage as `challenge-problem-tests.log`,
`challenge-problem-cached-tests.log`, `challenge-problem-analysis.log` and
`challenge-problem-browser.log`. The browser harness removes its databases/server;
the disposable PostgreSQL cluster is stopped after verification.

There are no schema, package, asset, environment-variable or provider activation
changes. Existing built assets serve the PHP-only correction, so frontend builds,
game unit tests and restore rehearsals are not repeated for this batch. Remote
CI has not been monitored or claimed to pass. Registry `picomatch` latest remains
4.0.7 on this date; the inherited [npm advisory gate](static-analysis-2026-11-18.md#npm-advisory-gate)
is unresolved and the security check remains enabled. Curriculum, legal and staged
operations evidence stay in the [deferred register](deferred-features.md).
