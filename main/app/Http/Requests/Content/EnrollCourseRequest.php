<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class EnrollCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['revision_id' => ['required', 'integer', 'min:1']];
    }
}
