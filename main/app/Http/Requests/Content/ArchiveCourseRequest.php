<?php

namespace App\Http\Requests\Content;

use App\Models\LearningCourse;
use Illuminate\Foundation\Http\FormRequest;

class ArchiveCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof LearningCourse && $this->user()?->can('archive', $course);
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']];
    }
}
