<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrowseModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:80'],
            'template' => ['nullable', 'string', Rule::in(array_keys($this->templates()))],
        ];
    }

    public function templates(): array
    {
        return ['command-garden' => 'Logic Garden', 'choice-quiz' => 'Quick quizzes']
            + collect(config('arcade'))->map(fn (array $game) => $game['title'])->all();
    }
}
