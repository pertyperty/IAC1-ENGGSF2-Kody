<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LearningCourse extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['record_version' => 'integer'];
    }

    public function latestRevision(): HasOne
    {
        return $this->hasOne(CourseRevision::class, 'course_id')->latestOfMany();
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(CourseRevision::class, 'published_revision_id');
    }
}
