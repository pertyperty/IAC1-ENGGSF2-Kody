# Challenge submissions and evaluation — C03/C04/B07

## Approved requirements decisions

The owner approved these amendments on 2026-10-03, resolving C03 forwarding each
submission, C04's ambiguous single-evaluation/cancellation rules and B07's
server-side continuation after interruption:

- Evaluate every confirmed submission; at most one active evaluation per
  user/challenge context. Closing the browser does not cancel committed work.
- Three standard attempts per user/challenge across all published revisions.
  Each future weekly event has an independent three-attempt budget.
- Current authored challenges offer free participation to verified Active
  Learners, Contributors and Instructors. Paid, rank and prerequisite access are
  unavailable until their supporting rules and modules exist.

These are approved amendments; the source SRS/SDD files have not been rewritten.
Challenge Management owns attempt admission, records, test integrity and
evaluation. User Interaction presents the problem, confirmation and private
feedback. No balance, reward, rank, XP or learning-streak changes are made.

## Delivered scope

Published quests show remaining attempts, a language-specific source editor and
explicit confirmation. Submission pins the currently displayed approved revision;
stale revisions, archived quests and unsupported languages are rejected. Changing
publication does not reset attempts. Archived quests stop new attempts but already
confirmed work evaluates its pinned tests; existing results remain privately
readable through their attempt URL.

The account is freshly locked and its role, verified Active status and current
session are rechecked. Account → challenge → participation lock order serializes
admission. Repeated confirmation UUIDs return the same attempt only for identical
revision/language/code, including after publication changes. A different UUID
cannot start another attempt while one is active. Infrastructure failures do not
automatically refund or reset the confirmed-submission budget.

Source is capped at 65,536 bytes, kept exactly including whitespace, encrypted
with the managed Laravel APP_KEY and hidden from model serialization. It is never
flashed into validation-session history. The source-size limit is an operational
safeguard, not a newly attributed SRS constraint. Hidden inputs/outputs are never
loaded into learner pages. Result endpoints expose aggregate verdicts/counts and
static feedback only; raw stdout, stderr, compilation output and provider messages
are not requested or stored. This avoids program output revealing hidden inputs.
The owner alone reads their submitted source; authorized account-session middleware
also applies to the private status API. Blade and JS render feedback as text.

An additive migration creates provider verification profiles, per-user standard
participations, revision-pinned submissions and per-test evaluation records.
PostgreSQL foreign/composite keys bind tests and attempts to the same revision.
Checks constrain statuses, attempts/counts, completion timestamps, terminal
outcomes and leases. Unique confirmations/attempt numbers and a partial unique
index prohibit duplicate admission and two active submissions per participation.
The overdue index supports bounded scheduler scans. No existing records are deleted.

Attempts, encrypted source, case records, audit and the database queue job commit
together. The job contains only the submission UUID. The queue database connection
must use the application database; a separate configured connection blocks admission.
Even a test/default sync queue cannot execute code in the request.

## Provider boundary and recovery

