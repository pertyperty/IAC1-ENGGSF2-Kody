<?php

return [
    'enabled' => (bool) env('JUDGE0_ENABLED', false),
    'url' => env('JUDGE0_URL', ''),
    'rapidapi_key' => env('JUDGE0_RAPIDAPI_KEY', ''),
    'rapidapi_host' => env('JUDGE0_RAPIDAPI_HOST', ''),
    'auth_token' => env('JUDGE0_AUTH_TOKEN', ''),
    'languages' => ['python' => (int) env('JUDGE0_PYTHON_ID', 0),
        'java' => (int) env('JUDGE0_JAVA_ID', 0), 'cpp' => (int) env('JUDGE0_CPP_ID', 0)],
    // Operational safeguards, not a measured execution-feedback SLA.
    'source_bytes' => 65536,
    'response_bytes' => 262144,
    'evaluation_seconds' => 900,
];
