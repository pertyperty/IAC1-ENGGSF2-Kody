# Repeatable acceptance verification

Reviewed 2026-10-05. [Requirements acceptance](requirements-acceptance-audit.md)
maps all 61 original use cases and identifies source gaps/NFR limits.
[Launch curriculum review](launch-curriculum-review.md) supplies a concrete
eleven-lesson editorial queue. Neither document replaces approved source rules.

The later [navigation/visual follow-up](navigation-and-visual-polish.md) adds
scroll/hover/keyboard reveal and short-screen touch-menu checks, bringing the
browser suite to thirteen workflows. Its font assets are pinned for offline builds.

## Isolated browser acceptance

Install locked npm dependencies, build assets and install the test browser:

```sh
npm ci
npm run build
npx playwright install chromium
npm run test:browser
```

Supply local PostgreSQL connection environment variables privately. The test role
needs CREATE DATABASE on a disposable loopback server. `KODY_TEST_PHP` optionally
selects a PHP executable. Clear cached Laravel configuration first. This suite
does not use the developer's existing web server, accounts or database.

`tests/Browser/run.mjs` creates an unpredictable `kody_browser_<32 hex>` database,
applies migrations and seeds synthetic actors plus reviewed curriculum copies.
It owns a loopback PHP server, a random key and a temporary fixture manifest.
Providers are disabled, provider secrets/DB URL overrides are removed from child
environments, browser external requests are blocked and mail uses the test array
transport. A CLI-only guarded inbox supplies a synthetic registration token;
there are no fixture HTTP endpoints. Every learner save uses the actual interface,
CSRF/session routes and server validators. Cleanup stops the owned process and
drops only the created databases, including ordinary interruption/failure paths.
Forced process termination may require deleting orphaned test databases manually
after verifying ownership; never reset the development/production database.

Four workflows cover:

- Landing game, registration, fragment-based verification, login, saved starter
  win, sequential course enrollment, reading and assessment; repeat wins preserve
  exactly two XP awards/60 XP, while reading grants none.
- Creator quiz draft → staff publication → course composition/review → learner
  enrollment, through actual forms in separate actor browser contexts.
- Keyboard bypass and persisted light/dark mode at 1280/390/320px; disabled wallet
  packages provide no live checkout action.
- Pixel Studio, Number Machine, Sort Lab, virtual Terminal Quest and a three-question
  quiz on mobile; five validated first completions persist 200 XP.

axe checks WCAG A/AA rules on the exercised pages/states without exclusions.
Checks wait for finite transitions to finish; this avoids evaluating temporary
theme colors. The initial run found actual 320px overflow in the ladder preview.
Flexible nodes and zero minimum grid-child widths fixed it; the overflow check
remains a regression. The live quiz copy now accurately describes eligible XP.
Automated checks cover a subset of accessibility; they are not a complete WCAG
audit or actual device/Safari evidence.

Synthetic screenshots/failure context and HTML report go under ignored
`storage/app/browser-results` and `storage/app/browser-report`. Traces are disabled
to avoid retaining authentication requests. CI keeps synthetic reports for seven
days and never uploads the database dump, key, inbox token or credentials.

## Populated upgrade and restore rehearsal

```sh
npm run test:restore
```

Install matching PostgreSQL `pg_dump`/`pg_restore` clients. `KODY_TEST_PG_BIN`
optionally names their directory. Credentials stay in child environment variables,
not command arguments. The same loopback/testing/name guards apply.

The rehearsal applies the pre-discovery 40-migration baseline, publishes the
eleven synthetic lessons/two courses, records enrollment/reading/validated game
history and conserved synthetic ledger funding, and stores an encrypted test
delivery plus a pre-metadata challenge revision/hidden case. Migration 000038
then preserves every unchanged table fingerprint and supplies legacy metadata
defaults. No historical achievements or money are backfilled.

A custom-format dump restores into a second fresh owned database with the same
temporary application key. All **75 table row counts/digests** match; encrypted
fixture decryption, 60 XP, 50 synthetic KB and `kody:ledger-check` pass on both.
Local PostgreSQL 17 restore plus verification took **3,696 ms** in the recorded
run. This is a small synthetic local drill, not an RDS/S3/key-management backup
or the older pre-economy migration upgrade. Managed storage, staged recovery/load,
real mail/execution/payment and actual devices remain in the deferred register.

CI runs full PostgreSQL/cached regressions, frontend tests/build, isolated browser
journeys and the populated restore rehearsal. Matching clients come from the
[official PostgreSQL Ubuntu repository](https://www.postgresql.org/download/linux/ubuntu/).
Browser/accessibility dependencies follow [Playwright's testing guidance](https://playwright.dev/docs/accessibility-testing).
Re-run the workflow on the exact proposed commit before merging.

The [2026-10-07 interactive workspace follow-up](interactive-workspaces.md)
expands the suite to **eleven** workflows, adding all five role surfaces and direct
game editing/painting/swapping/terminal history, plus a failed completion-save retry.
The original registration, creator publication, reading and mobile assessment
journeys remain in the same isolated runner. These newer checks supersede the
earlier workflow count, without replacing the recorded restoration evidence.
