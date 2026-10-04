<?php

$minorSetting = static function (string $key): ?int {
    $value = env($key);

    return is_string($value) && preg_match('/^(0|[1-9][0-9]{0,8})$/D', $value) ? (int) $value : null;
};

return [
    'enabled' => (bool) env('XENDIT_ENABLED', false),
    'contract_verified' => (bool) env('XENDIT_CONTRACT_VERIFIED', false),
    'secret_key' => env('XENDIT_SECRET_KEY'),
    'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
    'business_id' => env('XENDIT_BUSINESS_ID'),
    'payment_fee_basis_points' => $minorSetting('XENDIT_PAYMENT_FEE_BASIS_POINTS'),
    'payment_fee_fixed_minor' => $minorSetting('XENDIT_PAYMENT_FEE_FIXED_MINOR'),
    'payout_fee_minor' => $minorSetting('XENDIT_PAYOUT_FEE_MINOR'),
    'refund_fee_minor' => $minorSetting('XENDIT_REFUND_FEE_MINOR'),
    'checkout_hosts' => ['checkout.xendit.co', 'xen.to', 'm.gcash.com'],
    'payout_purpose' => 'ROYALTY_FEES',
];
