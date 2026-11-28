<?php

namespace App\Services\Transactions\Xendit;

use Illuminate\Support\Facades\DB;

class Readiness
{
    public function available(): bool
    {
        return config('xendit.sandbox_enabled') !== true
            && ! str_starts_with((string) config('xendit.secret_key'), 'xnd_development_')
            && config('xendit.enabled') === true && config('xendit.contract_verified') === true
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

    public function checkoutAvailable(): bool
    {
        return $this->sandboxAvailable() || $this->available();
    }

    public function sandboxAvailable(): bool
    {
        if (! app()->environment('local') || config('xendit.sandbox_enabled') !== true
            || config('database.default') !== 'pgsql'
            || config('xendit.enabled') !== false || config('xendit.contract_verified') !== false) {
            return false;
        }

        $database = DB::connection('pgsql')->getConfig();
        $key = config('xendit.secret_key');
        $token = config('xendit.callback_token');
        $merchant = config('xendit.business_id');
        $returnUrl = config('xendit.sandbox_return_url');
        $localHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return empty($database['url']) && ! isset($database['read']) && ! isset($database['write'])
            && in_array($database['host'] ?? null, ['127.0.0.1', 'localhost'], true)
            && ($database['database'] ?? null) === 'kody_xendit_sandbox'
            && is_numeric($database['port'] ?? null) && (int) $database['port'] > 0 && (int) $database['port'] !== 5432
            && (config('queue.connections.database.connection') ?? 'pgsql') === 'pgsql'
            && in_array($localHost, ['127.0.0.1', 'localhost'], true)
            && is_string($key) && strlen($key) >= 20 && str_starts_with($key, 'xnd_development_')
            && is_string($token) && strlen($token) >= 20
            && is_string($merchant) && trim($merchant) !== ''
            && is_string($returnUrl) && filter_var($returnUrl, FILTER_VALIDATE_URL)
            && str_starts_with($returnUrl, 'https://');
    }

    public function fee(int $gross): int
    {
        abort_unless($this->available(), 503, 'Payments are not available yet.');

        // Contract configuration includes VAT and per-attempt processing costs.
        return intdiv($gross * config('xendit.payment_fee_basis_points') + 9999, 10000) + config('xendit.payment_fee_fixed_minor');
    }
}
