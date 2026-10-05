<?php

namespace App\Services\Challenges;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChallengeMetadata
{
    public function rules(): array
    {
        return ['category' => ['required', Rule::in(array_keys(config('challenges.categories')))],
            'tags' => ['present', 'array', 'list', 'max:5'],
            'tags.*' => ['required', 'string', 'distinct:strict', Rule::in(array_keys(config('challenges.tags')))]];
    }

    public function attributes(array $data): array
    {
        // Direct application-service callers obey the same bounded vocabulary as HTTP authoring.
        return Validator::make(array_intersect_key($data, ['category' => true, 'tags' => true]) + ['category' => 'foundations', 'tags' => []], $this->rules())->validate();
    }
}
