<?php

namespace App\Http\Requests\Challenges;

use App\Models\ChallengeSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SubmitCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', ChallengeSubmission::class);
    }

    public function rules(): array
    {
        return ['revision_id' => ['required', 'integer', 'min:1'], 'confirmation_id' => ['required', 'uuid'],
            'language' => ['required', Rule::in(array_keys(config('challenges.languages')))],
            'confirmed' => ['required', 'accepted'], 'source_code' => ['bail', 'required', 'string', function ($attribute, $value, $fail): void {
                if (! mb_check_encoding($value, 'UTF-8') || strlen($value) > config('judge0.source_bytes') || str_contains($value, "\0") || trim($value) === '') {
                    $fail('Supply code without null bytes within the 64 KiB source limit.');
                }
            }], 'weekly_event_id' => ['prohibited']];
    }
}
