<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TrustConfiguredProxies extends TrustProxies
{
    protected function setTrustedProxyIpAddresses(Request $request): void
    {
        $proxies = config('http.trusted_proxies', []);
        foreach ($proxies as $proxy) {
            $parts = explode('/', $proxy);
            $address = $parts[0];
            $maximum = str_contains($address, ':') ? 128 : 32;
            if (! filter_var($address, FILTER_VALIDATE_IP) || count($parts) > 2
                || (isset($parts[1]) && (! ctype_digit($parts[1]) || (int) $parts[1] < 1 || (int) $parts[1] > $maximum))) {
                throw new InvalidArgumentException('TRUSTED_PROXIES requires explicit IP addresses or nonzero CIDR ranges.');
            }
        }
        $this->setTrustedProxyIpAddressesToSpecificIps($request, $proxies);
    }

    protected function headers(): int
    {
        // Ingress must preserve the approved Host. Forwarded host/prefix are not authoritative.
        return Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT;
    }
}
