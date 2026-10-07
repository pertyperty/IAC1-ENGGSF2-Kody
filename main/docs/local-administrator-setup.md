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
