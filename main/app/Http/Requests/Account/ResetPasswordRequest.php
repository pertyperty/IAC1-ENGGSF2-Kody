<?php

namespace App\Http\Requests\Account;

use App\Support\AccountPasswords;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    public function rules(): array
    {
        return ['password' => AccountPasswords::rules()];
    }
}
