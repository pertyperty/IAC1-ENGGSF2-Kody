<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('delete', $this->user());
    }

    public function rules(): array
    {
        return ['profile_version' => ['required', 'integer', 'min:1'], 'current_password' => ['required', 'string', 'max:1024'],
            'confirmation_phrase' => ['required', 'string', Rule::in(['DELETE MY ACCOUNT'])], 'confirmed' => ['required', 'accepted'],
            'user_id' => ['prohibited'], 'account_status' => ['prohibited']];
    }
}
