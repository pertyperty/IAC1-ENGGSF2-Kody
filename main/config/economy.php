<?php

return [
    'policy_version' => 1,
    'packages' => [
        'starter' => ['label' => 'Starter', 'price_minor' => 10000, 'kodebits' => 105],
        'builder' => ['label' => 'Builder', 'price_minor' => 30000, 'kodebits' => 330],
        'explorer' => ['label' => 'Explorer', 'price_minor' => 50000, 'kodebits' => 575],
    ],
    'price_bands' => ['module' => [5, 100], 'course' => [20, 500], 'challenge' => [5, 50]],
    'ranks' => [0 => 'Seedling', 200 => 'Explorer', 600 => 'Builder', 1500 => 'Solver', 3000 => 'Architect'],
    'xp' => ['starter' => 20, 'module' => 40, 'weekly_pass' => 25, 'challenge' => ['Easy' => 50, 'Medium' => 100, 'Hard' => 150]],
    'weekly_prizes' => [1 => 10, 2 => 6, 3 => 4],
    'creator_basis_points' => 6500,
    'reward_cap_basis_points' => 400,
    'reward_backing_minor' => 100,
    'maturity_days' => 14,
    'purchase_refund_days' => 14,
    'access_refund_days' => 7,
    'minimum_payout_minor' => 50000,
];
