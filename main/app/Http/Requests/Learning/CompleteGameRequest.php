<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['program' => ['required', 'array', 'list', 'min:1', 'max:12'],
            'program.*' => $this->routeIs('play.game') ? ['required', 'string', Rule::in(['up', 'down', 'left', 'right'])] : ['required', 'string', 'max:100'],
            'repeat' => ['sometimes', 'boolean'], 'conditional' => ['sometimes', 'boolean']];
    }
}
