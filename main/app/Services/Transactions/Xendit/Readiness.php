<?php

namespace App\Services\Transactions\Xendit;

class Readiness
{
    public function available(): bool
    {
        return config('xendit.enabled') === true && config('xendit.contract_verified') === true
            && config('operations.staging_verified') === true && config('operations.budget_enforced') === true
            && is_string(config('operations.operations_owner')) && trim(config('operations.operations_owner')) !== ''
            && is_string(config('operations.backup_responder')) && trim(config('operations.backup_responder')) !== ''
            && is_string(config('xendit.secret_key')) && strlen(config('xendit.secret_key')) >= 20
            && is_string(config('xendit.callback_token')) && strlen(config('xendit.callback_token')) >= 20
            && is_string(config('xendit.business_id')) && config('xendit.business_id') !== ''
            && filter_var(config('app.url'), FILTER_VALIDATE_URL) && str_starts_with(config('app.url'), 'https://')
            && is_int(config('xendit.payment_fee_basis_points')) && config('xendit.payment_fee_basis_points') >= 0
            && config('xendit.payment_fee_basis_points') <= 1000
            && is_int(config('xendit.payment_fee_fixed_minor')) && config('xendit.payment_fee_fixed_minor') >= 0
            && is_int(config('xendit.payout_fee_minor')) && config('xendit.payout_fee_minor') >= 0
            && is_int(config('xendit.refund_fee_minor')) && config('xendit.refund_fee_minor') >= 0
            && (config('queue.connections.database.connection') ?? config('database.default')) === config('database.default');
    }

    public function fee(int $gross): int
    {
        abort_unless($this->available(), 503, 'Payments are not available yet.');

        // Contract configuration includes VAT and per-attempt processing costs.
        return intdiv($gross * config('xendit.payment_fee_basis_points') + 9999, 10000) + config('xendit.payment_fee_fixed_minor');
    }
}
