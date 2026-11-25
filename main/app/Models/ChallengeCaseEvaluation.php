<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeCaseEvaluation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $hidden = ['provider_token', 'result_details'];

    protected function casts(): array
    {
        return ['provider_token' => 'encrypted', 'provider_status' => 'integer', 'result_details' => 'encrypted:array'];
    }
}
