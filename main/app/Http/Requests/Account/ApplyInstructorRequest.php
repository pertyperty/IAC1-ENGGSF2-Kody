<?php

namespace App\Http\Requests\Account;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class ApplyInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->account_role, [Role::Learner, Role::Contributor], true);
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:0'],
            'institution_name' => ['required', 'string', 'max:100', 'not_regex:/\x00/'],
            'specialization' => ['required', 'string', 'max:100', 'not_regex:/\x00/'],
            'credential_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('account.credentials.max_kilobytes')],
            'confirmed' => ['required', 'accepted'], 'user_id' => ['prohibited'], 'account_role' => ['prohibited'],
            'verification_status' => ['prohibited'], 'credential_path' => ['prohibited']];
    }
}
