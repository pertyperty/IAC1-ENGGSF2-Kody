<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class CompleteTowerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['program' => ['sometimes', 'array', 'list', 'min:1', 'max:12'], 'program.*' => ['required', 'string', 'max:100'],
            'repeat' => ['sometimes', 'boolean'], 'conditional' => ['sometimes', 'boolean'],
            'answer' => ['sometimes', 'string', 'max:30'], 'answers' => ['sometimes', 'array', 'min:1', 'max:10'], 'answers.*' => ['required', 'string', 'max:30']];
    }
}
