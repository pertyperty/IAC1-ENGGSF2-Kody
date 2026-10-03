<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountPasswords
{
    public static function rules(): array
    {
        return ['required', 'string', 'max:32', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()];
    }

    public static function matches(#[\SensitiveParameter] string $password, string $hash): bool
    {
        $driver = match (password_get_info($hash)['algoName']) {
            'bcrypt' => 'bcrypt', 'argon2i' => 'argon', 'argon2id' => 'argon2id', default => null,
        };

        return $driver !== null && Hash::driver($driver)->check($password, $hash);
    }
}
