# Responsive UI audit — 22 November 2026

This pass starts from `main` with PR 57 integrated. Its browser matrix checks
72 page-and-role combinations at 320, 768, 1101 and 1440 CSS pixels. It covers
the public shell, learner progress and content, creator tools, moderation and
administration. The matrix checks the actual destination and response before
checking horizontal layout. It captures representative viewport screenshots and
opens the workspace drawer at phone and tablet widths. Existing workflow tests
cover stateful pages such as enrollment, review details and recovery; the matrix
is not an exhaustive review of every record or possible account state.

| Finding | Change |
| --- | --- |
| At 768 px, the signed-in header exceeded the viewport on nearly every authenticated page tested. A second overflow appeared just above the rail breakpoint for staff and creators. | The drawer replaces the duplicate primary links on compact screens. Page titles live in the page; the account menu identifies the user and carries the compact wallet shortcut. |
| Wide account and operations pages inherited the margins and inner panel padding of single-form screens. | Wide mobile screens use the same gutters as other pages. Secondary panels stop adding a second layer of horizontal padding. Footer links have consistent touch targets. |
| Opening an account or workspace menu on a short screen could make navigation hard to reach. | The account menu is bounded by the viewport, and the workspace drawer uses one scroller. |
| Arcade repeated the title and difficulty explanation above the board. The Run row could be clipped, and Hint and failed Run could focus text outside the visible pane. | The page shows one activity title. The program controls keep the action row within reach, and feedback scrolls into view. Stacked layouts still present the goal and board before the program. |

The activity layout follows the useful parts of the previously observed
[style benchmark](style-audit-2026-11-22.md#public-reference-comparison): keep the
current goal next to the workspace, give the primary action a stable position,
and leave context available on request. The game interpreters, server validation,
access rules and provider configuration do not change in this pass.

The 288 route and width visits passed in Chromium. Six additional Chromium
journey and accessibility tests passed, covering both public themes, staff
forms, navigation and the live Focus workspace. Six targeted game tests passed
across Chromium, Firefox and WebKit. The production asset build, 79 frontend
tests and 19 backend interface tests also passed.
