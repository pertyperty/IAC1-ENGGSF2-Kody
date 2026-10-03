<?php

return [
    'verification' => [
        // A02 requires expiry but specifies no duration; this is an operational setting.
        'expires_minutes' => max(1, (int) env('ACCOUNT_VERIFICATION_EXPIRES_MINUTES', 60)),
        'mailer' => env('ACCOUNT_VERIFICATION_MAILER', 'smtp'),
    ],
    'credentials' => [
        'disk' => env('INSTRUCTOR_CREDENTIAL_DISK', 'local'),
        'max_kilobytes' => max(1, (int) env('INSTRUCTOR_CREDENTIAL_MAX_KILOBYTES', 5120)),
    ],
    'recovery' => [
        // A04 specifies expiry/rate limits but no numeric values: operational defaults.
        'expires_minutes' => max(1, (int) env('ACCOUNT_RECOVERY_EXPIRES_MINUTES', 60)),
        'cooldown_seconds' => max(60, (int) env('ACCOUNT_RECOVERY_COOLDOWN_SECONDS', 60)),
        'requests_per_hour' => max(1, (int) env('ACCOUNT_RECOVERY_REQUESTS_PER_HOUR', 5)),
        'mailer' => env('ACCOUNT_RECOVERY_MAILER', env('ACCOUNT_VERIFICATION_MAILER', 'smtp')),
    ],
];
