<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TowerRevision extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function level(): BelongsTo
    {
        return $this->belongsTo(TowerLevel::class, 'level_id');
    }

    protected function casts(): array
    {
        return ['stages' => 'array'];
    }
}
