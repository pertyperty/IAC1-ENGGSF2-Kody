<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreatorVerification extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['user_id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'reviewed_at' => 'immutable_datetime', 'record_version' => 'integer'];
    }
}
