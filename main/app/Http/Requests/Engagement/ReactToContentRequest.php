<?php

namespace App\Http\Requests\Engagement;

use App\Models\LearningCourse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReactToContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewLearning', LearningCourse::class);
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:0'], 'reaction' => ['present', 'nullable', Rule::in(['Like', 'Helpful', 'Favorite'])],
            'user_id' => ['prohibited'], 'content_id' => ['prohibited'], 'count' => ['prohibited'], 'opened' => ['prohibited']];
    }
}
