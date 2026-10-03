<?php

namespace App\Http\Requests\Administration;

use App\Services\Administration\ContentModeration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ModerateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $class = app(ContentModeration::class)->model($this->route('kind'));

        return Gate::allows('moderate', $class::findOrFail($this->route('content')));
    }

    public function rules(): array
    {
        return ['record_version' => ['required', 'integer', 'min:1'], 'action' => ['required', Rule::in(['Withdrawn', 'Restored'])],
            'confirmed' => ['required', 'accepted'], 'flagged' => $this->input('action') === 'Withdrawn' ? ['required', 'accepted'] : ['sometimes', 'accepted'],
            'status' => ['prohibited'], 'staff_withdrawn_at' => ['prohibited'], 'created_by' => ['prohibited'],
            'published_revision_id' => ['prohibited'], 'content_id' => ['prohibited']];
    }
}
