<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReviewContributorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('review', $this->route('application'));
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:1'], 'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'moderator_feedback' => ['nullable', 'string', 'max:500', 'not_regex:/\x00/'], 'confirmed' => ['required', 'accepted'],
            'user_id' => ['prohibited'], 'account_role' => ['prohibited']];
    }
}
