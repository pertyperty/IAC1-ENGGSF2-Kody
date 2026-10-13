# Role applications: local demonstration and approval welcome

Owner-requested, 2026-10-13. Learners 4 and 5 remain applicants until a real
Moderator/Administrator decision. The owner explicitly approved reinstating the
suspended local Learner 5 through the existing audited moderation flow.

## Prepare the local accounts

After creating the ordinary local role fixtures, opt in with:

```sh
php artisan kody:role-demo-prepare
```

If Learner 5 is suspended and its reinstatement is explicitly intended:

```sh
php artisan kody:role-demo-prepare --reinstate-five
```

The command/`LocalRoleApplicationDemoSeeder` require local/testing and loopback
PostgreSQL. They require audited fixture identities at `learner4@kody.local` and
`learner5@kody.local`, plus Active local Instructor/Contributor/Moderator authors.
They never replace passwords, usernames, verification, prior attempts or
application decisions. Renamed usernames are preserved; use the command output
or the current profile instead of assuming a seed username still applies.

Both Learners receive a 31-day demo account age, 25 assessed module history
records and 50 explicitly synthetic Passed quest results. Module inputs are
replayed against typed server objectives. This is demonstration history, not
earned participation: it grants no XP, streak state, KodeBits or financial entries.
The quest profile is deliberately unrelated to provider configuration and cannot
enable Judge0. No provider is called or execution job queued.

The supporting modules/quests are created through normal publishing/review
services, then archived to keep learner catalogs clear. Audit markers identify
every artifact and prepared user. Only newly generated fixture review notices
are suppressed; existing notifications are preserved. Creator sessions are
restored before commit. Reinstatement retains enforcement history and revokes
the suspended account's old session as usual. The transaction rolls back on
failure, and reruns preserve decisions and do not duplicate demo history.

This seeder is **not** in default seeds. Remove it and its command before
deployment; do not copy local databases or private credential files to production.
Ordinary accounts still need the approved A09 thresholds, and A10 still requires
private credentials and manual credibility review. No application is pre-submitted
or automatically approved by preparation.

## Suggested presentation

1. Sign in as Learner 4. Open Profile & security → Contributor application.
   Its local-demo banner identifies the prepared history. Enter a clearly labeled
   demonstration message and upload the original Kody sample at
   `database/fixtures/lesson-resources/programming-foundations.pdf`. This worksheet
   is a mock supporting document, not a real credential. Submit explicitly.
2. In a separate browser profile/incognito window, sign in as a Moderator or
   Administrator. Open Contributor applications, inspect the identity, supplied
   document and milestones, then confirm the decision.
3. Leave the applicant page open. Within the visible-page polling interval
   (15 seconds), Kody presents the approved role and direct Quest studio action.
   Refreshing or signing in later also discovers the durable approval notice.
4. Repeat with Learner 5's Instructor application. Use a clearly labeled demo
   institution/specialization and mock worksheet. Staff must explicitly confirm
   their credential review. The Instructor welcome opens Module studio or the
   new creator Overview.

Both prepared Learners can choose either legitimate application route. Only one
Pending role application is permitted at a time. For repeat presentations,
use an isolated fresh demo database; this command intentionally does not undo
an approval or erase history to make an applicant eligible again.

## Durable role confirmation

Approved A09/A10 transactions insert an owned `account.role-changed` database
notification alongside the role update. An audit or write failure rolls both
back. Existing decision email delivery stays queued and cannot delay the screen.
Rejection creates no promotion celebration.

`RoleChangeNotices` checks the current Active session, notification ownership,
current role and current creator policy. Old role notices cannot announce stale
privileges or link to a forbidden studio. The same notification remains available
in Updates as historical approval evidence, with policy-checked destinations.

The shared native dialog uses the configured celebration mascot, semantic theme
tokens, previous/new role badges, capability summaries, a direct studio action
and Overview action. It is centered, responsive, keyboard accessible, and uses
reduced motion when requested. Focused security/editing pages defer it. An open
tutorial or game-result dialog takes precedence. A server acknowledgment marks
the notice read, making repeated acknowledgment harmless and preventing replay.
Overview navigation avoids reloading an application form that the new role can
no longer access. A failed acknowledgment offers a safe account refresh.

Polling runs only for a pending application/unacknowledged update, pauses in
hidden tabs, backs off after errors and stops after authorization loss. Browser
history restoration fetches fresh state. Neither browser storage nor the dialog
can change a role. Without JavaScript, the server provides an approval notice and
ordinary POST continuation form.

## Verification

PostgreSQL checks cover fixture guards, repeatability, explicit reinstatement,
unchanged passwords/sessions, no rewards, owned acknowledgments, stale roles,
rejection and transactional notice rollback. Existing A09/A10 and local role
seeder tests remain in the regression suite. Tests reflect the owner's combined
nine-account seeder on main, including its five Learners.

Browser journeys submit both applications through the actual forms, approve with
separate Moderator/Administrator sessions, observe the live mascot welcome,
check mobile/reduced-motion accessibility, enter the new studio/overview and
verify that acknowledgment prevents replay. All automated tests use isolated
synthetic databases and disabled live providers. Final CI evidence is in the PR.
