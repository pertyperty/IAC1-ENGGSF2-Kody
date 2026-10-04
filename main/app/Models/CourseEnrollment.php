<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnrollment extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['enrolled_at' => 'immutable_datetime', 'sequential' => 'boolean'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(CourseRevision::class, 'course_revision_id');
    }
}
