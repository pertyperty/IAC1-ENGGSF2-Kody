<?php

namespace App\Http\Requests\Administration;

use App\Models\TowerLevel;
use Illuminate\Foundation\Http\FormRequest;

class SaveTowerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', TowerLevel::class) ?? false;
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:1'], 'title' => ['required', 'string', 'max:100'],
            'concept' => ['required', 'string', 'max:100'], 'description' => ['required', 'string', 'max:1000'],
            'source_notes' => ['required', 'string', 'max:5000'], 'stages_json' => ['required', 'string', 'json', 'max:30000']];
    }
}
