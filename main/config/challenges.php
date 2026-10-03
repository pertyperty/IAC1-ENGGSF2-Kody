<?php

return [
    // Approved Version 1 scope; provider compiler IDs are configured separately.
    'languages' => ['python' => 'Python', 'java' => 'Java', 'cpp' => 'C++'],
    // Authoring safeguards, not a claim about the future provider's supported limits.
    'authoring' => ['max_test_cases' => 20, 'max_case_characters' => 16384,
        'min_cpu_time_ms' => 100, 'max_cpu_time_ms' => 5000,
        'min_memory_kib' => 16384, 'max_memory_kib' => 262144],
];
