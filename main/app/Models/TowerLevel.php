<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TowerLevel extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<TowerRevision, $this> */
    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(TowerRevision::class, 'current_revision_id');
    }
}
