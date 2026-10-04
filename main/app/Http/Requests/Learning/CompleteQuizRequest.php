<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['answer' => ['required_without:answers', Rule::prohibitedIf($this->has('answers')), 'string', 'max:30', 'regex:/\A[a-z0-9_-]+\z/'],
            'answers' => ['required_without:answer', Rule::prohibitedIf($this->has('answer')), 'array', 'min:1', 'max:10'],
            'answers.*' => ['required', 'string', 'max:30', 'regex:/\A[a-z0-9_-]+\z/']];
    }
}
