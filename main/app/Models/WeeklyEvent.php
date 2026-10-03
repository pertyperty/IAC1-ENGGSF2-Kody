<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyEvent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'record_version' => 'integer'];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(CodingChallengeRevision::class, 'revision_id');
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(CodingChallenge::class, 'challenge_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['Scheduled', 'Active'])->where('starts_at', '<=', now())->where('ends_at', '>', now())
            ->whereHas('challenge', fn ($builder) => $builder->where('status', 'Published'))
            ->whereHas('revision', fn ($builder) => $builder->where('review_status', 'Approved'));
    }
}
