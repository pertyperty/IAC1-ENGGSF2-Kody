<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ArchiveAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('archive', $this->user());
    }

    public function rules(): array
    {
        return ['confirmed' => ['required', 'accepted'], 'current_password' => ['required', 'string', 'max:1024'],
            'profile_version' => ['required', 'integer', 'min:1'], 'user_id' => ['prohibited'], 'account_status' => ['prohibited']];
    }
}
