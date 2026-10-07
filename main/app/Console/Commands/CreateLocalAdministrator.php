<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Support\AccountPasswords;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateLocalAdministrator extends Command
{
    protected $signature = 'kody:admin-create-local {email} {--username=kody_admin} {--first-name=Kody} {--last-name=Administrator} {--generate : Save a generated password in a new private local file}';

    protected $description = 'Create an audited local Administrator without changing existing accounts or sending email';

    public function handle(AuditRecorder $audit): int
    {
        $connection = DB::connection();
        if (! app()->environment(['local', 'testing']) || $connection->getDriverName() !== 'pgsql'
            || ! in_array($connection->getConfig('host'), ['127.0.0.1', 'localhost', '::1'], true)) {
            $this->error('This command requires a local/testing environment and loopback PostgreSQL.');

            return self::FAILURE;
        }

        $credentialPath = storage_path('app/private/local-admin-credentials.txt');
        $createdFile = false;

        try {
            $data = Validator::make([
                'email' => mb_strtolower(trim($this->argument('email'))),
                'username' => $this->option('username'),
                'first_name' => $this->option('first-name'),
                'last_name' => $this->option('last-name'),
            ], [
                'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')],
                'username' => ['required', 'string', 'min:6', 'max:30', Rule::unique('users', 'username')],
                'first_name' => ['required', 'string', 'max:50', 'regex:/\A\p{L}+\z/u'],
                'last_name' => ['required', 'string', 'max:50', 'regex:/\A\p{L}+\z/u'],
            ])->validate();

            $password = $this->option('generate') ? 'Aa1!'.Str::random(26) : $this->secret('Password (12–32 characters, uppercase, lowercase, number and symbol)');
            $confirmation = $this->option('generate') ? $password : $this->secret('Confirm password');
            Validator::make(['password' => $password, 'password_confirmation' => $confirmation],
                ['password' => AccountPasswords::rules()])->validate();

            DB::transaction(function () use ($data, $password, $credentialPath, &$createdFile, $audit): void {
                $user = new User($data + ['name' => $data['first_name'].' '.$data['last_name'], 'password' => $password]);
                $user->forceFill(['account_role' => Role::Administrator, 'account_status' => AccountStatus::Active,
                    'email_verified_at' => now()])->save();
                $audit->record(null, $user->id, 'local_administrator_created', 'User', (string) $user->id,
                    ['source' => 'local_console', 'environment' => app()->environment()]);

                if ($this->option('generate')) {
                    // Exclusive creation prevents replacing another account's credential file.
                    $previousMask = umask(0077);
                    try {
                        if (! is_dir(dirname($credentialPath)) && ! mkdir(dirname($credentialPath), 0700, true)) {
                            throw new \RuntimeException('Private directory unavailable.');
                        }
                        $file = @fopen($credentialPath, 'x');
                        if ($file === false) {
                            throw new \RuntimeException('Credential file exists or is unavailable.');
                        }
                        $createdFile = true;
                        try {
                            $content = "Local Kody Administrator\nEmail: {$data['email']}\nUsername: {$data['username']}\nPassword: {$password}\n\nChange the password in My account after signing in, then remove this file.\n";
                            if (! chmod($credentialPath, 0600) || fwrite($file, $content) !== strlen($content) || ! fflush($file)) {
                                throw new \RuntimeException('Private credential write failed.');
                            }
                        } finally {
                            fclose($file);
                        }
                    } finally {
                        umask($previousMask);
                    }
                }
            });
        } catch (Throwable $exception) {
            if ($createdFile) {
                @unlink($credentialPath);
            }
            if ($exception instanceof ValidationException) {
                foreach ($exception->validator->errors()->all() as $message) {
                    $this->error($message);
                }
            } elseif ($exception instanceof UniqueConstraintViolationException) {
                $this->error('The email or username is already registered; no account was changed.');
            } else {
                // Never print database exceptions or credential material.
                $this->error('Account creation failed. Check local database/storage availability and existing credential files.');
            }

            return self::FAILURE;
        }

        $this->info('Local Administrator created. Sign in through the normal Kody login.');
        if ($createdFile) {
            $this->line('Credentials saved privately: '.$credentialPath);
        }

        return self::SUCCESS;
    }
}
