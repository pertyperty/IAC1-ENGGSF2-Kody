<?php

namespace App\Http\Requests\Transactions;

use App\Models\LearningCourse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseKodeBitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewLearning', LearningCourse::class) ?? false;
    }

    public function rules(): array
    {
        return ['package' => ['required', Rule::in(array_keys(config('economy.packages')))], 'confirmation_id' => ['required', 'uuid'],
            'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']];
    }
}
