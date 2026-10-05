<?php

namespace App\Services\Transactions\Xendit;

use App\Support\ExactMoney;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class Client
{
    public function request(string $method, string $path, #[\SensitiveParameter] ?array $body = null, string $version = '2024-11-11', ?string $idempotency = null): array
    {
        abort_unless(app(Readiness::class)->available(), 503, 'Payments are unavailable.');
        try {
            $http = Http::withBasicAuth(config('xendit.secret_key'), '')->acceptJson()->withHeaders(['api-version' => $version])
                ->connectTimeout(5)->timeout(15)->withoutRedirecting();
            if ($idempotency !== null) {
                $http = $http->withHeaders(['idempotency-key' => $idempotency]);
            }
            if ($body !== null) {
                // Only generated decimal amounts may be inserted as numeric JSON tokens.
                $json = json_encode($body, JSON_THROW_ON_ERROR);
                $json = preg_replace('/("(?:request_amount|amount)":)"__PHP_AMOUNT_([0-9]+\.[0-9]{2})__"/', '$1$2', $json);
                $http = $http->withBody($json, 'application/json');
            }
            $response = $http->send($method, 'https://api.xendit.co'.$path);
            if (! $response->successful() || strlen($response->body()) > 131072) {
                throw new RuntimeException('Provider response requires review.');
            }

            return ExactMoney::decode($response->body());
        } catch (Throwable) {
            // Provider errors can embed credentials, recipient data or request bodies.
            throw new RuntimeException('The financial provider response requires reconciliation.');
        }
    }

    public function createPayment(object $purchase): ProviderResult
    {
        return $this->payment($this->request('POST', '/v3/payment_requests', ['reference_id' => $purchase->id, 'type' => 'PAY',
            'country' => 'PH', 'currency' => 'PHP', 'request_amount' => '__PHP_AMOUNT_'.ExactMoney::decimal($purchase->gross_minor).'__',
            'capture_method' => 'AUTOMATIC', 'channel_code' => 'GCASH',
            'channel_properties' => ['success_return_url' => rtrim(config('app.url'), '/').route('wallet.index', absolute: false), 'failure_return_url' => rtrim(config('app.url'), '/').route('wallet.index', absolute: false)],
            'description' => 'Kody KodeBits']));
    }

    public function retrievePayment(string $id): ProviderResult
    {
        $this->id($id, 'pr-');

        return $this->payment($this->request('GET', '/v3/payment_requests/'.$id));
    }

    public function payment(array $data): ProviderResult
    {
        $id = $data['payment_request_id'] ?? $data['id'] ?? '';
        $this->id($id, 'pr-');
        if (($data['type'] ?? null) !== 'PAY' || ($data['country'] ?? null) !== 'PH' || ($data['currency'] ?? null) !== 'PHP'
            || ($data['channel_code'] ?? null) !== 'GCASH' || ($data['capture_method'] ?? null) !== 'AUTOMATIC') {
            throw new RuntimeException('Unexpected payment proof.');
        }
        $checkout = null;
        foreach ($data['actions'] ?? [] as $action) {
            if (($action['type'] ?? null) === 'REDIRECT_CUSTOMER' && ($action['descriptor'] ?? null) === 'WEB_URL') {
                $checkout = $this->checkout($action['value'] ?? '');
            }
        }
        $captures = [];
        foreach ($data['captures'] ?? [] as $capture) {
            $this->id($capture['capture_id'] ?? '', str_starts_with($capture['capture_id'] ?? '', 'cptr-') ? 'cptr-' : 'cap-');
            if (isset($captures[$capture['capture_id']])) {
                throw new RuntimeException('Duplicate capture reference.');
            }
            $captures[$capture['capture_id']] = ExactMoney::minor($capture['capture_amount'] ?? null);
        }
        ksort($captures);

        return new ProviderResult($id, $this->text($data, 'reference_id'), $this->text($data, 'status'),
            ExactMoney::minor($data['request_amount'] ?? null), 'PHP', $this->text($data, 'business_id'),
            $checkout, $data['latest_payment_id'] ?? null, $captures);
    }

    public function createPayout(object $payout, array $recipient): ProviderResult
    {
        $data = $this->request('POST', '/v3/payouts', ['reference_id' => $payout->id, 'recipient' => $recipient,
            'payout_details' => ['source_currency' => 'PHP', 'source_amount' => $payout->gross_minor - $payout->fee_minor, 'destination_currency' => 'PHP'],
            'source_of_fund' => 'BUSINESS_REVENUE', 'purpose_code' => config('xendit.payout_purpose'), 'description' => 'Kody creator earnings'], '2025-09-01', $payout->id);

        return $this->payout($data);
    }

    public function retrievePayout(string $id): ProviderResult
    {
        $this->id($id, 'po-');

        return $this->payout($this->request('GET', '/v3/payouts/'.$id, version: '2025-09-01'));
    }

    public function payout(array $data): ProviderResult
    {
        $this->id($data['payout_id'] ?? '', 'po-');
        if (($data['source_currency'] ?? null) !== 'PHP' || ($data['destination_currency'] ?? null) !== 'PHP'
            || ! is_int($data['source_amount'] ?? null) || ! is_int($data['destination_amount'] ?? null)) {
            throw new RuntimeException('Unexpected payout proof.');
        }

        return new ProviderResult($data['payout_id'], $this->text($data, 'reference_id'), $this->text($data, 'status'), $data['source_amount'],
            'PHP', $data['business_id'] ?? null, recipient: $data['recipient']['account_details'] ?? [], destinationMinor: $data['destination_amount']);
    }

    public function createRefund(object $refund, object $purchase): ProviderResult
    {
        return $this->refund($this->request('POST', '/refunds', ['reference_id' => $refund->id, 'payment_request_id' => $purchase->provider_request_id,
            'currency' => 'PHP', 'amount' => '__PHP_AMOUNT_'.ExactMoney::decimal($purchase->gross_minor).'__', 'reason' => 'REQUESTED_BY_CUSTOMER']));
    }

    public function refund(array $data): ProviderResult
    {
        $this->id($data['id'] ?? '', 'rfd-');
        if (($data['currency'] ?? null) !== 'PHP' || ($data['channel_code'] ?? null) !== 'GCASH') {
            throw new RuntimeException('Unexpected refund proof.');
        }

        return new ProviderResult($data['id'], $this->text($data, 'reference_id'), $this->text($data, 'status'), ExactMoney::minor($data['amount'] ?? null),
            'PHP', $data['business_id'] ?? null, paymentRequestId: $data['payment_request_id'] ?? null, paymentId: $data['payment_id'] ?? null);
    }

    public function checkout(string $url): string
    {
        $parts = parse_url($url);
        if (! filter_var($url, FILTER_VALIDATE_URL) || ($parts['scheme'] ?? null) !== 'https' || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['port']) || ! in_array($parts['host'] ?? null, config('xendit.checkout_hosts'), true) || strlen($url) > 2000) {
            throw new RuntimeException('Unsupported provider checkout destination.');
        }

        return $url;
    }

    public function id(mixed $id, string $prefix): void
    {
        if (! is_string($id) || ! preg_match('/^'.preg_quote($prefix, '/').'[a-zA-Z0-9-]{8,96}$/D', $id)) {
            throw new RuntimeException('Invalid provider reference.');
        }
    }

    private function text(array $data, string $key): string
    {
        if (! is_string($data[$key] ?? null) || $data[$key] === '' || strlen($data[$key]) > 255) {
            throw new RuntimeException('Incomplete provider proof.');
        }

        return $data[$key];
    }
}
