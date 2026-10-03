<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class CompleteQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['answer' => ['required', 'string', 'max:30', 'regex:/\A[a-z0-9_-]+\z/']];
    }
}
