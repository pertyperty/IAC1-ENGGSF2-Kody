<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Guarded(['id'])]
#[Hidden(['token_hash', 'password_digest'])]
class AccountRecovery extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'last_requested_at' => 'immutable_datetime',
            'window_started_at' => 'immutable_datetime',
            'request_count' => 'integer',
        ];
    }
}
