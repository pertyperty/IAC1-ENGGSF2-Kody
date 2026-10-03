<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountFileErasure extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['path', 'path_digest'];

    protected function casts(): array
    {
        return ['path' => 'encrypted', 'attempts' => 'integer', 'last_queued_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime'];
    }
}
