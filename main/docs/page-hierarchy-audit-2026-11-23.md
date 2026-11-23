# Page hierarchy and component audit — 23 November 2026

This pass follows PR 58. That pass addressed viewport overflow; this one looks at
what people see first, how they scan a page, and where they act. It keeps the
existing learning, authorization, progress, and provider behavior.

| Page family | Finding and change |
| --- | --- |
| Modules, Courses, Coding challenges | Headings, metadata and actions differed across catalogs. Use one header and one item layout, with the title first, readable metadata, creator links and badges, and one clearly named link to the content. The filters sit together above the results. Challenge search stays visible; additional filters open on demand and stay open when applied. |
| Library and Progress | Dense cards and repeated copy compete with continuation. Library entries align their actions and preserve saved state. Progress removes repeated concepts and shortens supporting explanations. |
| Account and Notifications | Put Edit profile beside the page title. Keep related links separate from account details. Notifications lead with the subject and show unread state alongside the date; old items do not repeat a Read badge. |
| Help and error pages | Separate the guided tour from the Search controls. Give questions more space to wrap. Use direct recovery text on shared error pages. |
| Creator and staff workspaces | Use consistent headings, flat review rows and table separators so the title, state, and action can be scanned across items. Shared styles also cover filtered empty states. The Finance page names its purpose and links directly to financial reports. |
| Public landing, Tower, Arcade, authentication, wallets and weekly events | Checked responsive page shells and representative routes. Board layouts, game rules and transactional controls retain their specialized styles. |

For the visual comparison, the [Codecademy catalog](https://www.codecademy.com/catalog)
shows how consistent title, description and metadata placement makes results easy
to compare, and the [Exercism Python track](https://exercism.org/tracks/python/exercises)
visually separates learning from practice. Kody keeps that clear hierarchy while
using lighter dividers for its shorter catalogs. Kody's action labels use the [GOV.UK summary-list pattern](https://design-system.service.gov.uk/components/summary-list/)
of making a repeated action intelligible out of context, and its staff rows use
separators instead of individual boxes. The earlier [public game and quiz
comparison](style-audit-2026-11-22.md#public-reference-comparison) still applies to
Focus mode. This change does not add another global stylesheet.

The responsive browser suite checks representative routes across six roles and
four widths (288 route visits in Chromium) and captures phone and desktop screenshots for the selected public,
learner, creator, and staff views. The accessibility suite checks public pages in
light and dark and exercises staff forms. Catalog regression checks cover the
single destination per course, accessible link names, progress state, and
filtered empty results, and keyboard access to challenge filters at 320px. These checks cover shared patterns and selected states;
they cannot establish that every record, error state, or page is visually polished.
