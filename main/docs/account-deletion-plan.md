# Next step: permanent account deletion/anonymization

The approved SRS labels this use case A10 Delete Account; it separately labels
Instructor verification A10. Identify the use case by name as well as number in
traceability. This file is a requirements/design prompt, not implemented deletion.

## Authentication conflict requiring an owner decision

Delete Account requires an authenticated owner and permits Active or Archived
status. A07 terminates sessions on archiving and A03 blocks Archived login; the
current account-session middleware deliberately requires verified Active status.

Recommended resolution: require Archived users to reactivate through approved
A04 recovery before accessing authenticated self-service deletion. Alternatively,
define a separate tightly scoped deletion-proof flow for Archived users. Do not
relax ordinary authenticated routes or let generic recovery proofs become broad
Archived account sessions. No resolution has been approved yet.

## Implementation prompt after resolution

Account Management owns confirmation, password re-authentication, session/token
revocation and deletion/anonymization. Preserve referential and audit integrity
without unnecessary personal data. The SRS requires an explicit confirmation
phrase, final confirmation, verified password and no later account recovery.
Inventory identity fields, credential objects/version history, mail recipients,
notification payloads, learner source and creator content before designing the
mutation. Document removal/anonymization and retention decisions; do not assume
that marking an account Deleted alone removes personal data.

Use database transactions/locks, durable retry-safe private-file cleanup where
needed, fresh authorization, sanitized logs and PostgreSQL constraints. Preserve
the existing paid-feature deferrals. Cover stale/concurrent requests, confirmation,
password errors, restricted accounts, session/token revocation, personal-data
removal, rollback, cleanup retries and non-recoverability with appropriate tests.
Do not claim privacy compliance or deployment verification from local tests.
