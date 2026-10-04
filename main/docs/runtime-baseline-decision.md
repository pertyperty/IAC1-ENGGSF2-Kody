# Approved PHP runtime baseline decision

On 2026-10-04, the owner approved PHP 8.4.1+ in chat. Application, development,
tests and production PHP-FPM now require `^8.4.1` (PHP 8.4.1 or newer within PHP
8.x), superseding the previous `^8.3` baseline. Locked versions are preserved:

| Package | Locked version | PHP requirement |
| --- | --- | --- |
| Laravel Framework | v13.34.0 | ^8.3 |
| Symfony HTTP Kernel | v8.1.8 | >=8.4.1 |
| Pest | v5.2.1 | ^8.4 |
| PHPUnit | 13.3.4 | >=8.4.1 |

Source: composer.lock, checked locally on 2026-10-04. PHP 8.3 cannot install
these locked runtime/test dependencies. Updating the root requirement and lock
metadata resolves that mismatch without upgrading or downgrading packages.

AGENTS.md, README and architecture guidance reflect the approved minimum. CI
already selects PHP 8.4; local verification uses PHP 8.4.26. Teams must use a
compatible runtime for Composer, Artisan, web serving and queue workers.
Composer platform checks must pass without ignoring platform requirements.

This decision does not change a deployed runtime or certify every future PHP
release. Validate runtime upgrades before deployment. There are no schema,
authorization or business-rule changes.

Provider setup deferrals, including Google OAuth and Judge0, remain separate.

Verification on PHP 8.4.26: strict Composer validation, locked platform checks,
install dry-run and dependency audit passed (no reported vulnerability advisories).
All 137 locked package records are unchanged; only the content hash and root PHP
platform constraint changed. Health and password-hashing regression checks passed
(5 tests, 23 assertions), as did Pint and the whitespace check. Remote CI and
production runtime validation remain separate from these local checks.
