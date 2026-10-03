<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('entry'));
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']];
    }
}
