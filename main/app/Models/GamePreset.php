<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class GamePreset extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['record_version' => 'integer'];
    }

    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(GamePresetRevision::class, 'current_revision_id');
    }

    public function lessonRevisions(): HasManyThrough
    {
        return $this->hasManyThrough(ModuleRevision::class, GamePresetRevision::class, 'preset_id', 'game_preset_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(GamePresetRevision::class, 'preset_id');
    }
}
