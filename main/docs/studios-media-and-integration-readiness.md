# Studios, resources, reporting and integration preparation

Owner request and article reward amendment: 2026-10-12. This record supplements
the implementation map. Live providers and staging remain deferred.

## Creator and role workspaces

Creator step navigation stays sticky; the local back/home group participates in
normal layout on studio pages so it cannot cover the steps. Module libraries use
responsive cards, and account filters/results use wide-screen columns with a
single-column phone fallback. The account browser initially returns only the ten
newest accounts, ordered by creation time then ID. Explicit username/role/status
filters or All accounts enable pagination (10/20/50). Saved filters can be reset;
a forged page parameter does not expose further default accounts.

Pixel targets can be painted, switches/gates toggled, sort values rearranged,
stacks built, number goals dragged, string transformations composed and virtual
files edited directly. Module/preset and Tower editors share the visual scenario
designer. Advanced typed fields remain available. Typing preserves the control
and caret; edits clear stale previews. Invalid advanced data disables the visual
surface until corrected. Preview controls mount once, and the Arcade preview uses
the current game-shell selectors. Garden marker decoration no longer duplicates
the existing mascot. Saved assessments retain existing review, immutability,
reachability and server replay checks; no executable author uploads are accepted.

Selected reaction counts keep a readable text color on a soft accent background.
The server-owned HUD includes available KodeBits and a Wallet destination. Staff
see their owned balance without participant XP/streaks. Focused security/editing
and standalone resource-preview pages omit the progression HUD.

## Private document previews

PDFs retain native inline viewing. DOCX files render document pages and PPTX files
render slide images from the authorized original bytes, instead of relying only
on extracted text. The lesson pane width is adjustable from 30% to 70% on wide
screens, with enlarged viewing; phones open a full-viewport dialog and may request
native fullscreen. Standalone resource pages offer navigation minimization and
fullscreen with a fixed-surface fallback. Download and extracted-text alternatives
remain available.

Office rendering runs in a separate, nonce-authorized classic bundle within an
opaque-origin CSP sandbox (`allow-scripts`, **no** `allow-same-origin`). Network
connections, forms and external resources are blocked. Original bytes stay inside
the existing access-controlled, no-store resource response; no Office/Google
public viewer receives private files. Size and checksum are rechecked before
embedding bytes. Uploaded hyperlinks are inert, DOCX altChunks/comments are
disabled, and PPTX SVG is displayed as image data rather than active inline HTML.

These are approximate browser previews, not a guarantee of Microsoft Office
fidelity. Complex charts, fonts, layouts or unsupported features may differ or
require the original download. No macros or uploaded code are executed. The
Office bundle loads only for Office previews; ordinary pages retain the main
application bundle. `npm run build` builds both manifests; deploy the entire
`public/build` output. With `npm run dev`, build the Office bundle once first.

Pinned renderer provenance:

