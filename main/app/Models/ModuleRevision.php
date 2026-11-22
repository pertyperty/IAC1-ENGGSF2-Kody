<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $price_kb
 * @property int $minimum_xp
 * @property list<int> $prerequisite_modules
 * @property string $creator_settlement
 */
class ModuleRevision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['attachments' => 'array', 'price_kb' => 'integer', 'minimum_xp' => 'integer', 'prerequisite_modules' => 'array', 'assessment' => 'array', 'number' => 'integer', 'reviewed_at' => 'immutable_datetime'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(LearningModule::class, 'module_id');
    }

    public function gamePresetRevision(): BelongsTo
    {
        return $this->belongsTo(GamePresetRevision::class);
    }
}
