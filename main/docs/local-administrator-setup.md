# Local Administrator setup

The owner requested a local Administrator on 2026-10-07. This is development
provisioning through an operator's terminal, separate from public registration,
role applications and G02 Moderator appointments. It supplies no production
bootstrap policy or public Administrator-creation endpoint.

## Create a fresh local account

Run from the Laravel application directory with its intended local `.env`:

```sh
php artisan kody:admin-create-local admin@kody.local --username=kody_admin
```

The command prompts for a hidden password and confirmation, using the existing
12–32-character mixed-case/number/symbol policy. Passwords are never command
arguments. The configured Laravel password hasher owns hashing (Argon2id by
default). Optional `--first-name` and `--last-name` use the account name rules.

Alternatively, `--generate` creates a random password and writes it exclusively
to `storage/app/private/local-admin-credentials.txt`. That ignored file is outside
the public directory; the command never prints its contents. It refuses to
replace an existing file. POSIX creation uses a restrictive umask and mode 0600.
On Windows, restrict the file's ACL to the current owner before sharing the
machine. Change the password through My account after signing in, then remove
the credential file. Never publish, commit or back it up as a release artifact.

The command requires `APP_ENV=local` or `testing` and a resolved loopback
PostgreSQL connection. Production/staging and remote hosts are refused. It
creates a fresh Active Administrator with a trusted local email-verification
timestamp; this is not evidence that an email was delivered or its address owns
a public mailbox. Normal login, lockouts, session replacement and authorization
still apply. `admin@kody.local` is for local use and cannot receive real recovery
mail. No existing account is promoted, reset or overwritten.

User creation and a secret-free `local_administrator_created` audit entry are
one transaction. Unique email/username constraints protect racing invocations;
file-write/audit failures roll back the account. A failed invocation removes
only a credential file it created. After a process interruption, inspect any
leftover private file and account state before trying again.

## Local provision on 2026-10-07

Created `admin@kody.local` / `kody_admin` in the owner's existing loopback `kody`
development database. All current migrations were already applied. The generated
credential file lives in this worktree's private storage and its Windows ACL was
restricted to the current owner. Credentials are not tracked in Git. No provider
calls, verification email, deployment or production account were created.

`LocalAdministratorTest` covers hashing, normal login/staff access, rejected
environments/hosts, duplicate identity preservation, exclusive credential-file
creation, password validation and audit rollback. The shared workspace continues
to use existing policies; Administrators do not acquire the Moderator-only E02
weekly scheduling permission.

## Reproducible local role accounts

The owner also requested verified Active accounts for every role and their inclusion
in seeders on 2026-10-07. This is a local development fixture amendment, not a change
to A01/A02 registration or A09/A10/G02 role elevation. No schema or public route changes.

With `APP_ENV=local` and loopback PostgreSQL, run:

```sh
php artisan db:seed
```

`DatabaseSeeder` calls `LocalRoleAccountsSeeder` only in the local environment.
For an explicit isolated testing invocation:

```sh
php artisan db:seed --class=LocalRoleAccountsSeeder --env=testing
```

| Role | Email | Username |
| --- | --- | --- |
| Learner | learner@kody.local | kody_learner |
| Contributor | contributor@kody.local | kody_contributor |
| Instructor | instructor@kody.local | kody_instructor |
| Moderator | moderator@kody.local | kody_moderator |
| Administrator | admin2@kody.local | kody_admin_two |

Each missing account receives a unique generated 30-character password, Laravel
hashing, Active status and a trusted local verification timestamp. No Unverified
fixtures are created. New credentials go into an exclusively created, ignored
`storage/app/private/local-role-credentials-<uuid>.txt` file; console output shows
only its path. Files from earlier manual provisioning remain usable and unchanged.
Restrict Windows ACLs to the owner on shared machines; POSIX files use mode 0600.
Existing accounts are never reset, reactivated, verified or promoted on reruns;
email/username/role collisions fail and roll back the whole batch. A removed fixture
can be recreated with a fresh password in a new file. The original
`admin@kody.local` account is outside this seeder and remains unchanged.

The explicit role seeder also refuses production/staging, non-PostgreSQL connections
and non-loopback database hosts, even with `--force`. Default production/staging
seeding creates no fixture accounts. The owner plans to remove these fixtures
before deployment: remove this seeder and its `DatabaseSeeder` call together, and
exclude local databases and private credential files from deployment artifacts.
Do not copy a development database into production. Removing seed code does not
remove accounts already present in a database.

All new users and secret-free `local_role_account_created` audits commit together;
storage/audit errors roll back the batch and clean up only its newly created file.
Unique database constraints protect competing invocations; a racing collision can
be retried. After an interrupted process, inspect database and private-file state
before rerunning. Fixtures grant no content, progress, financial credits or fabricated
role-application history. Directly provisioned Moderators retain an unknown prior
role and the existing removal safeguard. Normal server authorization still applies.

`LocalRoleAccountsSeederTest` covers all five roles, private generated credentials,
hashing, idempotency, nonlocal defaults, explicit deployment/remote refusal, identity
conflicts and transactional rollback on audit/storage failure.
