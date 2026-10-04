# Public Help and audited FAQ management — B11/G11–G13

## Requirements and approved decisions

The owner approved public reading and search of Active FAQ entries, including
guests. Management belongs only to verified Active Administrators. The approved
predefined topics are Getting started, Accounts, Playing and learning, and Creating
content. This resolves B11's optional Guest actor and undefined category list.
`Active` follows the FAQ data dictionary and B11's Published/Active availability;
no new Published lifecycle is invented. Source text labels B11 as `UC 011`.

Administration owns authoring/deletion and audit. User Interaction provides the
read-only Help experience. This does not change learning module authentication,
grant rewards or add remote integrations.

## Delivered behavior

- B11: public `/help` lists Active questions by topic with native accessible
  expandable answers, detail links, literal keyword search across question/answer,
  category filtering, pagination and empty results. The learning footer links Help.
  Archived/Deleted entries cannot appear in lists or direct detail requests.
- G11: `/manage/faqs` lets Administrators create plain-text questions/answers in an
  approved topic. Saving makes the entry Active and publicly visible. Questions
  follow the dictionary's 255-character bound. Answers have a 10,000-character
  operational request bound, not a new scoring/business rule.
- G12: edit the current question/answer/category with a record version. Successful
  updates are immediately visible; stale forms must reload. Exact duplicate
  questions (ignoring case and surrounding spaces) display a validation warning
  and require a different question or editing the existing entry. No claim of
  semantic/fuzzy duplicate detection. Matching an entry's own question is allowed.
- G13: explicit confirmation soft-deletes the entry to the dictionary's Deleted
  status. Lists and all direct Help/management edit paths exclude it. The row and
  minimal audit references remain; no deleted-text browsing/restoration endpoint
  exists. A separately authored replacement may reuse the deleted question.

No FAQ archival or restore workflow is added. Archived is constrained for dictionary
compatibility but unavailable for public viewing and first-release management.
Public responses are no-store to prevent ordinary browser/proxy caching after
changes/deletion; previously delivered answers cannot be retracted from a browser.

## Security, integrity and deployment

Authenticated/current-session middleware, policies and fresh account checks guard
management. Mutations lock the Administrator first, then an existing FAQ; record
versions prevent lost edits and competing deletions. Prescribed fields exclude
client status/ownership. Nonempty validated text, operational bounds, approved
categories, CSRF and ten-per-minute management throttling apply. Public Help has a
thirty-per-minute read/search budget. Parameters are bounded and query values bound;
search escapes wildcard characters. Templates escape text, including code/HTML.

The additive `2026_10_03_000024_create_faq_entries.php` migration adds FAQ storage,
author references, status/version/category/nonempty constraints, visibility indexes
and a partial PostgreSQL unique normalized-question index for non-Deleted entries.
Duplicate-name races become a validation warning after transaction rollback.
Content state and minimal audit commit atomically; audit failure rolls back.
Audits retain operation/version/category/actor references without copying answers.

Migration rollback refuses to erase authored rows/audit references. Ordinary
application rollback can keep the additive table. Deploy normal migrations and
rebuild assets/caches; no secret, worker, scheduler, package or external setup.
No production seed credentials or invented FAQ answers are installed.

## Verification

Tests cover guest/participant reading, staff restrictions, escaped content, keyword
and literal-wildcard search, topics, paging, empty/missing/inactive paths, field
bounds, protected input, duplicate/stale edits, confirmation, cancellation, deletion
visibility, audit rollback, PostgreSQL constraints and migration preservation.
Independent PostgreSQL processes verify duplicate creation and simultaneous
update/deletion commit one state version and audit. Focused FAQ checks passed
31 tests / 225 assertions; the full PostgreSQL suite passed 748 tests / 5,383
assertions. All 27 migrations execute on clean test databases. Cached
configuration/routes/views passed 106 FAQ, preset, learning and health checks /
830 assertions. Pint, 16 frontend tests, production asset build and diff whitespace
checks passed.
Remote CI, production deployment and browser visual checks remain unverified.

## Next implementation prompt

B10 has since been resolved by the owner: one replaceable Like/Helpful/Favorite
reaction with removal; numeric ratings deferred. The delegated eligibility choice
requires current access and a trusted opening/completion, plus course enrollment.
See [content feedback](content-feedback-implementation.md) for implementation and
the next content-deletion decision. Retain monetization, reward formulas and live
Judge0 activation in the deferred-feature register.
