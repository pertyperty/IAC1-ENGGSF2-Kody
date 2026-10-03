<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class DeleteOwnedContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted'],
            'created_by' => ['prohibited'], 'force' => ['prohibited']];
    }
}
