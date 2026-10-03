<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CourseReviewed extends Notification
{
    public function __construct(private readonly int $courseId, private readonly int $revisionId, private readonly string $title, private readonly string $decision) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'course.reviewed';
    }

    public function toArray(object $notifiable): array
    {
        return ['course_id' => $this->courseId, 'revision_id' => $this->revisionId, 'title' => $this->title, 'decision' => $this->decision];
    }
}
