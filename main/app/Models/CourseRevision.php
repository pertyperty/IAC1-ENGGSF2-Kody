<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $price_kb
 * @property int $minimum_xp
 * @property list<int> $prerequisite_modules
 * @property string $creator_settlement
 */
class CourseRevision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['price_kb' => 'integer', 'minimum_xp' => 'integer', 'prerequisite_modules' => 'array', 'number' => 'integer', 'estimated_duration' => 'integer', 'reviewed_at' => 'immutable_datetime', 'sequential' => 'boolean'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    /** @return HasMany<CourseRevisionModule, $this> */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseRevisionModule::class, 'course_revision_id')->orderBy('position');
    }
}
