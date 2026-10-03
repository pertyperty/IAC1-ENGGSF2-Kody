<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewPublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:1'], 'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'review_notes' => ['nullable', 'required_if:decision,Rejected', 'string', 'max:255']];
    }
}
