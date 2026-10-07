<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class LocalRoleAccountsSeeder extends Seeder
{
    public function run(AuditRecorder $audit): void
    {
        $connection = DB::connection();
        if (! app()->environment(['local', 'testing']) || $connection->getDriverName() !== 'pgsql'
            || ! in_array($connection->getConfig('host'), ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('Role account fixtures require local/testing and loopback PostgreSQL.');
        }

        $accounts = [
            ['learner@kody.local', 'kody_learner', Role::Learner],
            ['contributor@kody.local', 'kody_contributor', Role::Contributor],
            ['instructor@kody.local', 'kody_instructor', Role::Instructor],
            ['moderator@kody.local', 'kody_moderator', Role::Moderator],
            ['admin2@kody.local', 'kody_admin_two', Role::Administrator],
        ];
        $credentialPath = storage_path('app/private/local-role-credentials-'.Str::uuid().'.txt');
        $createdFile = false;

        try {
            DB::transaction(function () use ($accounts, $audit, $credentialPath, &$createdFile): void {
                $credentials = "Local Kody role accounts\n\n";
                $created = 0;
                foreach ($accounts as [$email, $username, $role]) {
                    $existing = User::query()->where('email', $email)->orWhere('username', $username)->get();
                    if ($existing->isNotEmpty()) {
                        if ($existing->count() !== 1 || $existing->sole()->email !== $email
                            || $existing->sole()->username !== $username || $existing->sole()->account_role !== $role) {
                            throw new RuntimeException('A fixture identity conflicts with an existing account.');
                        }

                        // Preserve passwords, verification, status, sessions and all user history on reruns.
                        continue;
                    }

                    $password = 'Aa1!'.bin2hex(random_bytes(13));
                    $user = new User(['email' => $email, 'username' => $username, 'first_name' => 'Kody',
                        'last_name' => $role->name, 'name' => 'Kody '.$role->name, 'password' => $password]);
                    $user->forceFill(['account_role' => $role, 'account_status' => AccountStatus::Active,
                        'email_verified_at' => now()])->save();
                    $audit->record(null, $user->id, 'local_role_account_created', 'User', (string) $user->id,
                        ['source' => 'local_role_seeder', 'environment' => app()->environment(), 'role' => $role->value]);
                    $credentials .= "Role: {$role->name}\nEmail: {$email}\nUsername: {$username}\nPassword: {$password}\n\n";
                    $created++;
                }

                if ($created === 0) {
                    return;
                }

                $credentials .= "Change passwords through My account. Never commit or publish this file.\n";
                $previousMask = umask(0077);
                try {
                    if (! is_dir(dirname($credentialPath)) && ! mkdir(dirname($credentialPath), 0700, true)) {
                        throw new RuntimeException('Private storage unavailable.');
                    }
                    $file = @fopen($credentialPath, 'x');
                    if ($file === false) {
                        throw new RuntimeException('Private credential file unavailable.');
                    }
                    $createdFile = true;
                    try {
                        if (! chmod($credentialPath, 0600) || fwrite($file, $credentials) !== strlen($credentials) || ! fflush($file)) {
                            throw new RuntimeException('Private credential write failed.');
                        }
                    } finally {
                        fclose($file);
                    }
                } finally {
                    umask($previousMask);
                }
            });
        } catch (Throwable) {
            if ($createdFile) {
                @unlink($credentialPath);
            }
            // Do not expose SQL parameters, password hashes or credential material in seeder output.
            throw new RuntimeException('Local role seeding failed; no accounts changed. Check identity conflicts and private storage.');
        }

        if ($createdFile) {
            $this->command?->info('Local role accounts created. Private credentials: '.$credentialPath);
        } else {
            $this->command?->info('Local role accounts already exist; no accounts or passwords changed.');
        }
    }
}
