<?php

namespace App\Http\Requests\Administration;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BrowseAccountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', User::class);
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:80', 'not_regex:/\x00/'], 'role' => ['nullable', Rule::enum(Role::class)],
            'status' => ['nullable', Rule::enum(AccountStatus::class)]];
    }
}
