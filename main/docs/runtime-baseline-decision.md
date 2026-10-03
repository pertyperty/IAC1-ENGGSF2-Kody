# Pending PHP runtime baseline decision

The repository's declared application minimum is PHP `^8.3` in composer.json and
the verified technology baseline in AGENTS.md. The locked packages currently are:

| Package | Locked version | PHP requirement |
| --- | --- | --- |
| Laravel Framework | v13.34.0 | ^8.3 |
| Symfony HTTP Kernel | v8.1.8 | >=8.4.1 |
| Pest | v5.2.1 | ^8.4 |
| PHPUnit | 13.3.4 | >=8.4.1 |

Source: installed composer.lock, checked locally on 2026-10-03. README and CI
already use PHP 8.4, and local verification uses PHP 8.4.26. PHP 8.3 cannot install
the current locked runtime/test dependencies, regardless of the root declaration.
The mismatch was previously recorded in architecture.md; it remains unresolved.

Recommended: adopt PHP 8.4.1+ as the supported application/development/test baseline,
update the root requirement to `^8.4.1`, align AGENTS.md and setup/deployment docs,
and preserve the tested dependency versions. This changes the stated minimum;
the owner should confirm that team and deployment runtimes can follow it.

Alternative: require actual PHP 8.3 support and reassess compatible locked packages
and test tooling. Pest 5 cannot remain in a PHP 8.3 test environment. This requires
a separate dependency change, compatibility review, audits and full regression.

No dependency downgrade, minimum-version change or production runtime change is
made while the decision is pending. The Google setup deferral remains separate.
