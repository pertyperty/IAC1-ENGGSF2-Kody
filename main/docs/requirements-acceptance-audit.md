# Requirements acceptance audit

Reviewed 2026-10-05 against the supplied **Kody SRS v1.4**, SDD through section 4
and approved owner amendments in AGENTS.md and the [economy/launch decisions](economy-and-launch-decisions.md).
Original sources remain outside Git. SRS SHA-256:
`0ef49e72042b102ea35ea93b564869fbf5ea99e8536016fe4a0010262a4cfb66`.

This is the master acceptance checklist. The [implementation map](implementation-status.md)
provides navigation; feature records retain implementation detail. “Local slice”
means implemented behavior with automated coverage, not production acceptance.
“Amended” indicates explicit owner policy superseded original behavior. Live
provider/infrastructure evidence remains separate. No row certifies every original
alternate flow or NFR. See [browser verification](acceptance-verification.md).

## Functional map

| SRS use case | Acceptance state | Implementation / coverage | Scope and remaining boundary |
| --- | --- | --- | --- |
| A01 Register Account | Local slice / content gap | [account-implementation-plan](account-implementation-plan.md) · [tests](../tests/Feature/Account/RegistrationTest.php) | Terms content/version missing; no recorded acceptance. |
| A02 Verify Email | Local slice / live unverified | [account-implementation-plan](account-implementation-plan.md) · [tests](../tests/Feature/Account/EmailVerificationTest.php) | Unverified activation; real mail deferred. |
| A03 User Login | Local slice / amended / live unverified | [login-implementation](login-implementation.md) · [tests](../tests/Feature/Account/LoginTest.php) | Approved lockouts/single session; linked Google disabled. |
| A04 Recover Account | Local slice / live unverified | [recovery-profile-implementation](recovery-profile-implementation.md) · [tests](../tests/Feature/Account/RecoveryTest.php) | Generic response and approved role/status eligibility; live delivery deferred. |
| A05 View Account | Local slice | [recovery-profile-implementation](recovery-profile-implementation.md) · [tests](../tests/Feature/Account/ProfileTest.php) | Owned private profile. |
| A06 Edit Account | Local slice / amended | [profile-editing-implementation](profile-editing-implementation.md) · [tests](../tests/Feature/Account/ProfileEditingTest.php) | Approved all-role fields and sensitive reauthentication; elevation reviewed. |
| A07 Archive Account | Local slice | [account-archival-implementation](account-archival-implementation.md) · [tests](../tests/Feature/Account/ArchivalTest.php) | Participant archival, session revocation and retained history. |
| A08 Delete Account | Local slice / live unverified | [account-deletion-plan](account-deletion-plan.md) · [tests](../tests/Feature/Account/DeletionTest.php) | Creator retention review, settlement/evaluation guards; managed erasure awaits staging. |
| A09 Request Contributor Role | Local slice / amended | [contributor-application-plan](contributor-application-plan.md) · [tests](../tests/Feature/Account/ContributorApplicationTest.php) | Approved AND eligibility, distinct achievements and one Pending application. |
| A10 Verify Instructor Credentials | Local slice / amended | [creator-review-implementation](creator-review-implementation.md) · [tests](../tests/Feature/Account/InstructorReviewTest.php) | Approved application states and manual Moderator/Admin credibility review. |
| B01 View User Dashboard | Local slice | [platform-completion-implementation](platform-completion-implementation.md) · [tests](../tests/Feature/PlatformDashboardTest.php) | Owned persisted dashboard and bounded queries. |
| B02 Browse Courses and Modules | Local slice | [module-discovery-implementation](module-discovery-implementation.md) · [tests](../tests/Feature/ModuleDiscoveryTest.php) | Reviewed catalog, literal search and assessment filters. |
| B03 Enroll in Course | Local slice / live unverified | [course-learning-implementation](course-learning-implementation.md) · [tests](../tests/Feature/EconomyWorkflowsTest.php) | Atomic free/paid enrollment; live funding disabled. |
| B04 Access Course Module | Local slice / amended | [course-learning-implementation](course-learning-implementation.md) · [tests](../tests/Feature/CourseLearningPathTest.php) | Approved sequential enrollment snapshots and explicit reading completion. |
| B05 Access Standalone Module | Local slice | [creator-learner-journey](creator-learner-journey.md) · [tests](../tests/Feature/CreatorJourneyTest.php) | Authenticated standalone lessons; approved module reuse across courses. |
| B06 Browse Challenges | Local slice | [challenge-discovery-implementation](challenge-discovery-implementation.md) · [tests](../tests/Feature/ChallengeDiscoveryTest.php) | Reviewed immutable topic/concept/language/difficulty filters. |
| B07 Participate in Challenge | Local slice / live unverified | [challenge-submission-implementation](challenge-submission-implementation.md) · [tests](../tests/Feature/ChallengeSubmissionTest.php) | Confirmed durable attempts/access gates; Judge0 disabled. |
| B08 View Execution Feedback | Local slice / live unverified | [challenge-submission-implementation](challenge-submission-implementation.md) · [tests](../tests/Feature/ChallengeSubmissionTest.php) | Owned escaped feedback; live compiler verification deferred. |
| B09 View Leaderboard | Local slice / amended | [economy-implementation](economy-implementation.md) · [tests](../tests/Feature/EconomyWorkflowsTest.php) | Approved authoritative XP/ranks; no browser/payment/reading XP. |
| B10 React to Content | Local slice / amended / live unverified | [content-feedback-implementation](content-feedback-implementation.md) · [tests](../tests/Feature/ContentFeedbackTest.php) | Approved replaceable reaction; numeric ratings deferred. |
| B11 View FAQ | Local slice / amended | [faq-implementation](faq-implementation.md) · [tests](../tests/Feature/FaqTest.php) | Approved public Active FAQ reading and four categories. |
| C01 Create Code Challenge | Local slice / amended | [challenge-studio-implementation](challenge-studio-implementation.md) · [tests](../tests/Feature/ChallengePublishingTest.php) | Approved Contributor/Instructor authoring and Python/Java/C++. |
| C02 Approve Code Challenge | Local slice | [challenge-studio-implementation](challenge-studio-implementation.md) · [tests](../tests/Feature/ChallengePublishingTest.php) | Versioned staff approval preserves existing publication. |
| C03 Submit Code Solution | Local slice / amended / live unverified | [challenge-submission-implementation](challenge-submission-implementation.md) · [tests](../tests/Feature/ChallengeSubmissionTest.php) | Approved three attempts across revisions, separate weekly budget; live deferred. |
| C04 Evaluate Code Solution | Local slice / live unverified | [challenge-submission-implementation](challenge-submission-implementation.md) · [tests](../tests/Feature/ChallengeSubmissionTest.php) | Provider-only sandbox adapter and durable retries; fake-provider evidence. |
| C05 Edit Code Challenge | Local slice | [challenge-studio-implementation](challenge-studio-implementation.md) · [tests](../tests/Feature/ChallengePublishingTest.php) | Immutable replacements; submissions retain original revision. |
| C06 Archive Code Challenge | Local slice / amended | [challenge-studio-implementation](challenge-studio-implementation.md) · [tests](../tests/Feature/ChallengePublishingTest.php) | Approved Published archival and lifecycle. |
| C07 Delete Code Challenge | Local slice / amended | [content-deletion-implementation](content-deletion-implementation.md) · [tests](../tests/Feature/ContentDeletionTest.php) | Approved all retained dependencies block deletion, locked recheck. |
| D01 Create Module | Local slice / amended | [creator-studio-implementation](creator-studio-implementation.md) · [tests](../tests/Feature/ModulePublishingTest.php) | Approved Instructor authoring/staff review; typed game/quiz data. |
| D02 Edit Module | Local slice | [creator-studio-implementation](creator-studio-implementation.md) · [tests](../tests/Feature/QuizAuthoringTest.php) | Immutable revisions; unsaved previews grant no progress. |
| D03 Archive Module | Local slice / amended | [creator-studio-implementation](creator-studio-implementation.md) · [tests](../tests/Feature/ModulePublishingTest.php) | Approved Published archival hides lessons from learners. |
| D04 Delete Module | Local slice / amended | [content-deletion-implementation](content-deletion-implementation.md) · [tests](../tests/Feature/ContentDeletionTest.php) | Approved retained dependency protection, including inactive history. |
| D05 Create Course | Local slice / amended | [course-composition-implementation](course-composition-implementation.md) · [tests](../tests/Feature/CoursePublishingTest.php) | Approved Instructor courses and staff publication review. |
| D06 Edit Course | Local slice | [course-composition-implementation](course-composition-implementation.md) · [tests](../tests/Feature/CoursePublishingTest.php) | Published version remains during replacement review. |
| D07 Assign Module to Course | Local slice / amended | [course-composition-implementation](course-composition-implementation.md) · [tests](../tests/Feature/CoursePublishingTest.php) | Approved cross-course reuse, pinned revisions and unique ordering. |
| D08 Archive Course | Local slice / amended | [course-learning-implementation](course-learning-implementation.md) · [tests](../tests/Feature/CourseLearningTest.php) | Approved archival blocks new joins and retains existing enrollment access. |
| D09 Delete Course | Local slice / amended | [content-deletion-implementation](content-deletion-implementation.md) · [tests](../tests/Feature/ContentDeletionTest.php) | Approved retained references block permanent deletion. |
| E01 Create Gamified Activity from Preset | Local slice / amended / live unverified | [game-presets-implementation](game-presets-implementation.md) · [tests](../tests/Feature/GamePresetsTest.php) | Approved typed snapshots and creator role boundary; direct preset KB rewards deferred. |
| E02 Configure Weekly Challenge | Local slice / amended | [weekly-challenge-plan](weekly-challenge-plan.md) · [tests](../tests/Feature/WeeklyEventsTest.php) | Approved Manila Sunday windows, future planning, immutable started events. |
| E03 Evaluate Weekly Submissions | Local slice / live unverified | [weekly-challenge-plan](weekly-challenge-plan.md) · [tests](../tests/Feature/WeeklyResultsTest.php) | Verified retained scores and per-event budgets; live execution deferred. |
| E04 Publish Weekly Results | Local slice | [weekly-challenge-plan](weekly-challenge-plan.md) · [tests](../tests/Feature/WeeklyResultsTest.php) | Staff-confirmed immutable publication and idempotent notices/rewards. |
| E05 Grant Rewards | Local slice / amended | [economy-implementation](economy-implementation.md) · [tests](../tests/Feature/EconomySettlementTest.php) | Approved once-per-content XP and backed capped KB rewards. |
| E06 Determine Ranking | Local slice / amended | [economy-and-launch-decisions](economy-and-launch-decisions.md) · [tests](../tests/Feature/WeeklyResultsTest.php) | Approved best passed-case score and competition-rank ties supersede original tie ordering. |
| F01 Purchase Tokens | Local slice / live unverified | [economy-implementation](economy-implementation.md) · [tests](../tests/Feature/EconomyWorkflowsTest.php) | Disabled PHP GCash packages; verified callback/retrieval credit only. |
| F02 Use Tokens | Local slice | [economy-implementation](economy-implementation.md) · [tests](../tests/Feature/EconomyLedgerTest.php) | Exact FIFO spending, atomic grants and nonnegative balance. |
| F03 View Publisher Earnings | Local slice | [economy-implementation](economy-implementation.md) · [tests](../tests/Feature/EconomySettlementTest.php) | Owned earnings and mature claims/refund history. |
| F04 Receive Earnings | Local slice / live unverified | [economy-implementation](economy-implementation.md) · [tests](../tests/Feature/EconomyRecoveryTest.php) | Instructor cash / Contributor KB; payout reservation and verified settlement; live deferred. |
| F05 Receive Notifications | Local slice / live unverified | [economy-implementation](economy-implementation.md) · [tests](../tests/Feature/EconomyWorkflowsTest.php) | Durable financial notices; real delivery deferred. |
| G01 View User Accounts | Local slice | [account-governance-implementation](account-governance-implementation.md) · [tests](../tests/Feature/Account/GovernanceTest.php) | Paginated staff inventory and target authorization. |
| G02 Update User Account | Local slice / amended | [support-profile-corrections-implementation](support-profile-corrections-implementation.md) · [tests](../tests/Feature/Account/SupportProfileCorrectionTest.php) | Approved support fields plus Moderator appointments/removal; audits and revocation. |
| G03 Suspend User Account | Local slice / amended | [account-governance-implementation](account-governance-implementation.md) · [tests](../tests/Feature/Account/GovernanceTest.php) | Approved hierarchy; self and Administrator targets blocked. |
| G04 Reinstate User Account | Local slice | [account-governance-implementation](account-governance-implementation.md) · [tests](../tests/Feature/Account/GovernanceTest.php) | Version-checked reinstatement, notices and retained history. |
| G05 Approve Contributor Requests | Local slice / amended | [contributor-application-plan](contributor-application-plan.md) · [tests](../tests/Feature/Account/ContributorApplicationTest.php) | Approved eligibility, resubmission history and 500-character storage. |
| G06 Moderate Content | Local slice / amended | [content-withdrawal-implementation](content-withdrawal-implementation.md) · [tests](../tests/Feature/ContentModerationTest.php) | Approved withdrawal blocks all learners, preserves data/evaluations. |
| G07 View System Reports | Local slice | [system-reports-implementation](system-reports-implementation.md) · [tests](../tests/Feature/Account/SystemReportsTest.php) | Read-only dated aggregates; Administrator-only financial reports. |
| G08 Create Game Preset | Local slice | [game-presets-implementation](game-presets-implementation.md) · [tests](../tests/Feature/GamePresetsTest.php) | Administrator typed presets, never uploaded executable rules. |
| G09 Update Game Preset | Local slice | [game-presets-implementation](game-presets-implementation.md) · [tests](../tests/Feature/GamePresetsTest.php) | Immutable revisions retain existing instances. |
| G10 Delete Game Preset | Local slice / amended | [game-presets-implementation](game-presets-implementation.md) · [tests](../tests/Feature/GamePresetsTest.php) | Approved inactivation preserves references and prevents new use. |
| G11 Create FAQ Entry | Local slice | [faq-implementation](faq-implementation.md) · [tests](../tests/Feature/FaqTest.php) | Administrator bounded escaped FAQ creation. |
| G12 Update FAQ Entry | Local slice | [faq-implementation](faq-implementation.md) · [tests](../tests/Feature/FaqTest.php) | Versioned FAQ editing and audit. |
| G13 Delete FAQ Entry | Local slice | [faq-implementation](faq-implementation.md) · [tests](../tests/Feature/FaqTest.php) | Confirmed version-checked FAQ deletion; this is not financial reporting. |

