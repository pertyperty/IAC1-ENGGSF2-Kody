<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class GoogleLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->user()) ?? false;
    }

    public function rules(): array
    {
        return ['current_password' => ['required', 'string', 'max:1024'],
            'profile_version' => ['required', 'integer', 'min:1'], 'identity_version' => ['required', 'integer', 'min:0']];
    }
}
