<?php

namespace App\Models;

use App\Models\Concerns\HasStaffWithdrawal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CodingChallenge extends Model
{
    use HasStaffWithdrawal;

    protected $guarded = ['id', 'staff_withdrawn_at'];

    protected function casts(): array
    {
        return ['record_version' => 'integer', 'staff_withdrawn_at' => 'immutable_datetime'];
    }

    public function latestRevision(): HasOne
    {
        return $this->hasOne(CodingChallengeRevision::class, 'challenge_id')->latestOfMany();
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(CodingChallengeRevision::class, 'published_revision_id');
    }
}
