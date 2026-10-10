# Workflow quality and shared interface audit

Reviewed 2026-10-09. This follows the owner's request to check implemented
functionality, improve usability and reuse layouts/partials. The
[61-use-case acceptance checklist](requirements-acceptance-audit.md) remains the
master requirements record; this document records the resulting maintenance
changes and local verification. It does not certify deferred live integrations
or every original alternate flow.

## Changes

- Both account and learning shells extend one base document. CSRF metadata,
  referrer protection, theme/assets, feedback, skip link, workspace navigation
  and the main landmark now have a single owner. Shell classes and footers remain
  separate. Shared form-error and review-action partials preserve field errors,
  policy checks and distinct approval/return actions.
- Empty module, course and coding-quest searches offer an explicit next-action
  button through a reusable empty-state component, with narrow-screen and
  light/dark checks.
- Dashboard chrome reuses the controller's owned progress, achievement and unread
  snapshots. Participants avoid four repeated progression/achievement/count
  queries; staff avoid the two unused participant progress queries. Regression
  checks measure query counts for all five roles. There is no cross-user cache.
- Sequential course outlines compute unlocked lessons in one ordered pass,
  replacing repeated scans of preceding lessons. Persisted enrollment policy,
  server authorization and reading/completion rules remain unchanged.
- Notification destinations are computed in a service with batched resource
  lookup and current policy checks. Retained notices after role changes remain
  readable without a link to a forbidden creator/reviewer page. Marking an update
  read confirms the action through the shared toast; pagination has a stable
  secondary ordering.
- Reaction requests reject redirects and have a ten-second deadline, so an
  interrupted request releases controls through existing failure handling.
- Pending evaluation polling resumes after browser back/forward-cache restoration,
  serializes requests and validates terminal counts/feedback before displaying a
  result. Expired polling/access explains manual recovery. Browser interruption
  never cancels a committed server evaluation.

## Functional coverage

The full PostgreSQL suite checks the implemented slices in each logical module,
including authorization, alternate paths, concurrency and fake-provider behavior.
Feature records linked from the acceptance checklist retain their detailed limits.

| Module | Local checks |
| --- | --- |
| Account/authentication | Registration, verification, login/lockouts, recovery, profile/security changes, role applications, archival/deletion and disabled Google boundaries |
| Content | Draft/review/publication, immutable revisions, course composition/enrollment, sequential lessons, assessment/read progress, withdrawal and dependency-safe deletion |
| Coding challenges | Authoring/review, discovery/access, durable submissions, separate attempt budgets, provider validation/retries and hidden-case isolation |
| Gamification | Typed game/quiz server replay, starter levels, Manila streaks, once-per-content XP, ranks/leaderboards and weekly reward invariants |
| Interaction | Discovery, reactions, owned updates, continued journeys, themes, keyboard/touch navigation and feedback |
| Transactions | Exact ledger/access/earnings, payout reservation, refunds, authenticated/idempotent fake callbacks and reconciliation |
| Governance | Role hierarchy, session revocation, content moderation, reports, presets, FAQ management and privacy reviews |

The isolated browser suite exercises registration → verification → play → course
enrollment → assessment, creator draft → staff review → publication, all five
role workspaces, mobile arcade interactions, notification feedback and empty
catalog recovery. It checks WCAG A/AA rules and horizontal overflow across the
visited pages, including both themes and 320-pixel screens. These automated
checks complement visual review; they do not replace human accessibility testing.

## Verification boundary

No schema, dependency, financial formula or access-policy changes are introduced.
Local tests use disposable PostgreSQL fixtures and disabled external providers.
Real Judge0 execution, Google identity, SendGrid delivery, Xendit callbacks and
managed staging/production operations remain in the
[deferred register](deferred-features.md). Setup remains documented in the
[integration guide](integration-setup.md). Missing legal terms and other content
acceptance decisions remain visible in the master checklist.

Final test counts and CI evidence are recorded in the pull request for this change.
