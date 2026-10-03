<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRevisionModule extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(LearningModule::class, 'module_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ModuleRevision::class, 'module_revision_id');
    }
}
