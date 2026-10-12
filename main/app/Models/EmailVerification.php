<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Guarded(['id'])]
#[Hidden(['token_hash', 'otp_hash'])]
class EmailVerification extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'last_requested_at' => 'immutable_datetime',
            'request_count' => 'integer',
            'otp_expires_at' => 'immutable_datetime', 'otp_attempts' => 'integer',
        ];
    }
}
