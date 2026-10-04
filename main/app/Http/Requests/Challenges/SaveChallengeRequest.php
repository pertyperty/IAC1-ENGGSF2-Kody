<?php

namespace App\Http\Requests\Challenges;

use App\Http\Requests\Content\NormalizesAccessSettings;
use App\Models\CodingChallenge;
use App\Services\Publishing\AccessSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveChallengeRequest extends FormRequest
{
    use NormalizesAccessSettings;

    protected function prepareForValidation(): void
    {
        $this->normalizeAccessSettings();
    }

    public function authorize(): bool
    {
        $challenge = $this->route('challenge');

        return $challenge instanceof CodingChallenge ? $this->user()?->can('update', $challenge) : $this->user()?->can('create', CodingChallenge::class);
    }

    public function rules(): array
    {
        $limits = config('challenges.authoring');

        return ['record_version' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:150', 'not_regex:/\x00/'],
            'description' => ['required', 'string', 'max:50000', 'not_regex:/\x00/'],
            'language' => ['required', Rule::in(array_keys(config('challenges.languages')))],
            'difficulty' => ['required', Rule::in(['Easy', 'Medium', 'Hard'])],
            'rules' => ['required', 'string', 'max:20000', 'not_regex:/\x00/'],
            'input_format' => ['required', 'string', 'max:5000', 'not_regex:/\x00/'],
            'output_format' => ['required', 'string', 'max:5000', 'not_regex:/\x00/'],
            'cpu_time_ms' => ['required', 'integer', 'between:'.$limits['min_cpu_time_ms'].','.$limits['max_cpu_time_ms']],
            'memory_kib' => ['required', 'integer', 'between:'.$limits['min_memory_kib'].','.$limits['max_memory_kib']],
            'test_cases' => ['required', 'array', 'list', 'min:1', 'max:'.$limits['max_test_cases']],
            'test_cases.*' => ['required', 'array:input,expected_output,hidden'],
            'test_cases.*.input' => ['present', 'nullable', 'string', 'max:'.$limits['max_case_characters'], 'not_regex:/\x00/'],
            'test_cases.*.expected_output' => ['present', 'nullable', 'string', 'max:'.$limits['max_case_characters'], 'not_regex:/\x00/'],
            'test_cases.*.hidden' => ['required', 'boolean']] + app(AccessSettings::class)->rules('challenge');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $outputs = [];
            foreach ($this->input('test_cases') as $index => $case) {
                // Laravel skips nonimplicit rules for whitespace-only strings;
                // test payloads must still satisfy limits and cannot contain NUL.
                foreach (['input', 'expected_output'] as $field) {
                    $value = $case[$field] ?? '';
                    if (str_contains($value, "\0") || mb_strlen($value) > config('challenges.authoring.max_case_characters')) {
                        $validator->errors()->add('test_cases.'.$index.'.'.$field, 'Test payloads must stay within the character limit and cannot contain NUL bytes.');
                    }
                }
                $key = hash('sha256', $case['input'] ?? '');
                $output = $case['expected_output'] ?? '';
                if (array_key_exists($key, $outputs) && $outputs[$key] !== $output) {
                    $validator->errors()->add('test_cases.'.$index.'.expected_output', 'The same input cannot require different outputs.');
                }
                $outputs[$key] = $output;
            }
        });
    }
}
