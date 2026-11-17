<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->app['env'] = 'production';
    config(['app.debug' => false, 'http.allowed_hosts' => ['^kody\.example\.test$']]);
    Route::get('/_test_ingress', fn (Request $request) => response()->json([
        'ip' => $request->ip(), 'origin' => $request->getSchemeAndHttpHost(), 'login' => route('login'),
    ]));
});

afterEach(function () {
    Request::setTrustedHosts([]);
    Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
});

test('direct clients cannot spoof forwarded IP scheme host port or prefix', function () {
    config(['http.trusted_proxies' => []]);
    $this->withHeaders(['X-Forwarded-For' => '198.51.100.22', 'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Port' => '8443', 'X-Forwarded-Host' => 'attacker.example', 'X-Forwarded-Prefix' => '/fake'])
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.8'])
        ->get('http://kody.example.test/_test_ingress')->assertOk()->assertExactJson([
            'ip' => '203.0.113.8', 'origin' => 'http://kody.example.test', 'login' => 'http://kody.example.test/login',
        ]);
});

test('configured ingress can forward IP and HTTPS but never choose a different generated-link host', function () {
    config(['http.trusted_proxies' => ['10.20.0.0/24']]);
    $this->withHeaders(['X-Forwarded-For' => '198.51.100.22', 'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Port' => '443', 'X-Forwarded-Host' => 'attacker.example', 'X-Forwarded-Prefix' => '/fake'])
        ->withServerVariables(['REMOTE_ADDR' => '10.20.0.5'])
        ->get('http://kody.example.test/_test_ingress')->assertOk()->assertExactJson([
            'ip' => '198.51.100.22', 'origin' => 'https://kody.example.test', 'login' => 'https://kody.example.test/login',
        ]);
});

test('configured proxy trust never extends to a direct origin client', function () {
    config(['http.trusted_proxies' => ['10.20.0.0/24']]);
    $this->withHeaders(['X-Forwarded-For' => '198.51.100.22', 'X-Forwarded-Proto' => 'https'])
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.8'])
        ->get('http://kody.example.test/_test_ingress')->assertOk()->assertJsonPath('ip', '203.0.113.8')
        ->assertJsonPath('origin', 'http://kody.example.test');
});

test('production rejects unapproved hosts even when forwarded host claims the approved name', function () {
    $this->withHeaders(['X-Forwarded-Host' => 'kody.example.test'])
        ->get('http://attacker.example/up')->assertStatus(400);
});

test('proxy shortcuts and all-address CIDRs cannot reenable wildcard trust', function (string $proxy) {
    config(['http.trusted_proxies' => [$proxy]]);
    $this->withoutExceptionHandling();
    expect(fn () => $this->get('http://kody.example.test/up'))->toThrow(InvalidArgumentException::class);
})->with(['*', '**', 'REMOTE_ADDR', 'private_ranges', '0.0.0.0/0', '::/0', '10.0.0.1/33', 'bad-address']);
