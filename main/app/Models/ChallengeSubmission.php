<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeSubmission extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected $hidden = ['source_code', 'request_digest', 'confirmation_id', 'lease_id'];

    protected function casts(): array
    {
        return ['source_code' => 'encrypted', 'attempt' => 'integer', 'passed_cases' => 'integer',
            'total_cases' => 'integer', 'submitted_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime', 'lease_expires_at' => 'immutable_datetime'];
    }
}
