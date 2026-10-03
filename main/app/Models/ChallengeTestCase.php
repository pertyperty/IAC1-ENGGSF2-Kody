<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeTestCase extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $hidden = ['input', 'expected_output'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'hidden' => 'boolean'];
    }
}
