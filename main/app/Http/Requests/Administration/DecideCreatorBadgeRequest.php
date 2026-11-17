<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DecideCreatorBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('verifyCreator', $this->route('creator'));
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:0'],
            'decision' => ['required', 'in:grant,revoke'], 'current_password' => ['required', 'string', 'max:1024'],
            'qualifications_reviewed' => ['sometimes', 'accepted'], 'confirmed' => ['required', 'accepted'],
            'application_id' => ['nullable', 'integer', 'min:1'], 'application_version' => ['nullable', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:5', 'max:500', 'not_regex:/\x00/']];
    }
}
