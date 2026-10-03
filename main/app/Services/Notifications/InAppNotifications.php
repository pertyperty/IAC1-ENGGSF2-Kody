<?php

namespace App\Services\Notifications;

use App\Enums\AccountStatus;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Notifications\ModuleReviewed;

class InAppNotifications
{
    public function moduleReviewed(User $recipient, ModuleRevision $revision): void
    {
        if ($recipient->account_status === AccountStatus::Active) {
            // The database channel shares the caller's transaction; no remote delivery blocks publication.
            $recipient->notify(new ModuleReviewed($revision->module_id, $revision->id, $revision->title, $revision->review_status));
        }
    }
}
