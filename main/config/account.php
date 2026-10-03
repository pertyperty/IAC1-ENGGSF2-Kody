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
];
