<?php

namespace App\Services\Notifications;

use App\Enums\AccountStatus;
use App\Models\CodingChallengeRevision;
use App\Models\CourseRevision;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Notifications\ChallengeReviewed;
use App\Notifications\CourseReviewed;
use App\Notifications\CreatorBadgeDecided;
use App\Notifications\ModuleReviewed;

class InAppNotifications
{
    public function creatorBadgeDecided(User $recipient, string $decision, string $role): void
    {
        if ($recipient->account_status === AccountStatus::Active) {
            $recipient->notify(new CreatorBadgeDecided($decision, $role));
        }
    }
    public function challengeReviewed(User $recipient, CodingChallengeRevision $revision): void
    {
        if ($recipient->account_status === AccountStatus::Active) {
            $recipient->notify(new ChallengeReviewed($revision->challenge_id, $revision->id, $revision->title, $revision->review_status));
        }
    }

    public function courseReviewed(User $recipient, CourseRevision $revision): void
    {
        if ($recipient->account_status === AccountStatus::Active) {
            $recipient->notify(new CourseReviewed($revision->course_id, $revision->id, $revision->title, $revision->review_status));
        }
    }

    public function moduleReviewed(User $recipient, ModuleRevision $revision): void
    {
        if ($recipient->account_status === AccountStatus::Active) {
            // The database channel shares the caller's transaction; no remote delivery blocks publication.
            $recipient->notify(new ModuleReviewed($revision->module_id, $revision->id, $revision->title, $revision->review_status));
        }
    }
}