| Dependency | License / source | Use |
| --- | --- | --- |
| docx-preview 0.4.1 | Apache-2.0; [docxjs](https://github.com/VolodymyrBaydalka/docxjs) | Browser document pages |
| @office-kit/pptx 0.24.0 | MIT; [Office Kit](https://github.com/office-kit/pptx) | Browser presentation parsing |
| @office-kit/pptx-preview 0.15.0 | MIT; same repository | Slide-to-SVG images |
| dompdf/dompdf 3.1.6 | LGPL-2.1; [Dompdf](https://github.com/dompdf/dompdf) | Server-generated static reports/receipts |
| openspout/openspout 5.12.0 | MIT; [OpenSpout](https://github.com/openspout/openspout) | Spreadsheet exports |

Composer/npm lockfiles pin transitive packages. Browser distribution notices live
in `public/third-party`; Composer retains server-package licenses. Office Kit is
pre-1.0, so upgrades require representative preview regression checks.

## Explicit article completion and XP

The owner explicitly superseded the earlier no-reading-XP policy: a verified
Active participant may mark an assessment-free **Article** as read after a
server-recorded authorized opening. It grants **40 XP once per module**, shared
across standalone and course access. It advances course progress where applicable,
but grants **no daily streak, starter/Tower clear, assessment prerequisite credit
or Contributor eligibility credit**. Videos without assessments remain course
reading progress only. Existing assessed module reward references prevent another
grant if that module already earned its first-completion reward.

The user/module uniqueness constraint and locked transaction own completion and
reward together. Original revision linkage is retained; publication edits and
replays cannot repeat the reward. Withdrawal, archival, access and current session
are rechecked. Completion history blocks permanent content deletion and is removed
by the authorized account-erasure workflow. Old course reading records are not
silently rewarded: a newly authorized explicit article completion is required.

## Administrator reports

G07 offers an entire-platform overview and scoped account, content, validated
learning, coding execution, posted economy, reward, engagement, governance and
operations reports. Four observations compare registration windows, count distinct
assessed participants, calculate coding pass rate from Passed/Failed attempts, and
show the submitted-window evaluation backlog. They describe recorded data, not a
prediction, unique-module completion rate, live billing feed or tax statement.

The existing independent PostgreSQL repeatable-read, read-only snapshot and fresh
Administrator/session checks remain. Exports use the same filtered snapshot:
formatted A4 PDF with repeating table headers/page numbers; UTF-8 CSV with formula
escaping; and wrapped, typed XLSX cells that never turn strings into formulas.
Downloads are private/no-store and contain aggregates, not learner identities or
credentials. Monetary metrics retain their labeled exact minor units. Current
states/reactions are not reconstructed historical transitions; erased private
records are excluded. PDF generation permits only internal report/receipt views,
with remote access, PHP and JavaScript disabled.

## Provider preparation, without activation

**SendGrid:** the existing TLS SMTP boundary delivers a six-digit verification
code alongside the secure-link fallback. Codes expire after ten minutes and allow
five incorrect guesses per issuance. Their hash binds account, email and issuance
under an application-key HMAC; queued plaintext is encrypted at rest and removed
after delivery/cancellation. Resend invalidates the previous code. Activation is
single-use under the account lock; email edits/session security revoke old codes.
Existing request cooldown/maximum, generic errors and route throttles remain.

**Xendit:** purchase submission opens an owned status page. Bounded same-origin
polling reports checkout readiness and verified state; it pauses on navigation
and recovers after history restoration. Checkout remains unavailable when provider
readiness is disabled. Only the existing authenticated callback plus retrieved,
matching provider proof credits the Transaction-owned ledger. A return URL or
poll cannot credit money. Verified purchases expose an owned HTML/PDF payment
receipt and attach the same structured receipt to queued financial email.
Delivery failure never reverses a financial commit. These are payment receipts,
not tax invoices. Receipt and status routes reject other account owners.

**Judge0:** existing durable queued evaluation, compiler preflight, private test
cases, resource limits and three attempts per context remain. Additional tunable
operational defaults cap each account at three queued/evaluating submissions across
contexts and twenty confirmations in a rolling 24 hours. The account lock
serializes admission; rejected admissions consume no attempt and idempotent
retries consume no additional work. Local admission caps do not invalidate the
provider compiler fingerprint. UI feedback shows evaluated case progress and
terminal results without exposing hidden input or provider output.

See [integration setup](integration-setup.md) and the [deferred register](deferred-features.md)
for private configuration and required sandbox/staging drills. No credentials,
real messages, payments or learner execution were requested/enabled in this work.

## Verification

Focused PostgreSQL coverage checks article access/idempotent XP/streak exclusions,
encrypted OTP expiry/attempts, account limits, Office access/CSP, private exports,
callback-owned receipts and global evaluation admission. Browser coverage checks
direct visual editing, real DOCX/PPTX output, pane sizing/fullscreen, reaction
contrast, responsive Wallet/HUD, ten-account filtering and export downloads.
Existing creator review/course/learner journeys and accessibility checks remain
part of CI. Synthetic PDF exports are rendered and visually inspected; originals
and live provider contracts still require staging acceptance.
