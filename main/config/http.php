<?php

return [
    // Exact addresses/CIDRs only. Empty means forwarded headers are untrusted.
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),
    // Exact hostnames, without scheme or port; never accept host input as a regex.
    'allowed_hosts' => array_map(fn (string $host): string => '^'.preg_quote($host, '/').'$',
        array_filter(array_map('trim', explode(',', (string) (env('APP_ALLOWED_HOSTS') ?: (parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost')))))),
];
