<?php

namespace App\Http\Requests\Content;

use App\Services\Publishing\AccessSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCourseRequest extends FormRequest
{
    use NormalizesAccessSettings;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:1'], 'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'], 'category' => ['required', 'string', 'max:50'],
            'difficulty' => ['required', Rule::in(['Beginner', 'Intermediate', 'Advanced'])],
            'estimated_duration' => ['required', 'integer', 'min:1', 'max:10000'],
            'sequential' => ['sometimes', 'boolean'],
            'module_ids' => ['present', 'array', 'list', 'max:100'], 'module_ids.*' => ['required', 'integer', 'min:1', 'distinct']] + app(AccessSettings::class)->rules('course');
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeAccessSettings();
        if (is_array($this->input('module_ids')) && array_is_list($this->input('module_ids'))) {
            $this->merge(['module_ids' => array_values(array_filter($this->input('module_ids'), fn ($id) => $id !== null && $id !== ''))]);
        }
    }
}
