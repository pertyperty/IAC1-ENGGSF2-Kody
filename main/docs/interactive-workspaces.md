# Interactive workspaces and game surfaces

Owner-requested interface follow-up, 2026-10-07. This record extends the shared
[interface implementation](interface-implementation.md), [arcade templates](arcade-templates-implementation.md)
and [play implementation](play-implementation.md). It changes presentation and
local interaction; existing role policies, immutable assessments, server replay,
attempt limits and financial/progression rules remain authoritative.

## Design and provenance

The review used [Coddy](https://coddy.tech/), [Codecademy](https://www.codecademy.com/)
and [Brilliant](https://brilliant.org/) as references. Kody's interpretation favors
a focused workspace, a prominent interactive activity and supporting information
that can be opened when needed. These are original Blade/CSS/DOM implementations.
No third-party game code, logos, illustrations, fonts or website assets were copied
by this change; existing locally bundled fonts remain in use.

`workspace.css` owns shared chrome, role navigation, studio/library surfaces and
responsive workspace behavior. `game-play.css` owns playable surfaces. Superseded
garden/arcade component blocks are removed from the older style files. Semantic
blue/grey theme tokens still come from `theme.css`; colors in the pixel canvas,
garden and terminal represent actual game state. All motion is finite and disabled
under reduced-motion preferences.

## Role workspaces

Both layouts use one header, a compact native Account menu and policy-filtered
navigation. Desktop navigation occupies a side rail; smaller screens use an
expandable workspace menu. Account-menu Escape returns focus to its summary;
clicking outside closes it. Native menu/navigation behavior remains usable without
JavaScript. The theme control remains available in the header.

- Learners see their overview, Learning catalog, journeys, arcade and coding quests.
- Contributors gain Quest studio. Instructors also gain Module studio and Course builder.
- Moderators gain community, moderation, publication/role reviews and weekly planning.
- Administrators gain their existing staff tools and reports/accounting. Weekly
  planning remains Moderator-only under the existing policy.

Creator studios prioritize owned work before optional starter libraries. Examples
remain editable draft inputs and never auto-publish. Publication queues have
consistent links between modules, courses and coding quests. Shared fields,
review rows, account results, reports, cards and empty states use consistent
spacing, contrast and focus treatment. Route/service authorization still owns
every action; navigation visibility grants no access.

## Play interactions

Starter lessons put the garden before explanations and the knowledge check.
The board, objective, command palette, ordered program and run feedback form one
workspace. Published creator lessons retain their escaped learning material in an
expandable reader when they have an assessment; assessment-free reading remains
fully available with its existing explicit Mark as read flow.

Command Garden supports selecting, moving and removing individual instructions,
Undo and reset. Arrow shortcuts apply only when the game card itself is focused;
they never intercept fields or general page navigation. Enter runs that program.
On a program tile, Alt + Left/Right reorders it and Delete removes it. Runs highlight
the executing instruction and visited path, update crystal objectives and lock
editing through completion saving. The payload and deterministic interpreter are
unchanged. Editing clears previous visual success; only a complete successful run
requests server validation.

Pixel Studio offers a color palette and tappable canvas. Repainting a cell replaces
its last paint instruction; blank target cells must still remain blank. Sort Lab
lets learners select two numbered tiles to append a swap, retaining keyboard focus.
Number Machine shows start/current/target values and previews changes. Each activity
shows its instruction queue with per-step removal; the text editor remains available
for direct instruction editing. Non-terminal runs animate bounded replay steps.

Terminal Quest shows a virtual file inventory, mission, prompt and transcript.
Enter runs the accumulated program; Up/Down recalls local commands. Reset clears
history and the program. Its limited `ls`, `cat` and `cp` interpreter never invokes
a host shell or a language runtime. Judge0/live integrations remain disabled.

All direct edits update local previews only. A successful Run sends the existing
program to the existing server completion route. Local guest and creator previews
have no completion route and grant nothing. Controls stay locked during evaluation
and save requests, then restore for safe retry. Network/save failure messages remain
visible; visual success alone is not proof of saved progress. Creator placeholders,
published revisions, preset snapshots and server win rules are unchanged.

## Verification

The isolated browser suite now includes separate role-workspace checks and direct
game interaction checks, alongside the existing registration, review/publication,
reading, enrollment and multi-question assessment journeys. It exercises both
themes, desktop and 320/390px layouts, keyboard focus, axe checks, horizontal
overflow and failed-save recovery. Synthetic accounts and screenshots live only in
the guarded disposable browser environment. See the completed evidence in
[interface implementation](interface-implementation.md#interactive-role-workspaces--2026-10-07).

This slice has no migration, dependency addition, new privileged endpoint or
provider activation. Browser measurements are local Chromium evidence; real-device,
Safari/Firefox and staging/NFR verification remain in the existing verification
registers.
