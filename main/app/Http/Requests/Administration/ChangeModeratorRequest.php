<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChangeModeratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('changeModeratorRole', $this->route('account'));
    }

    public function rules(): array
    {
        return ['action' => ['required', Rule::in(['Appointed', 'Removed'])], 'profile_version' => ['required', 'integer', 'min:1'],
            'current_password' => ['required', 'string', 'max:1024'], 'confirmed' => ['required', 'accepted'],
            'account_role' => ['prohibited'], 'account_status' => ['prohibited'], 'moderator_prior_role' => ['prohibited'],
            'resulting_role' => ['prohibited'], 'user_id' => ['prohibited']];
    }
}
