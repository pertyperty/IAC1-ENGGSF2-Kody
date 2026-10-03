<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LearningModule extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['record_version' => 'integer'];
    }

    public function latestRevision(): HasOne
    {
        return $this->hasOne(ModuleRevision::class, 'module_id')->latestOfMany();
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(ModuleRevision::class, 'published_revision_id');
    }
}
