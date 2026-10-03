<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ModuleReviewed extends Notification
{
    public function __construct(private readonly int $moduleId, private readonly int $revisionId, private readonly string $title, private readonly string $decision) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'module.reviewed';
    }

    public function toArray(object $notifiable): array
    {
        return ['module_id' => $this->moduleId, 'revision_id' => $this->revisionId, 'title' => $this->title, 'decision' => $this->decision];
    }
}
