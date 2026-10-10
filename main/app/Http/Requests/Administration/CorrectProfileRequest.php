<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CorrectProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('correctProfile', $this->route('account'));
    }

    public function rules(): array
    {
        return [
            'profile_version' => ['required', 'integer', 'min:1'],
            'username' => ['required', 'string', 'min:6', 'max:30', Rule::unique('users', 'username')->ignore($this->route('account')->id)],
            'first_name' => ['required', 'string', 'max:50', 'regex:/\A\p{L}[\p{L}\p{M}]*(?:[ \x{0027}\x{2019}-]\p{L}[\p{L}\p{M}]*)*\z/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/\A\p{L}[\p{L}\p{M}]*(?:[ \x{0027}\x{2019}-]\p{L}[\p{L}\p{M}]*)*\z/u'],
            'current_password' => ['required', 'string', 'max:1024'],
            'confirmed' => ['required', 'accepted'], 'support_requested' => ['required', 'accepted'],
            'email' => ['prohibited'], 'password' => ['prohibited'], 'name' => ['prohibited'],
            'account_role' => ['prohibited'], 'account_status' => ['prohibited'], 'role' => ['prohibited'],
            'permissions' => ['prohibited'], 'email_verified_at' => ['prohibited'], 'user_id' => ['prohibited'],
            'moderator_prior_role' => ['prohibited'],
        ];
    }
}
