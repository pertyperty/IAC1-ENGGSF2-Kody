<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ProfilePictureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->user());
    }

    public function rules(): array
    {
        return ['user_id' => ['prohibited'], 'account_role' => ['prohibited'], 'account_status' => ['prohibited'], 'profile_picture' => ['prohibited'], 'disk' => ['prohibited'], 'path' => ['prohibited'], 'profile_version' => ['required', 'integer', 'min:0'],
            'remove_picture' => ['sometimes', 'boolean'],
            'picture' => ['required_unless:remove_picture,1', 'prohibited_if:remove_picture,1', 'file', 'mimetypes:image/jpeg,image/png', 'max:2048']];
    }
}
