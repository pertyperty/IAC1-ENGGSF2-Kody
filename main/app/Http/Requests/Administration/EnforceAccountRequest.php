<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EnforceAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage', $this->route('account'));
    }

    public function rules(): array
    {
        return ['action' => ['required', Rule::in(['Suspended', 'Reinstated'])],
            'profile_version' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted'],
            'account_role' => ['prohibited'], 'account_status' => ['prohibited'], 'user_id' => ['prohibited']];
    }
}
