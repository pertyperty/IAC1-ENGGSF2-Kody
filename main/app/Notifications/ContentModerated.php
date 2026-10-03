<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ContentModerated extends Notification
{
    public function __construct(private readonly string $actionId, private readonly string $kind, private readonly int $contentId, private readonly string $action) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'content.moderated';
    }

    public function toArray(object $notifiable): array
    {
        return ['action_id' => $this->actionId, 'kind' => $this->kind, 'content_id' => $this->contentId,
            'title' => ucfirst($this->kind).' #'.$this->contentId.' staff moderation', 'decision' => $this->action];
    }
}
