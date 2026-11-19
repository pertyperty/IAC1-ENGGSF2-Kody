# Page-specific JavaScript delivery

This consolidation batch supports the shared interface and existing B01-B05,
C01-C02, D01-D07 and G08-G10 workflows. It adds no game, provider integration,
schema, access rule or reward. It builds on the learner-first rework in PR #47.
The verified application/test revision is
`8587073c81dbc7f44a0015200398978bc73bfde8`; the following documentation commit
records the results below.

## What changed

The base shell keeps `app.js` for shared account, navigation, feedback and progress
controls. Blade's page-script stack declares the other entries once, according to
the rendered components rather than a duplicate route allowlist:

- `play.js`: existing movement, Arcade and quiz initializers. Game partials declare
  it, including when used as an authoring preview template.
- `tower-page.js`: existing map/list navigation, guest sequencing and level-stage
  continuation. Tower views declare it.
- `authoring.js`: existing lesson, course, challenge, preset and Tower editors.
  Authoring views declare it. Tower editing moves out of the learner Tower module.

Shared interpreter modules remain shared build chunks. Native module scripts load
up front in document order; no click-triggered import, custom loader, new framework
or dependency is introduced. The leave guard mounts after editor defaults, as
before. Preview controls remain excluded from its change signature. Multiple game
partials produce one play entry, preserving a single set of listeners.

Server replay, immutable revisions, permissions, progress/rewards and provider
flags are unchanged. Office previews keep their separate existing bundle. Build
and deploy the complete Vite manifest and chunk set together; no migration or
environment change is required.

## Delivery measurements

The baseline uses application revision `cc8c9dd96b95e791f3550d0899e1bfccafda358b`.
Both measurements use fresh production builds and the isolated browser harness
on Windows, PHP 8.4.27, Node 22.20.0 and PostgreSQL 18.6. The probe records three
navigations per page in local Chromium with browser cache disabled and CPU
throttling set to four. JavaScript URLs come from actual Resource Timing entries;
source-byte totals count each requested build file once. Estimated gzip totals
compress each file with Node's default gzip settings; they are not observed HTTP
compression from the development server. CSS, images, fonts and Office frames are
outside these totals.

| Page | Before JS bytes | After JS bytes | Before/after estimated gzip bytes | Before/after script requests |
| --- | ---: | ---: | ---: | ---: |
| Sign-in | 146,874 | 51,789 | 44,224 / 17,680 | 1 / 2 |
| Help | 146,874 | 51,789 | 44,224 / 17,680 | 1 / 2 |
| Number practice | 146,874 | 95,934 | 44,224 / 32,165 | 1 / 4 |
| Tower list | 146,874 | 59,955 | 44,224 / 20,710 | 1 / 3 |
| Lesson editor | 146,874 | 139,062 | 44,224 / 44,184 | 1 / 5 |

This reduces common-page code, rather than removing the existing capabilities.
The authoring group deliberately retains related editors and gains little in
compressed bytes; chunking increases the number of requests. It is not a claim
that every page is faster or that total application code shrank substantially.

The local median DOMContentLoaded readings (milliseconds) were 512/403 for
sign-in, 614/484 for Help, 703/447 for practice, 888/443 for the Tower list and
967/1,012 for lesson editing. There are only three samples per page, with cold
outliers and uncontrolled host/cache effects. These readings are diagnostics,
not device/network benchmarks, capacity evidence or a speed guarantee. No latency
threshold was weakened to pass a test.

Ignored evidence: `storage/app/private/page-assets-baseline.json`,
`page-assets-after.json` and the `page-assets-*.log` files. The browser regression
checks exact entry declarations and their transitive requested chunks against the
build manifest, rather than hard-coding hashed filenames.

## Verification and deferred work

- All 65 frontend domain/interface tests passed.
- All 143 selected backend cases passed with 1,540 assertions; another 38 preset
  cases passed with 281 assertions. Selection discovers the full fixture set.
- All 25 selected Chromium workflows passed: actual script requests, navigation,
  authoring leave protection, Tower publication, creator/staff/learner publication,
  saved games/rewards, custom previews and guided concept checks.
- The cross-engine run passed 18 of 21 checks. Its three challenge-editor failures
  used a nonexistent fixture account, before reaching the editor. Correcting the
  account required no application change. The final six challenge/preset checks
  passed across Chromium, Firefox and WebKit; the passing earlier checks cover
  Tower sequencing, concept answers, bounded pseudocode, Help and keyboard
  scrolling. No single clean final 21-check run is claimed.
- Full Pint and both production builds passed.
- Fresh installed-package and lock-file npm audits reported zero vulnerabilities.
  The [previous picomatch advisory was withdrawn](https://github.com/advisories/GHSA-fc5q-39xc-vjpf).
  Manifest, lock, audit threshold and suppression policy are unchanged. See the
  [dated recheck](static-analysis-2026-11-18.md#19-november-advisory-recheck).

The local comparison chart is `storage/app/private/page-assets-report.html`.

No owner database migration/reset or provider activation was performed. Remote CI
has not been reviewed. The broader backend suite, managed restore and live systems
were not rerun for this presentation-only split.

Reporting-query consolidation still requires representative measurement and
reconciliation evidence. Human learning observation and editorial sign-off remain
the next product-validation work; automated reference solutions cannot supply
them. Production legal/retention review, actual devices, named operational owners
and staged release evidence remain in the [deferred register](deferred-features.md).
Third-party/API work remains paused, with Judge0 first when the owner resumes it.
