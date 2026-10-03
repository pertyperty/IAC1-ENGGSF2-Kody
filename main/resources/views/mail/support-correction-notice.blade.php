<p>An Administrator recorded a support correction for your Kody account at {{ \Carbon\CarbonImmutable::parse($occurredAt)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila.</p>
<p>Fields corrected: {{ implode(', ', array_map(fn ($field) => str_replace('_', ' ', $field), $fields)) }}. Later actions may have changed your account details.</p>
<p>Your previous sessions were ended for security. Sign in again to view your profile. If you did not request this help, contact Kody support.</p>
