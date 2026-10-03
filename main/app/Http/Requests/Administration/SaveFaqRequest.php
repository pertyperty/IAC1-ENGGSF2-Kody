<?php

namespace App\Http\Requests\Administration;

use App\Models\FaqEntry;
use App\Services\Administration\FaqManagement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SaveFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('entry') === null ? Gate::allows('create', FaqEntry::class) : Gate::allows('update', $this->route('entry'));
    }

    public function rules(): array
    {
        return app(FaqManagement::class)->rules();
    }
}
