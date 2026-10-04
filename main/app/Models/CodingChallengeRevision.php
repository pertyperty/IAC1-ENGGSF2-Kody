<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodingChallengeRevision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['price_kb' => 'integer', 'minimum_xp' => 'integer', 'prerequisite_modules' => 'array', 'number' => 'integer', 'cpu_time_ms' => 'integer', 'memory_kib' => 'integer', 'reviewed_at' => 'immutable_datetime'];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(CodingChallenge::class, 'challenge_id');
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(ChallengeTestCase::class, 'revision_id')->orderBy('position');
    }
}
