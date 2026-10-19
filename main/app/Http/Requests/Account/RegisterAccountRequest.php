<?php

namespace App\Http\Requests\Account;

use App\Support\AccountPasswords;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterAccountRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() === null;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:6', 'max:30', Rule::unique('users', 'username')],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')],
            'first_name' => ['prohibited'], 'last_name' => ['prohibited'], 'name' => ['prohibited'],
            'password' => AccountPasswords::rules(),
            'account_type' => ['sometimes', Rule::in(['learner'])],
            'account_role' => ['prohibited'],
            'account_status' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'role' => ['prohibited'],
            'permissions' => ['prohibited'],
            'institution_name' => ['prohibited'], 'specialization' => ['prohibited'], 'credential_document' => ['prohibited'],
        ];
    }
}
