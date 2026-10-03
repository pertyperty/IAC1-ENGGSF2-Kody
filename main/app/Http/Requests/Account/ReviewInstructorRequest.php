<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('application')) ?? false;
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:1'], 'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'verification_notes' => ['required_if:decision,Rejected', 'nullable', 'string', 'max:255'],
            'credibility_reviewed' => ['required', 'accepted']];
    }
}
