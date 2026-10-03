<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ChallengeReviewed extends Notification
{
    public function __construct(private readonly int $challengeId, private readonly int $revisionId, private readonly string $title, private readonly string $decision) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'challenge.reviewed';
    }

    public function toArray(object $notifiable): array
    {
        return ['challenge_id' => $this->challengeId, 'revision_id' => $this->revisionId, 'title' => $this->title, 'decision' => $this->decision];
    }
}
