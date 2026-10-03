<?php

namespace App\Services\Administration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditRecorder
{
    public function record(?int $actorId, ?int $subjectUserId, string $event, string $subjectType, string $subjectId, array $context = []): void
    {
        DB::table('audit_events')->insert(['id' => (string) Str::uuid(), 'actor_id' => $actorId,
            'subject_user_id' => $subjectUserId, 'event' => $event, 'subject_type' => $subjectType,
            'subject_id' => $subjectId, 'context' => json_encode($context, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
