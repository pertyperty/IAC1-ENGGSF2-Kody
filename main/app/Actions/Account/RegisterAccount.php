<?php

namespace App\Actions\Account;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\Account\EmailVerificationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use Throwable;

class RegisterAccount
{
    public function __construct(private EmailVerificationService $verification) {}

    public function handle(array $data, ?UploadedFile $credential = null): User
    {
        $disk = config('account.credentials.disk');
        $path = null;

        try {
            if ($data['account_type'] === 'instructor') {
                if ($disk === 'public' || config('filesystems.disks.'.$disk.'.visibility') === 'public') {
                    throw new LogicException('Instructor credentials require private storage.');
                }
                if ($credential === null) {
                    throw ValidationException::withMessages(['credential_document' => 'Teaching credentials are required.']);
                }
                $path = Storage::disk($disk)->putFile('instructor-credentials', $credential, ['visibility' => 'private']);
                if ($path === false) {
                    throw new RuntimeException('Credentials could not be stored.');
                }
            }

            return DB::transaction(function () use ($data, $disk, $path): User {
                $user = new User([
                    'username' => $data['username'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'name' => $data['first_name'].' '.$data['last_name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                ]);
                $user->forceFill(['account_role' => Role::Learner, 'account_status' => AccountStatus::Unverified])->save();

                if ($path !== null) {
                    InstructorApplication::create([
                        'user_id' => $user->id,
                        'institution_name' => $data['institution_name'],
                        'specialization' => $data['specialization'],
                        'credential_disk' => $disk,
                        'credential_path' => $path,
                        'verification_status' => 'Pending',
                    ]);
                }

                $this->verification->request($user);

                return $user;
            });
        } catch (Throwable $exception) {
            if (is_string($path)) {
                try {
                    if (! Storage::disk($disk)->delete($path)) {
                        Log::warning('Registration credential cleanup failed.');
                    }
                } catch (Throwable) {
                    Log::warning('Registration credential cleanup failed.');
                }
            }

            if ($exception instanceof UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['email' => 'The email or username is already registered.']);
            }

            throw $exception;
        }
    }
}
