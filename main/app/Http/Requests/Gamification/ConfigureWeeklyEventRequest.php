<?php

namespace App\Http\Requests\Gamification;

use App\Models\WeeklyEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ConfigureWeeklyEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage', WeeklyEvent::class);
    }

    public function rules(): array
    {
        return ['week' => ['required', 'date_format:Y-m-d'], 'challenge_id' => ['required', 'integer', 'min:1'],
            'revision_id' => ['required', 'integer', 'min:1'], 'record_version' => ['required', 'integer', 'min:0'],
            'rules' => ['required', 'string', 'max:20000', 'not_regex:/\x00/'], 'confirmed' => ['required', 'accepted']];
    }
}
