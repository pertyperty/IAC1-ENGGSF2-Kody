<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => trim($this->input('email'))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() === null;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:100'],
            // Authenticate existing passwords without imposing new registration rules.
            'password' => ['required', 'string', 'max:1024'],
        ];
    }
}
