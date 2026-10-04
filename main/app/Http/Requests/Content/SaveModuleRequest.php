<?php

namespace App\Http\Requests\Content;

use App\Services\Games\QuizAuthoring;
use App\Services\Publishing\AccessSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveModuleRequest extends FormRequest
{
    use NormalizesAccessSettings;

    protected function prepareForValidation(): void
    {
        $this->normalizeAccessSettings();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'record_version' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'content' => ['required', 'string', 'max:50000'],
            'type' => ['required', Rule::in(['Article', 'Interactive', 'Video'])],
            'video_url' => ['exclude_unless:type,Video', 'required', 'url:https', 'max:2000'],
            'assessment_kind' => ['required', Rule::in($this->input('type') === 'Interactive' ? ['game', 'quiz', 'preset'] : ['none', 'game', 'quiz', 'preset'])],
            'managed_preset' => ['exclude_unless:assessment_kind,preset', 'required', 'integer', 'min:1'],
            'preset_title' => ['exclude_unless:assessment_kind,preset', 'required', 'string', 'max:100'],
            'game_preset' => ['exclude_unless:assessment_kind,game', 'required', Rule::in(array_keys(config('learning.instances') + config('arcade')))],
            'game_scenario' => ['exclude_unless:assessment_kind,game', 'nullable', 'string', 'max:4000'],
            'game_layout' => ['exclude_unless:assessment_kind,game', 'nullable', 'string', 'max:4000'],
            'game_title' => ['exclude_unless:assessment_kind,game', 'required', 'string', 'max:100'],
            'game_instructions' => ['exclude_unless:assessment_kind,game', 'required', 'string', 'max:1000'],
            'game_hint' => ['exclude_unless:assessment_kind,game', 'required', 'string', 'max:1000'],
            'game_learning_idea' => ['exclude_unless:assessment_kind,game', 'required', 'string', 'max:1000'],
            'quiz_title' => ['exclude_unless:assessment_kind,quiz', 'required', 'string', 'max:100'],
            'quiz_question' => ['exclude_with:quiz_questions', 'exclude_unless:assessment_kind,quiz', 'required', 'string', 'max:500'],
            'quiz_a' => ['exclude_with:quiz_questions', 'exclude_unless:assessment_kind,quiz', 'required', 'string', 'max:300', 'different:quiz_b'],
            'quiz_b' => ['exclude_with:quiz_questions', 'exclude_unless:assessment_kind,quiz', 'required', 'string', 'max:300'],
            'quiz_answer' => ['exclude_with:quiz_questions', 'exclude_unless:assessment_kind,quiz', 'required', Rule::in(['a', 'b'])],
            'quiz_explanation' => ['exclude_with:quiz_questions', 'exclude_unless:assessment_kind,quiz', 'required', 'string', 'max:1000'],
        ] + ($this->input('assessment_kind') === 'quiz' ? app(QuizAuthoring::class)->rules() : ['quiz_questions' => ['exclude']]) + app(AccessSettings::class)->rules('module');
    }
}
