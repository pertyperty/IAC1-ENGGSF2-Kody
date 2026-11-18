<?php

namespace App\Actions\Account;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Account\EmailVerificationService;
use App\Support\RegistrationTerms;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegisterAccount
{
    public function __construct(private EmailVerificationService $verification) {}

    public function handle(array $data): User
    {
        Validator::make($data, RegistrationTerms::rules())->validate();
        try {
            return DB::transaction(function () use ($data): User {
                $user = new User([
                    'username' => $data['username'], 'name' => $data['username'],
                    'first_name' => null, 'last_name' => null,
                    'email' => $data['email'], 'password' => $data['password'],
                ]);
                $user->forceFill(['account_role' => Role::Learner, 'account_status' => AccountStatus::Unverified,
                    'terms_version' => RegistrationTerms::VERSION, 'terms_accepted_at' => now()])->save();
                $this->verification->request($user);

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'The email or username is already registered.']);
        }
    }
}
