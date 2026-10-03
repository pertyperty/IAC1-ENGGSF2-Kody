<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePresetRevision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['instance' => 'array', 'number' => 'integer'];
    }

    public function preset(): BelongsTo
    {
        return $this->belongsTo(GamePreset::class, 'preset_id');
    }
}
