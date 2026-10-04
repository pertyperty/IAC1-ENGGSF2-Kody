<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class MarkLessonReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['confirmed' => ['required', 'accepted']];
    }
}
