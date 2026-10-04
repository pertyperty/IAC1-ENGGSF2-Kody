# Guided creation and continued play

Supports D01/D02, B01/B03/B05 and the approved game-first amendment. Content owns
the lesson examples and publication; Gamification continues to own validated
progress. No schema changes, rewards, provider activation or new business rules.

The studio offers eight editable starters: sequences, loops, conditions, Pixel
Studio, Number Machine, Sort Lab, Terminal Quest and a two-choice quiz. An
allowlisted example query prefills a new unsaved form with lesson text and existing
versioned assessment defaults. It neither creates a record nor grants publication.
Current validation, owner/session checks, immutable approved revisions and staff
review remain required. Blank drafts are still supported; existing drafts and
validation-restored input are not replaced by examples. The editor explains teach,
preview and save/review steps. Garden/scenario previews and saved quiz previews
remain local practice without completion endpoints.

The hub derives the next uncompleted unlocked starter level and today's activity
from the existing database snapshot. A quiz win qualifies daily practice without
unlocking a ladder level. Fully cleared trails point to Learning; guest landing
wins point to registration/sign-in and disclose that the trial is not saved.
Signed-in trials and saved starter lessons point back to the continued-play hub.
No browser result is promoted into saved progress without server validation.

Mobile navigation wraps for authenticated accounts. Garden arrow controls are
44×44 pixels, auxiliary controls have 44-pixel minimum targets, keyboard activation
works and existing focus/reduced-motion behavior remains. Live learner lesson
titles use h1; creator/reviewer previews retain h2 under their page heading.

`CreatorJourneyTest` covers all eight starter saves/previews, rejected example
values, guest/role restrictions, next-level and daily state, landing links and the
registration → verification → play → enrollment → assessment HTTP workflow.
Browser rehearsal also completed custom creation → review → publication and
learner enrollment → server-confirmed win. See [operations evidence](deployment-operations.md).
Checks are targeted accessibility observations, not a full WCAG audit.

Local verification (2026-10-04): full PostgreSQL suite 961 tests / 6,968 assertions
passed; final cached config/route/view regression set 91 tests / 809 assertions
passed, including the new learner h1 assertion. All 32 frontend tests, Pint,
workflow YAML, Vite build and diff checks passed. Creator gallery and learner hub
fit a 390px mobile viewport after correcting signed-in navigation overflow.