The adapter implements the [official Judge0 CE API](https://ce.judge0.com/): active
languages, configuration limits, asynchronous submission creation and token-based
status polling. Compiler IDs are configuration, not guessed defaults. Language names
must match Python/Java/C++; per-challenge CPU and memory must fit verified limits.
Requests set network access off, one run, explicit CPU/wall/memory/stack/file/process
limits and no uploaded executable files, callbacks or compiler arguments.
Source/input/expected output use base64 transport; Judge0's configured expected-output
verdict determines pass/fail. Kody does not invent another normalization or scoring
formula. Provider status 13 is an infrastructure issue; normal wrong-answer,
timeout, compile and runtime verdicts are failed cases. All cases are evaluated
unless provider failure or the evaluation deadline prevents completion.

HTTP requests require a server-configured HTTPS URL without userinfo/query/fragment.
Credentials stay in server-side headers/config. Redirects are disabled; connection
timeout is three seconds, request/read timeout five seconds and response reads are
capped at 256 KiB. The adapter catches transport/response failures and emits only a
generic exception without its original exception, body, headers or secrets.
Database write failures log only SQLSTATE after rollback, never SQL bindings.

Each job invocation performs at most one remote operation outside database locks.
A durable 60-second lease prevents duplicate workers creating the same case.
The case is marked Creating before POST. Judge0 provides no creation idempotency
contract here: a timeout or lost response marks evaluation Unavailable; a recovered
Creating case is never POSTed again. Known encrypted tokens can be safely polled
again. Ready operations continue without an artificial delay; in-flight results
are polled at one-second intervals and transient GET failures retry after five
seconds. A 15-minute overall operational deadline bounds the workflow. Jobs have 20-second timeouts below
the database retry_after of 90 seconds, and bounded releases/attempts.

Queued/Evaluating are operational states in addition to academic Passed/Failed.
Unavailable denotes incomplete infrastructure evaluation, not a failed solution.
The minute scheduler closes overdue attempts and frees their active slot, including
when a worker permanently fails. Scheduler operation is required for recovery.
Browser status polling uses one-second intervals, same-origin credentials, no-store responses, bounded
polling, text rendering and stops after completion, authorization loss or page exit.
Manual refresh works without JavaScript. Queue latency and failed jobs need monitoring.

## Activation and deployment

1. Deploy migrations normally; keep APP_KEY stable and securely backed up for
   encrypted source/token readability. Retain these tables during application rollback.
2. Configure JUDGE0_URL, RapidAPI host/key and the three provider compiler IDs
   privately, using sandbox credentials. Optional X-Auth-Token stays server-side.
   Set JUDGE0_ENABLED=true only in a controlled environment; rebuild config caches.
3. Run `php artisan kody:judge0-check`. This performs only read-only language/limit
   checks, not code execution. A matching verified configuration fingerprint is
   required before admission. Credential/endpoint/ID/config changes invalidate
   the previous profile; rerun verification and restart workers after changes.
4. Supervise `php artisan queue:work database --queue=code-execution --sleep=1 --timeout=20`.
   Keep the existing worker for mail/default queues, and run the Laravel scheduler
   every minute. The default worker does not consume code-execution implicitly.
5. Perform sandbox submissions for all three compilers, review provider isolation,
   output comparison (including empty expected output), failure recovery and
   operational quotas. Measure the SRS 3–5-second normal-load feedback target;
   this polling implementation is not proof that the target is met. Large test
   suites may need bounded parallel case evaluation after provider measurements.
   Verification of reported settings is not proof of provider
   isolation or measured NFR performance.

The owner selected `https://judge0-ce.p.rapidapi.com` for live verification.
The example environment records that URL and host without credentials. Execution
remains disabled/unavailable in the default example configuration.
The owner explicitly deferred API-plan setup and credentials on 2026-10-03.
Do not treat missing credentials as requiring immediate setup or ask for the
key again during unrelated development. Resume live verification when the owner
is ready; keep execution disabled and use provider fakes in the meantime.
No new dependency, real provider call, local learner-code execution or deployment
was performed for this change. Live provider endpoint, credentials and compiler
IDs are still needed. Remote CI and production latency/security/NFRs are unverified.

## Verification and remaining scope

Automated PostgreSQL and HTTP-fake tests cover roles/current sessions, confirmation,
language/size/weekly validation, exact source whitespace, encrypted persistence,
queue rollback, SQL exception sanitization, idempotency, revision retention,
archive/result privacy, lifetime limits, concurrent final attempts, database
constraints, CSRF, hidden-output exclusion, deterministic verdicts, polling retries,
duplicate leases, ambiguous POST failures, overdue recovery and provider preflight.
Frontend tests cover bounded private status rendering, revoked authorization,
external URL refusal, network interruption and stopping on page exit.

Final local verification: the full PostgreSQL suite passed 371 tests / 2,558
assertions; 48 submission/concurrency tests passed with cached config/routes/views
(271 assertions), including actual database-queue advancement without artificial
delays. All 16 frontend tests, Pint, Vite build and diff checks
passed. All fifteen migrations run on clean disposable PostgreSQL databases
through the concurrency suite. Remote CI, authenticated browser visual checks,
real Judge0 execution and production deployment remain unverified.

C03/C04/B07 standard participation is implemented behind provider activation.
Weekly event records, scheduler lifecycle and independent event participations
are implemented in the [weekly event follow-up](weekly-challenge-plan.md).
Reward calculations, XP/ranks, paid admission and financial effects remain deferred.
Client-supplied weekly IDs are rejected; trusted weekly routes bind their event
and its pinned revision, while standard participation remains context zero.
No user-facing infrastructure failure is claimed to
be a wrong solution. Historical source retrieval/result access requires current
verified Active account authorization.

## Next implementation prompt

Configure the approved Judge0 sandbox privately, verify actual compiler IDs and
resource ceilings, run end-to-end sandbox submissions and failure drills, then
record provider version/isolation/quotas and measured feedback timings when the
owner is ready to configure the deferred API plan. Keep execution unavailable
while that input is missing and continue independent development. E02 weekly
events now use independent participation IDs and the approved separate attempt
budget; see the [weekly event implementation](weekly-challenge-plan.md).
Resolve reward formulas and ledger ownership before adding financial or ranking
effects. Preserve the game-first Play hub and separate Learning catalog.
