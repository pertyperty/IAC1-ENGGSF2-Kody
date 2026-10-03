<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleRevision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['assessment' => 'array', 'number' => 'integer', 'reviewed_at' => 'immutable_datetime'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(LearningModule::class, 'module_id');
    }
}
