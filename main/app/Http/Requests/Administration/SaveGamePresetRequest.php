<?php

namespace App\Http\Requests\Administration;

use App\Models\GamePreset;
use App\Services\Games\PresetConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SaveGamePresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('preset') === null ? Gate::allows('create', GamePreset::class) : Gate::allows('update', $this->route('preset'));
    }

    public function rules(): array
    {
        return app(PresetConfiguration::class)->rules();
    }
}
