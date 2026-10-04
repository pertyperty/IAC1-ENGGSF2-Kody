# Shared themes and interaction improvements

Supports the 2026-10-04 owner request across account, learning, creator and staff
pages, and D01/D02 practice previews, A02/A04 email links and B05 assessments.
No migrations, provider activation or authorization changes are required.

## Appearance and accessibility

Both shared layouts offer a header button labelled Dark mode. Its pressed state
reports whether dark mode is active. It follows the operating-system preference
until a user chooses a mode, then stores only `light`/`dark` in `kody-theme`.
Preference changes synchronize across tabs; unavailable storage leaves switching
usable for the current page. A small static head script selects colors before
the page paints; the controller uses the same validated selection behavior.
JavaScript-disabled pages use the light palette and hide the inactive toggle.

`theme.css` owns semantic page/surface/text/border/blue accent colors and shared
interaction rules. `play.css` and `interface.css` consume those colors rather
than defining independent palettes. Amber and violet supply secondary game
accents; garden terrain and named pixel colors retain their visual semantics.
Browser interfaces share this styling; transactional mail does not use a browser
theme or local-storage preference.

Skip-to-content links, visible keyboard focus, checked quiz choices and pressed
controls expose state beyond color alone. Fields use readable 16px text and
44px minimum heights; primary buttons use 48px. Staff report/account listings
can use a wider shell. Navigation, game controls, filters, course slots and long
text wrap on small screens. Hover interactions use slight lifts, border changes
and icon movement, with reduced-motion support. These improvements do not
constitute a full WCAG audit or a claim about every browser/device.

## Interaction corrections

- Creator quiz previews read the unsaved title/question/two choices/answer/feedback,
  enforce current authoring limits and reject missing/duplicate choices. The
  preview is outside the authoring form, so its radio controls and submit button
  cannot submit a draft accidentally. Text uses text nodes, never executable HTML.
  Editing clears stale preview output. No completion URL is supplied.
- Quiz practice/preview feedback distinguishes server-validated saved play from
  local practice. A busy guard serializes correct-answer completion requests,
  temporarily disables choices/buttons and restores them after the save attempt.
  The existing server validation, idempotency and generic failure feedback remain.
- Module editor inactive quiz/preset/video fields are disabled without discarding
  their values. Existing game/scenario field selection remains intact. Server
  Form Request exclusion/validation remains authoritative.
- Verification and recovery fragment handling covers initial load and same-page
  hash changes, removes the fragment before POST and allows only one automatic
  submission per mounted page. Malformed links are erased without submission;
  a subsequent valid link can still work. Tokens never enter server GET queries,
  theme storage, logs or preview data.

## Verification and deployment

`InterfaceTest` covers shared guest/account pages, separate non-saving quiz
previews and staff authorization/layouts. Frontend regressions cover prepaint
selection, system/storage changes, blocked storage, same-page email links,
malformed tokens, prompt validation and serialized quiz completion/retry.
Existing account/creator/progression tests protect persisted workflows.

Rebuild Vite assets and refresh compiled views with the release. The theme
bootstrap is static inline JavaScript, like the application's existing script
setup; a future strict CSP must supply a nonce/hash policy rather than blocking
the prepaint script silently. No dependency or infrastructure changes are needed.

## Local evidence — 2026-10-04

- Full PostgreSQL suite: 968 tests / 7,022 assertions passed.
- Cached configuration, routes and views: 528 tests / 4,007 assertions passed;
  caches were cleared afterward. CI includes the new interface regressions in
  its existing cached verification step.
- All 40 frontend tests passed, including semantic text/action color pairs at
  4.5:1 or better in both themes. This checks the palette, not every rendered
  illustration or all WCAG criteria. Pint, Vite build, workflow YAML,
  documentation links and diff whitespace checks passed.
- Browser observations at 320px, 390px, 768px and 1280px: theme switching across
  landing/account routes; learner hub, catalog, game/quiz lesson, creator editor,
  staff account filters and report table with no observed horizontal overflow.
  Creator preview handled wrong/correct answers, duplicate-choice errors and
  literal markup text without saving. Saved learner quiz practice still recorded
  a streak through the server. A same-page verification fragment triggered POST
  and was absent from the resulting URL; its synthetic invalid token was rejected.
- Browser data used a separate disposable PostgreSQL database. No primary
  development database, cloud service or deferred provider was activated.

Real-device/Safari/Firefox checks, a complete accessibility audit and populated
staging performance measurements remain follow-up verification work.
