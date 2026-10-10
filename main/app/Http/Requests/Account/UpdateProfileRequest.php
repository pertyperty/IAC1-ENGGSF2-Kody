<?php

namespace App\Http\Requests\Account;

use App\Support\AccountPasswords;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function authorize(): bool
    {
        return Gate::allows('update', $this->user());
    }

    public function rules(): array
    {
        return [
            'profile_version' => ['required', 'integer', 'min:1'],
            'username' => ['required', 'string', 'min:6', 'max:30', Rule::unique('users', 'username')->ignore($this->user()->id)],
            'first_name' => ['required', 'string', 'max:50', 'regex:/\A\p{L}[\p{L}\p{M}]*(?:[ \x{0027}\x{2019}-]\p{L}[\p{L}\p{M}]*)*\z/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/\A\p{L}[\p{L}\p{M}]*(?:[ \x{0027}\x{2019}-]\p{L}[\p{L}\p{M}]*)*\z/u'],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'current_password' => ['nullable', 'string', 'max:1024'],
            'password' => ['nullable', ...array_filter(AccountPasswords::rules(), fn ($rule) => $rule !== 'required')],
            'account_role' => ['prohibited'], 'account_status' => ['prohibited'], 'role' => ['prohibited'],
            'permissions' => ['prohibited'], 'email_verified_at' => ['prohibited'], 'user_id' => ['prohibited'],
        ];
    }
}
