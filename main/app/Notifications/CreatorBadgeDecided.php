<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CreatorBadgeDecided extends Notification
{
    public const TYPE = 'creator.badge';

    public function __construct(private readonly string $decision, private readonly string $role) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return self::TYPE;
    }

    public function toArray(object $notifiable): array
    {
        return match ($this->decision) {
            'grant' => [
                'title' => 'Qualifications badge active',
                'decision' => 'Your staff-reviewed qualifications badge is now visible on your public profile and published work.',
                'outcome' => 'granted',
                'role' => $this->role,
            ],
            'decline' => [
                'title' => 'Qualifications badge request declined',
                'decision' => 'Staff reviewed your request and did not issue a qualifications badge at this time.',
                'outcome' => 'declined',
                'role' => $this->role,
            ],
            default => [
                'title' => 'Qualifications badge removed',
                'decision' => 'Your qualifications badge is no longer active on your public profile.',
                'outcome' => 'revoked',
                'role' => $this->role,
            ],
        };
    }
}
