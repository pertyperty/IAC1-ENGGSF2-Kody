<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributorApplication extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['credential_disk', 'credential_path'];

    protected function casts(): array
    {
        return ['record_version' => 'integer', 'reviewed_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
