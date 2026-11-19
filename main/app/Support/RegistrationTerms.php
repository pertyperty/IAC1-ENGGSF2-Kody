<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class RegistrationTerms
{
    public const VERSION = '2026-11-18';

    public static function rules(): array
    {
        return [
            'terms_accepted' => ['required', 'accepted'],
            'terms_version' => ['required', 'string', Rule::in([self::VERSION])],
        ];
    }
}
