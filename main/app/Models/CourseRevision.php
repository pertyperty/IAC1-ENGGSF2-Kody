<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseRevision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['number' => 'integer', 'estimated_duration' => 'integer', 'reviewed_at' => 'immutable_datetime', 'sequential' => 'boolean'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseRevisionModule::class, 'course_revision_id')->orderBy('position');
    }
}
