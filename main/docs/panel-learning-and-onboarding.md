# Panel follow-up: learning and onboarding

The owner authorized implementation of the two 18 October 2026 panel documents and delegated product recommendations. Their original “review only” labels are retained as provenance, not treated as a new ban on the owner's requested work. The owner subsequently requested simple Terms and Privacy. This record is the amendment for this delivery; the original SRS/SDD are not represented as rewritten.

## Adopted decisions and traceability

| Area | Decision / implementation | Preserved boundary |
| --- | --- | --- |
| A01 registration | Username, email, password and confirmation only. No name or credential collection. All accounts start Unverified Learners. | Existing email verification, exact-case username/case-insensitive email sign-in, password rules/hash, lockouts and single sessions. |
| A06 / G02 identity | Personal names are nullable, separately editable and clearable. The compatibility `name` value uses supplied optional names or username. Public application lists use username. | No backfill or reset of existing identities. Sensitive edits and staff support corrections keep current password, locked versions, audit and session policies. |
| A10 creator onboarding | Apply from the verified profile through the existing private credential application. Public signup rejects Instructor fields/uploads. | No automatic Instructor assignment; existing Pending/Approved/Rejected history, review and notices remain. |
| Tower tracks | Beginner floors 1–25 and Intermediate practice floors 26–50 are independently enterable; published levels inside each are sequential. Expert is explicitly unavailable. | Choosing a track creates no clears, XP, streak or assessment evidence. Guest trials remain floors 1–3; level 4+ still invites sign-in. Old clears remain. Later appended floors default to Intermediate practice, not an invented Expert qualification. |
| Teaching before play | Shared briefing gives an outcome, actual task, success condition and a different worked example before each game board. | Creator scenario data remains typed and escaped. The simulator's one-based positions are distinguished from language indexing. |
| Eight game interpreters | Readable pseudocode, actual state, Step / Replay / Restart preview. Garden shows iteration and per-move condition truth. Arcade traces use the same bounded interpreter as Run. | No `eval`, host shell, rewards or save requests from trace controls. Only explicit Run submits. Terminal Enter is now an unsaved experiment; it cannot silently submit a successful assessment. |
| Curriculum repair | Scenario-aware Number/Garden/Terminal instructions and hints, accurate outcomes, “Foundation synthesis” instead of mastery. | New seeds remain append-only. Existing revisions use explicit guarded editorial publication or the existing Administrator publisher. |
| Forms | Visible native-bound help with `aria-describedby`, stable label names, inline error associations and correction links; source inventory. | No broader username/password/file permissions. Client hints are not authorization or complete validation. |
| Legal | Short original [Terms](legal/terms-draft.md) and [Privacy](legal/privacy-draft.md), with [publication prerequisites](legal/review-and-publication.md). | Unpublished; no pretend acceptance, fabricated operator or retention deletion. Live services remain deferred. |

This supersedes the earlier required signup names/Instructor-signup upload and global Tower sequencing where they conflict. It does not authorize new financial rules, secure-exam claims, open-ended manual grading or account purges.

## Curriculum evidence and rollout

The [50-floor matrix](tower-teaching-matrix.md) summarizes all 72 seed stages. The CLI audit separately inspects retained database revisions, including customized/historical ones:

```sh
php artisan kody:tower-curriculum-audit --seed-only
php artisan kody:tower-curriculum-audit > storage/app/tower-editorial-audit.json
php artisan kody:tower-teaching-upgrade
php artisan kody:tower-teaching-upgrade --apply
```

The audit is private editorial material with practice answers, not a learner endpoint. It reports objectives, prerequisites, task/success, hint/example, reference submission and validation, wrong approaches, transfer reflection and provenance. It examines at most 5,000 retained revisions and explicitly reports truncation. A failed reference finder means manual editorial review is needed; it is not proof a customized level is impossible. Seed reference validation failure gives a nonzero command exit.

The upgrade defaults to review-only. `--apply` locks the seed advisory key and each level, recognizes the entire untouched original seed payload plus original provenance/null author/revision 1, verifies every proposed reference and creates a new immutable revision with a durable system audit. It skips edited or already upgraded levels. Failure rolls back the transaction. It never updates old stages, clears, attempts, XP, streaks or balances. Pending old-revision submissions get the existing 409/reload response. Administrator suggestions still publish through normal current-session, policy and version checks.

Before any local mutation, the read-only audit found **50 retained revisions, all reference-verified**, without truncation. The original Garden three-crystal boss prompt and inherited Number/Terminal hints were editorially stale despite reachable objectives. The audit validates reachability; it does not automatically judge a hint or measure understanding. No external curriculum was imported.

## Learning quality and limits

Read → predict → step → compare → modify is informed by [Raspberry Pi Foundation's PRIMM teaching guidance](https://www.raspberrypi.org/blog/using-primm-to-teach-programming-a-new-short-course-for-educators/). These simulator tasks are formative practice. Number Machine accepts any bounded arithmetic program reaching the target; Sort Lab does not enforce a named sorting algorithm. Keys in browser practice are not secret exam material. Reading a Python/Java/C++ quiz is not running that language; live execution remains behind deferred Judge0.

Optional prediction/transfer reflection is instructional guidance, not a new persisted diagnostic score. The existing independently graded language quizzes remain. A deeper novice-to-expert curriculum, validated diagnostic placement and measured learning effectiveness need content work and human study; no Expert label or mastery badge has been fabricated to hide that gap.

Usability/teaching acceptance script for a human pilot:

1. A novice states the task before touching the board and predicts the effect of one movement.
2. A returning programmer enters Intermediate without invented Beginner clears and explains the first Boolean expression.
3. A learner changes an arithmetic operation, traces both orders and explains the difference without using a memorized hint.
4. A learner distinguishes a loop body from its two executions, and a false IF from program termination.
5. A learner maps one-based simulator positions to a named language's indexing convention.
6. An editor repairs a stale hint, tests a working and plausible wrong submission, publishes a new revision and observes retained clears.

Record observed errors, task time and explanations with participant permission. Automated browser/server tests do not replace this human evidence.

## Database and release

Migration `2026_10_18_000001_allow_optional_profile_names` changes only the registered-profile CHECK constraint. Existing rows and anonymity/status/session constraints are retained. Rollback refuses to invent names if optional identities exist; use a compatible application release instead. Run Composer install from the lock in each checkout and build both normal and Office assets.

Verification covers server reference replay, independent-track admission/skip rejection, optional names, registration injection/queue rollback, immutable rollout/idempotency, preview isolation, desktop/mobile accessibility and populated upgrade/restore. Detailed final results are recorded below after execution. Providers/staging remain unverified and disabled.


### Local rollout and verification evidence

On 18 October 2026, after isolated populated upgrade/restore verification, the reviewed optional-name migration and guarded teaching upgrade were applied to loopback local PostgreSQL `kody`. A private custom-format backup was created first under ignored `storage/app/private`. Fifty untouched seed levels received new revisions. Fingerprints of all **77 protected tables** were unchanged, and all 50 original revisions were byte-equivalent as JSONB rows. Account identities, passwords, sessions, roles, progress and financial records were preserved. The operation was transactional and created the 50 editorial audit records; no provider was activated.

Initial focused runs passed 99 account/Tower tests (959 assertions), then 47 identity/support/teaching regressions (519 assertions). These overlapping counts are not added together. The populated upgrade and 81-table restore rehearsal passed with retained table fingerprints unchanged. Final full regression and CI results are added after completion; the initial full local runner exceeded its ten-minute harness timeout and was rerun with a longer bound, rather than represented as a pass.
