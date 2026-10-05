<?php

return [
    'region' => 'ap-southeast-1',
    'monthly_budget_minor' => 1200000,
    'budget_enforced' => (bool) env('KODY_BUDGET_ENFORCED', false),
    'provider_admission_paused' => (bool) env('KODY_PROVIDER_ADMISSION_PAUSED', false),
    'operations_owner' => env('KODY_OPERATIONS_OWNER'),
    'backup_responder' => env('KODY_BACKUP_RESPONDER'),
    'staging_verified' => (bool) env('KODY_STAGING_VERIFIED', false),
    'backup_retention_days' => 35,
    'noncurrent_object_days' => 30,
    'log_retention_days' => 30,
    'rpo_minutes' => 15,
    'rto_minutes' => 240,
];
