<?php

namespace App\Http\Requests\Account;

use App\Services\Account\CreatorProfiles;
use Illuminate\Foundation\Http\FormRequest;

class SaveCreatorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app(CreatorProfiles::class)->visible($this->user());
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:0'],
            'headline' => ['nullable', 'string', 'max:100', 'not_regex:/\x00/'],
            'bio' => ['nullable', 'string', 'max:1000', 'not_regex:/\x00/'],
            'accomplishments' => ['nullable', 'string', 'max:1000', 'not_regex:/\x00/'],
            'confirmed' => ['required', 'accepted'], 'user_id' => ['prohibited'], 'verified' => ['prohibited'], 'account_role' => ['prohibited']];
    }
}
