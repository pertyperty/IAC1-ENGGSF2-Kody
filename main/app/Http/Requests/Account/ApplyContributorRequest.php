<?php

namespace App\Http\Requests\Account;

use App\Models\ContributorApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ApplyContributorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', ContributorApplication::class);
    }

    public function rules(): array
    {
        return ['previous_application_id' => ['required', 'integer', 'min:0'],
            'request_message' => ['required', 'string', 'max:500', 'not_regex:/\x00/'],
            'portfolio_link' => ['nullable', 'string', 'url:http,https', 'max:255'],
            'credential_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('account.credentials.max_kilobytes')],
            'confirmed' => ['required', 'accepted'], 'user_id' => ['prohibited'], 'account_role' => ['prohibited'],
            'approval_status' => ['prohibited'], 'credential_path' => ['prohibited'],
            'account_age_days' => ['prohibited'], 'completed_modules_count' => ['prohibited'], 'completed_challenges_count' => ['prohibited']];
    }
}
