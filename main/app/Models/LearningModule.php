<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasStaffWithdrawal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LearningModule extends Model
{
    use HasCreator;
    use HasStaffWithdrawal;

    protected $guarded = ['id', 'staff_withdrawn_at'];

    protected function casts(): array
    {
        return ['record_version' => 'integer', 'staff_withdrawn_at' => 'immutable_datetime'];
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