## Actual gaps and source corrections

- **A01 Terms acceptance:** the functional summary requires acceptance, detailed
  A01 omits it and no approved Terms content/version has been supplied.
  Registration currently does not record acceptance. Reviewed Terms/privacy
  notices and server-enforced versioned consent are required before public launch.
  Do not substitute invented legal text.
- **Availability conflict:** summary says ≥99%; §4.2 says 99.9%. Preserve the
  stricter engineering target pending source harmonization; neither is proved locally.
- **B05 cross-reference:** its course-module rule points to E05 (rewards).
  Approved module reuse/course pinning defines current behavior; original text
  needs correction during document revision.
- **G07/G13:** G07 owns reports; G13 owns Delete FAQ. Financial reports extend G07.
  The implementation index and economy record now reflect these correct IDs.
- Actual launch curriculum publication requires designated creators/staff.
  Editable plans and disposable test publications are not real launch records.

## Non-functional acceptance

| SRS target | Local preparation/evidence | Required before acceptance |
| --- | --- | --- |
| Core pages ≤3 seconds; normal execution feedback 3–5 seconds | Bounded queries, assets and asynchronous adapter | Staging broadband/provider percentile measurements |
| ≥500 concurrent users, ≤2-second degradation | PostgreSQL concurrency/idempotency tests | Baseline plus staged 500-user load; pilot sizing does not change SRS |
| TLS 1.3; sensitive encryption at rest | Secure config checks, encrypted fields/private-storage preparation | Actual TLS negotiation, deployed RDS/S3/IAM/encryption verification |
| Sandboxing, RBAC, sessions, rate limits | Provider-only execution; authorization, CSRF and attempt tests | Live Judge0 limits/isolation and staged penetration review |
| 99.9% uptime and fault recovery | Health/reconciliation/retry procedures | Monitoring/SLO history, response and outage drills |
| Backup interval ≤24 hours, restore | Local custom-format restore; managed backup template | Encrypted managed DB/key/private-object restore and timed staging recovery |
| Maintenance 1–5 AM Manila, ≤4 hours/month | Deployment/rollback procedures | Scheduled maintenance and measured downtime |
| Chrome/Firefox/Edge/Safari and mobile | Chromium browser journeys, viewport/keyboard/axe checks | Other engines, actual devices, screen reader and user testing |
| Async effects, modularity, logs | Module-owned services, durable jobs/notices, full/cached CI | Supervised workers/scheduler and actual delivery/deployment rehearsal |

The [deferred register](deferred-features.md) owns activation blockers. Do not
request private credentials simply because this checklist is revisited. Pilot
sustainability estimates are not capacity or financial-viability evidence.
